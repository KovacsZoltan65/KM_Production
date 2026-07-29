<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use Spatie\Activitylog\Support\ActivityLogger;

/** A Spatie activity log egységes alkalmazási auditbejegyzéseit készíti. */
class AuditLogService
{
    /**
     * Az adatvédelmi szempontból érzékeny modellek kifejezett auditmezői.
     *
     * @var array<class-string<Model>, list<string>>
     */
    private const MODEL_ALLOWLISTS = [
        User::class => ['id', 'name', 'email', 'email_verified_at', 'created_at'],
        Employee::class => [
            'id', 'user_id', 'professional_role_id', 'employee_number', 'name',
            'email', 'phone', 'is_active', 'hired_at', 'left_at', 'created_at',
        ],
        Customer::class => [
            'id', 'code', 'name', 'tax_number', 'email', 'phone',
            'billing_address', 'shipping_address', 'is_active', 'created_at',
        ],
        Supplier::class => [
            'id', 'code', 'name', 'tax_number', 'email', 'phone',
            'address', 'is_active', 'created_at',
        ],
        Document::class => [
            'id', 'documentable_type', 'documentable_id', 'document_type',
            'title', 'mime_type', 'file_size', 'version', 'is_current',
            'approved', 'processing_status', 'processing_confidence',
            'processed_at', 'uploaded_by', 'approved_by', 'approved_at', 'created_at',
        ],
    ];

    /** @var list<string> */
    private const EXCLUDED_ATTRIBUTES = [
        'password',
        'password_hash',
        'remember_token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'client_secret',
        'private_key',
        'updated_at',
    ];

    /**
     * Rögzíti a modellhez kapcsolódó üzleti eseményt és kiegészítő adatokat.
     *
     * @param  array<string, mixed>  $properties  Az auditbejegyzés metaadatai.
     */
    public function log(
        string $event,
        ?Model $subject = null,
        array $properties = [],
        ?User $causer = null
    ): void {
        $activity = activity()
            ->event($event)
            ->withProperties($properties);

        if ($subject !== null) {
            $activity->performedOn($subject);
        }

        if ($causer !== null) {
            $activity->causedBy($causer);
        }

        $activity->log($event);
    }

    /**
     * A létrehozott rekord teljes, engedélyezett állapotát rögzíti.
     *
     * @param  array<string, mixed>  $properties  Az esemény üzleti metaadatai.
     */
    public function logCreated(
        string $event,
        Model $subject,
        ?User $causer = null,
        array $properties = [],
    ): void {
        $attributes = $this->auditableAttributes($subject);

        $this->activity($event, $subject, $properties, $causer)
            ->withChanges(['attributes' => $attributes])
            ->log($event);
    }

    /**
     * Csak a ténylegesen megváltozott, engedélyezett rekordmezőket rögzíti.
     *
     * @param  array<string, mixed>  $original  A mentés előtt eltett nyers attribútumok.
     * @param  array<string, mixed>  $properties  Az esemény üzleti metaadatai.
     */
    public function logUpdated(
        string $event,
        Model $subject,
        array $original,
        ?User $causer = null,
        array $properties = [],
    ): void {
        $changes = $this->attributeChanges($subject, $original);

        if ($changes['old'] === [] && $properties === []) {
            return;
        }

        $this->activity($event, $subject, $properties, $causer)
            ->withChanges($changes)
            ->log($event);
    }

    /**
     * Visszaadja a modell biztonságosan normalizált, auditható állapotát.
     *
     * @return array<string, mixed>
     */
    public function auditableAttributes(Model $model): array
    {
        return $this->normalizedAttributes($model, $model->getAttributes());
    }

    /**
     * Elkészíti a mentés előtti és utáni, dirty-only attribútumdiffet.
     *
     * @param  array<string, mixed>  $original
     * @return array{old: array<string, mixed>, attributes: array<string, mixed>}
     */
    public function attributeChanges(Model $model, array $original): array
    {
        $current = $this->auditableAttributes($model);
        $originalModel = clone $model;
        $originalModel->setRawAttributes($original, true);
        $old = $this->normalizedAttributes($originalModel, $original);
        $keys = array_intersect(array_keys($old), array_keys($current));
        sort($keys);

        $changes = ['old' => [], 'attributes' => []];

        foreach ($keys as $key) {
            if ($old[$key] === $current[$key]) {
                continue;
            }

            $changes['old'][$key] = $old[$key];
            $changes['attributes'][$key] = $current[$key];
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function activity(
        string $event,
        Model $subject,
        array $properties,
        ?User $causer,
    ): ActivityLogger {
        $activity = activity()
            ->event($event)
            ->performedOn($subject)
            ->withProperties($properties);

        if ($causer !== null) {
            $activity->causedBy($causer);
        }

        return $activity;
    }

    /**
     * @param  array<string, mixed>  $rawAttributes
     * @return array<string, mixed>
     */
    private function normalizedAttributes(Model $model, array $rawAttributes): array
    {
        $allowlist = self::MODEL_ALLOWLISTS[$model::class] ?? null;
        $casts = $model->getCasts();
        $attributes = [];

        foreach (array_keys($rawAttributes) as $key) {
            if (! $this->isAuditableKey($key, $allowlist, $casts[$key] ?? null)) {
                continue;
            }

            $value = $model->getAttribute($key);

            if (! $this->isNormalizable($value)) {
                continue;
            }

            $attributes[$key] = $this->normalizeValue($value);
        }

        ksort($attributes);

        return $attributes;
    }

    /**
     * @param  list<string>|null  $allowlist
     */
    private function isAuditableKey(string $key, ?array $allowlist, mixed $cast): bool
    {
        if ($allowlist !== null && ! \in_array($key, $allowlist, true)) {
            return false;
        }

        if (\in_array($key, self::EXCLUDED_ATTRIBUTES, true)) {
            return false;
        }

        if (preg_match('/(^|_)(password|token|secret|private_key)($|_)/i', $key) === 1) {
            return false;
        }

        return ! (\is_string($cast) && (
            str_starts_with($cast, 'encrypted')
            || $cast === 'hashed'
        ));
    }

    private function isNormalizable(mixed $value): bool
    {
        if ($value === null || \is_scalar($value) || $value instanceof BackedEnum || $value instanceof DateTimeInterface || $value instanceof Stringable || $value instanceof \Stringable) {
            return true;
        }

        if (! \is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! $this->isNormalizable($item)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof Stringable || $value instanceof \Stringable) {
            return (string) $value;
        }

        if (\is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeValue($item);
            }

            return $normalized;
        }

        return $value;
    }
}

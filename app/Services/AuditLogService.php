<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** A Spatie activity log egységes alkalmazási auditbejegyzéseit készíti. */
class AuditLogService
{
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
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $properties
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

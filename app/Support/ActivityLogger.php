<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActivityLogger
{
    /**
     * Write activity log in a safe way.
     *
     * @param string $module
     * @param string $action
     * @param string $description
     * @param string|null $referenceNo
     * @param string|null $subjectType
     * @param int|null $subjectId
     * @param array $meta
     * @param string|null $actorName
     * @param string|null $actorRole
     * @return void
     */
    public static function log(
        $module,
        $action,
        $description,
        $referenceNo = null,
        $subjectType = null,
        $subjectId = null,
        array $meta = [],
        $actorName = null,
        $actorRole = null
    ) {
        if (!DB::getSchemaBuilder()->hasTable('activity_logs')) {
            return;
        }

        $actorId = null;
        if (Auth::check()) {
            $actorId = Auth::id();
            if ($actorName === null) {
                $actorName = Auth::user()->name;
            }
            if ($actorRole === null) {
                $actorRole = Auth::user()->jabatan;
            }
        }

        try {
            DB::table('activity_logs')->insert([
                'actor_user_id' => $actorId,
                'actor_name' => $actorName,
                'actor_role' => $actorRole,
                'module' => $module,
                'action' => $action,
                'reference_no' => $referenceNo,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'description' => $description,
                'meta_json' => empty($meta) ? null : json_encode($meta),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Logging failure must never break business flow.
        }
    }
}

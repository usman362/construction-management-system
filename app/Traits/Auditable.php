<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Auto-records create/update/delete events on any Eloquent model.
 *
 * Usage:
 *   class Timesheet extends Model { use Auditable; }
 *
 * Opt-out per model via: protected array $auditExclude = ['updated_at', ...];
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->recordAudit('created', $model->auditableSnapshot($model->getAttributes()));
        });

        static::updated(function ($model) {
            $changes = $model->getDirty();

            // Skip pure timestamp touches.
            $ignored = array_merge(['updated_at'], $model->auditExclude ?? []);
            foreach ($ignored as $field) {
                unset($changes[$field]);
            }
            if (empty($changes)) {
                return;
            }

            $original = $model->getOriginal();
            $diff = [];
            foreach ($changes as $field => $new) {
                $diff[$field] = [
                    'old' => $original[$field] ?? null,
                    'new' => $new,
                ];
            }

            $model->recordAudit('updated', $diff);
        });

        static::deleted(function ($model) {
            $model->recordAudit('deleted', $model->auditableSnapshot($model->getOriginal()));
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->recordAudit('restored', $model->auditableSnapshot($model->getAttributes()));
            });
        }
    }

    /**
     * Inline history on any auditable model.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest('created_at');
    }

    /**
     * Strip fields we shouldn't persist in the audit blob (pivots, secrets, long blobs).
     */
    protected function auditableSnapshot(array $attrs): array
    {
        $ignored = array_merge(['updated_at', 'password', 'remember_token'], $this->auditExclude ?? []);
        foreach ($ignored as $field) {
            unset($attrs[$field]);
        }
        return $attrs;
    }

    protected function recordAudit(string $event, array $changes): void
    {
        $user = auth()->user();
        $req  = request();

        AuditLog::create([
            'user_id'        => $user?->id,
            'user_name'      => $user?->name,
            'auditable_type' => static::class,
            'auditable_id'   => $this->getKey(),
            'event'          => $event,
            'changes'        => $changes,
            'ip_address'     => $req?->ip(),
            'user_agent'     => substr((string) $req?->userAgent(), 0, 500),
            'created_at'     => now(),
        ]);
    }
}

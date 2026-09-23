<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            static::logActivity($model, 'created');
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            if ($model instanceof \App\Models\User && isset($dirty['password'])) {
                unset($dirty['password']);
                if (empty($dirty) && $model->wasChanged('password')) {
                    $dirty = ['password' => '[changed]'];
                }
            }
            static::logActivity($model, 'updated', $dirty);
        });

        static::deleted(function ($model) {
            static::logActivity($model, 'deleted');
        });
    }

    protected static function logActivity($model, string $action, array $changes = []): void
    {
        $description = class_basename($model) . " {$action} ID {$model->getKey()}";
        if (! empty($changes)) {
            $keys = implode(', ', array_keys($changes));
            if ($model instanceof \App\Models\User && isset($changes['password'])) {
                $description .= " (password diubah)";
            } else {
                $description .= " (changed: {$keys})";
            }
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'description' => $description,
        ]);
    }
}

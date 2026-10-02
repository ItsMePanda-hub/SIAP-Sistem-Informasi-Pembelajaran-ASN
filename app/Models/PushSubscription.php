<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = ['user_id', 'endpoint', 'endpoint_hash', 'p256dh', 'auth'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::saving(function (PushSubscription $model) {
            if (empty($model->endpoint_hash) && ! empty($model->endpoint)) {
                $model->endpoint_hash = hash('sha256', $model->endpoint);
            } elseif (! empty($model->endpoint)) {
                $model->endpoint_hash = hash('sha256', $model->endpoint);
            }
        });
    }
}

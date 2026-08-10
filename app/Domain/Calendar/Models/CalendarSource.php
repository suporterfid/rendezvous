<?php

namespace App\Domain\Calendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Support\Str;

class CalendarSource extends Model
{
    protected $table = 'calendar_sources';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'calendar_id',
        'source_type',
        'config_json',
        'secret_ref',
        'last_synced_at',
        'last_sync_state',
    ];

    protected $casts = [
        'config_json' => 'array',
        'last_synced_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::uuid();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class, 'calendar_id');
    }

    public function mailboxConfig(): HasOne
    {
        return $this->hasOne(MailboxIngestConfig::class, 'source_id');
    }
}

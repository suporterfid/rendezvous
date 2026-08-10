<?php

namespace App\Domain\Calendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $table = 'events';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'calendar_id',
        'uid',
        'sequence',
        'recurrence_id',
        'method_origin',
        'organizer_json',
        'attendees_json',
        'summary',
        'description',
        'location',
        'dtstart_utc',
        'dtend_utc',
        'tzid_original',
        'rrule',
        'exdates_json',
        'status',
        'source_type',
        'raw_vevent_hash',
    ];

    protected $casts = [
        'organizer_json' => 'array',
        'attendees_json' => 'array',
        'exdates_json' => 'array',
        'dtstart_utc' => 'datetime',
        'dtend_utc' => 'datetime',
        'sequence' => 'integer',
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
}

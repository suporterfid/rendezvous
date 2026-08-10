<?php

namespace App\Domain\Calendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Support\Str;

class Calendar extends Model
{
    protected $table = 'calendars';

    protected $fillable = [
        'public_id',
        'tenant_id',
        'name',
        'color',
        'visibility_default',
    ];

    protected $casts = [
        'visibility_default' => 'boolean',
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

    public function sources(): HasMany
    {
        return $this->hasMany(CalendarSource::class, 'calendar_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'calendar_id');
    }
}

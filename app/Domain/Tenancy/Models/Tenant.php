<?php

namespace App\Domain\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Calendar\Models\Calendar;
use App\Domain\Calendar\Models\CalendarSource;
use App\Domain\Calendar\Models\Event;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $table = 'tenants';

    protected $fillable = [
        'public_id',
        'name',
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

    public function calendars(): HasMany
    {
        return $this->hasMany(Calendar::class, 'tenant_id');
    }

    public function calendarSources(): HasMany
    {
        return $this->hasMany(CalendarSource::class, 'tenant_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'tenant_id');
    }
}

<?php

namespace App\Domain\Security\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Tenancy\Models\Tenant;

class Secret extends Model
{
    protected $table = 'secrets';

    protected $fillable = [
        'tenant_id',
        'secret_key',
        'encrypted_value',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}

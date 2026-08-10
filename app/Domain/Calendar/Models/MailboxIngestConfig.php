<?php

namespace App\Domain\Calendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Tenancy\Models\Tenant;

class MailboxIngestConfig extends Model
{
    protected $table = 'mailbox_ingest_configs';

    protected $fillable = [
        'tenant_id',
        'source_id',
        'imap_host',
        'imap_port',
        'folder',
        'secret_ref',
        'claim_token',
        'claimed_at',
        'claim_expires_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'claim_expires_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CalendarSource::class, 'source_id');
    }
}

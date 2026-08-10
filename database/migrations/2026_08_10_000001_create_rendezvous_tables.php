<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 36)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 36)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 32)->default('#6366f1');
            $table->boolean('visibility_default')->default(true);
            $table->timestamps();

            $table->index(['tenant_id']);
        });

        Schema::create('calendar_sources', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 36)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('calendar_id')->constrained('calendars')->cascadeOnDelete();
            $table->string('source_type', 32); // 'email_mailbox', 'ics_feed', 'caldav'
            $table->text('config_json')->nullable();
            $table->string('secret_ref')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_state', 32)->default('idle');
            $table->timestamps();

            $table->index(['tenant_id', 'source_type', 'last_synced_at']);
        });

        Schema::create('mailbox_ingest_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('calendar_sources')->cascadeOnDelete();
            $table->string('imap_host');
            $table->integer('imap_port')->default(993);
            $table->string('folder')->default('INBOX');
            $table->string('secret_ref');
            $table->string('claim_token', 64)->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('claim_expires_at')->nullable();
            $table->timestamps();

            $table->index(['claim_expires_at']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 36)->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('calendar_id')->constrained('calendars')->cascadeOnDelete();
            $table->string('uid');
            $table->integer('sequence')->default(0);
            $table->string('recurrence_id')->nullable();
            $table->string('method_origin', 32)->default('REQUEST');
            $table->text('organizer_json')->nullable();
            $table->text('attendees_json')->nullable();
            $table->string('summary', 500);
            $table->text('description')->nullable();
            $table->string('location', 500)->nullable();
            $table->dateTime('dtstart_utc');
            $table->dateTime('dtend_utc');
            $table->string('tzid_original', 100)->default('UTC');
            $table->text('rrule')->nullable();
            $table->text('exdates_json')->nullable();
            $table->string('status', 32)->default('confirmed'); // 'confirmed', 'cancelled', 'tentative'
            $table->string('source_type', 32); // 'email_invite', 'ics_feed_subscription', 'caldav_subscription', 'manual'
            $table->string('raw_vevent_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'calendar_id', 'uid', 'recurrence_id'], 'uq_event_uid');
            $table->index(['tenant_id', 'calendar_id', 'dtstart_utc'], 'idx_events_lookup');
        });

        Schema::create('secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('secret_key');
            $table->text('encrypted_value');
            $table->timestamps();

            $table->unique(['tenant_id', 'secret_key'], 'uq_secret_key');
        });

        Schema::create('ingest_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('calendar_sources')->cascadeOnDelete();
            $table->string('message_id')->nullable();
            $table->string('result', 32); // 'processed', 'quarantined', 'duplicate'
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingest_logs');
        Schema::dropIfExists('secrets');
        Schema::dropIfExists('events');
        Schema::dropIfExists('mailbox_ingest_configs');
        Schema::dropIfExists('calendar_sources');
        Schema::dropIfExists('calendars');
        Schema::dropIfExists('tenants');
    }
};

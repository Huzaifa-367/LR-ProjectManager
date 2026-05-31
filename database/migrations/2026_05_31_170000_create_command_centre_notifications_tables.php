<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_member_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 100);
            $table->string('channel');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(
                ['organization_member_id', 'event_type', 'channel'],
                'member_notification_preferences_unique',
            );
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });

        Schema::create('scheduled_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_member_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 100);
            $table->string('channel');
            $table->nullableMorphs('subject');
            $table->timestamp('trigger_at');
            $table->json('payload')->nullable();
            $table->string('dedupe_key')->unique();
            $table->string('status');
            $table->foreignId('notification_delivery_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'trigger_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('member_notification_preferences');
    }
};

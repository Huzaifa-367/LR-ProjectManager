<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_mail_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('provider');
            $table->boolean('is_default')->default(false);
            $table->string('from_name');
            $table->string('from_address');
            $table->string('reply_to_address')->nullable();
            $table->text('config');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_member_id')->constrained('organization_members')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'is_default']);
            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_mail_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_email');
            $table->string('channel');
            $table->string('notification_class');
            $table->string('event_type', 100);
            $table->string('subject', 500)->nullable();
            $table->json('payload')->nullable();
            $table->nullableMorphs('subject');
            $table->string('status');
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'created_at']);
            $table->index(['organization_member_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('organization_mail_profiles');
    }
};

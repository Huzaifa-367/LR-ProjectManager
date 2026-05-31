<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_member_id')->nullable()->constrained('organization_members')->nullOnDelete();
            $table->morphs('subject');
            $table->string('event', 100);
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['organization_id', 'subject_type', 'subject_id', 'created_at'],
                'activity_logs_org_subject_created_idx',
            );
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

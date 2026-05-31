<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('context');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('status');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(
                ['organization_id', 'organization_member_id', 'context', 'status'],
                'ai_sessions_org_member_context_status_idx',
            );
        });

        Schema::create('ai_onboarding_proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_member_id')->constrained('organization_members')->cascadeOnDelete();
            $table->string('proposal_type');
            $table->string('status');
            $table->json('payload');
            $table->text('summary')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('applied_by_member_id')->nullable()->constrained('organization_members')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['ai_session_id', 'version']);
        });

        Schema::create('ai_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_session_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->text('content')->nullable();
            $table->json('proposed_actions')->nullable();
            $table->foreignId('onboarding_proposal_id')->nullable()->constrained('ai_onboarding_proposals')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('tool_name', 100);
            $table->json('tool_input');
            $table->json('tool_output')->nullable();
            $table->string('llm_model', 100)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_audit_logs');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_onboarding_proposals');
        Schema::dropIfExists('ai_sessions');
    }
};

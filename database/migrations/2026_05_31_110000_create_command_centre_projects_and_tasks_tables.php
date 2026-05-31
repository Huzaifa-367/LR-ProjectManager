<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('objective')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->text('next_action')->nullable();
            $table->string('health', 20)->default('active');
            $table->foreignId('owner_member_id')->nullable()->constrained('organization_members')->nullOnDelete();
            $table->foreignId('created_by_member_id')->constrained('organization_members')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'archived_at'], 'projects_org_archived_idx');
        });

        Schema::create('project_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 100);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'slug'], 'project_roles_project_slug_unique');
        });

        Schema::create('project_role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_role_id')->constrained()->cascadeOnDelete();
            $table->string('permission', 100);
            $table->timestamps();

            $table->unique(['project_role_id', 'permission'], 'proj_role_perm_unique');
        });

        Schema::create('project_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_role_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->timestamps();

            $table->unique(['project_id', 'organization_member_id'], 'project_members_unique');
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->foreignId('created_by_member_id')->constrained('organization_members')->cascadeOnDelete();
            $table->string('priority', 20)->nullable();
            $table->string('status', 20)->default('pending');
            $table->dateTime('deadline_date')->nullable();
            $table->string('external_link', 2048)->nullable();
            $table->boolean('is_done')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_member_id')->nullable()->constrained('organization_members')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->integer('sort_order')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'kind', 'is_done'], 'tasks_org_kind_done_idx');
            $table->index(['project_id', 'kind', 'is_done'], 'tasks_project_kind_done_idx');
            $table->index(['organization_id', 'deadline_date'], 'tasks_org_deadline_idx');
        });

        Schema::create('task_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_member_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('assigned_at');
            $table->foreignId('assigned_by_member_id')->nullable()->constrained('organization_members')->nullOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'organization_member_id'], 'task_assignees_unique');
            $table->index('organization_member_id', 'task_assignees_member_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('project_role_permissions');
        Schema::dropIfExists('project_roles');
        Schema::dropIfExists('projects');
    }
};

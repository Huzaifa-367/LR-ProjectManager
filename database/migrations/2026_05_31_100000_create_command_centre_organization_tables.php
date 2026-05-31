<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('organization_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('organization_role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_role_id')->constrained()->cascadeOnDelete();
            $table->string('permission', 100);
            $table->timestamps();

            $table->unique(['organization_role_id', 'permission'], 'org_role_permissions_role_perm_unique');
        });

        Schema::create('organization_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_role_id')->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('email')->nullable();
            $table->string('title')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_primary_org')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->unique(['organization_id', 'user_id'], 'organization_members_org_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_members');
        Schema::dropIfExists('organization_role_permissions');
        Schema::dropIfExists('organization_roles');
        Schema::dropIfExists('organizations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->foreignId('organization_role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_member_id')->constrained('organization_members')->cascadeOnDelete();
            $table->string('token_hash', 64);
            $table->string('status');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('organization_member_id')->nullable()->constrained('organization_members')->nullOnDelete();
            $table->timestamps();

            $table->index(['token_hash']);
            $table->index(['expires_at']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
    }
};

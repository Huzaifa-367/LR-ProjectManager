<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_member_id')->constrained('organization_members')->cascadeOnDelete();
            $table->string('export_type');
            $table->json('filters')->nullable();
            $table->string('status');
            $table->string('disk', 50);
            $table->string('path', 500)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
    }
};

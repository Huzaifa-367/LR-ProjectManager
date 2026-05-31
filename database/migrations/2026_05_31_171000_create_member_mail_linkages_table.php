<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_mail_linkages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_member_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('gmail_address');
            $table->text('app_password');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_mail_linkages');
    }
};

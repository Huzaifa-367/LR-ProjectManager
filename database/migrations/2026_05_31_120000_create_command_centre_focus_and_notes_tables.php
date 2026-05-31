<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_daily_focus', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->date('focus_date');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_auto')->default(false);
            $table->timestamps();

            $table->unique(
                ['organization_member_id', 'task_id', 'focus_date'],
                'member_focus_member_task_date_unique',
            );
            $table->index(
                ['organization_member_id', 'focus_date', 'task_id'],
                'member_focus_member_date_task_idx',
            );
        });

        Schema::create('member_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_member_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_notes');
        Schema::dropIfExists('member_daily_focus');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('work_date');
            $table->timestamp('clock_in_time')->nullable();
            $table->timestamp('clock_out_time')->nullable();
            $table->enum('status', ['出勤中', '退勤済み'])->default('出勤中');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // ユーザーと日付の組み合わせでユニーク制約
            $table->unique(['user_id', 'work_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_records');
    }
};

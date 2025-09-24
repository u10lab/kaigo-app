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
        Schema::create('paid_leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('requested_days');
            $table->text('reason');
            $table->enum('leave_type', ['paid_leave'])->default('paid_leave');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('approver_comment')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->boolean('freee_registered')->default(false);
            $table->string('freee_absence_id')->nullable();
            $table->timestamps();
            
            // インデックス
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paid_leave_applications');
    }
};
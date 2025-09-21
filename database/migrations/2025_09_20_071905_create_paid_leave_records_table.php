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
        Schema::create('paid_leave_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('granted_days');
            $table->integer('used_days')->default(0);
            $table->integer('remaining_days');
            $table->date('grant_date');
            $table->date('expiry_date');
            $table->string('fiscal_year');
            $table->boolean('five_day_obligation_met')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paid_leave_records');
    }
};

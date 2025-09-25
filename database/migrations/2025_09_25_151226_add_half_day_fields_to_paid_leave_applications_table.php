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
        Schema::table('paid_leave_applications', function (Blueprint $table) {
            $table->boolean('half_day')->default(false)->after('leave_type');
            $table->enum('half_day_period', ['morning', 'afternoon'])->nullable()->after('half_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paid_leave_applications', function (Blueprint $table) {
            $table->dropColumn(['half_day', 'half_day_period']);
        });
    }
};

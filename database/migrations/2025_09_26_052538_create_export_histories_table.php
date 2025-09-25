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
        Schema::create('export_histories', function (Blueprint $table) {
            $table->id();
            $table->string('export_type')->default('care_insurance'); // エクスポート種別
            $table->string('period'); // 期間（例: 2025-09）
            $table->integer('record_count')->default(0); // 出力件数
            $table->string('file_name'); // ファイル名
            $table->unsignedBigInteger('exported_by'); // エクスポート実行者
            $table->timestamp('exported_at'); // エクスポート実行日時
            $table->timestamps();
            
            $table->foreign('exported_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_histories');
    }
};

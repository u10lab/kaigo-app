<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * 本番環境用のSeeder
     * 管理者アカウントとサービス種別のみ作成
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,     // 管理者アカウント
            ServiceTypeSeeder::class,   // サービス種別
            ClientSeeder::class,        // テスト用利用者データ
        ]);

        $this->command->info('本番環境用の初期データが作成されました。');
        $this->command->info('');
        $this->command->info('次の手順を実行してください：');
        $this->command->info('1. freee API設定を行う');
        $this->command->info('2. 管理画面で「従業員インポート」を実行');
        $this->command->info('3. 実際の利用者データを手動登録');
        $this->command->info('4. サービス種別を事業所に合わせて調整');
    }
}
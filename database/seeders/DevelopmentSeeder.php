<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * 開発環境用のSeeder
     * テストデータ（従業員、利用者、サービス種別）を作成
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,          // テスト従業員データ
            ClientSeeder::class,        // テスト利用者データ
            ServiceTypeSeeder::class,   // サービス種別データ
        ]);

        $this->command->info('開発環境用のテストデータが作成されました。');
    }
}
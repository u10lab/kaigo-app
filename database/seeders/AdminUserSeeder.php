<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 本番環境用：管理者アカウントのみ作成
     * 従業員データはfreee APIのimport機能を使用してください
     */
    public function run(): void
    {
        // 管理者アカウント（本番用）
        User::firstOrCreate(
            ['email' => 'admin@example.com'], // 実際のメールアドレスに変更してください
            [
                'name' => '管理者',
                'employee_id' => 'ADMIN001',
                'password' => Hash::make('admin123!'), // 本番では強固なパスワードに変更してください
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $this->command->info('管理者アカウントが作成されました。');
        $this->command->info('従業員データはfreee API連携後、管理画面の「従業員インポート」機能をご利用ください。');
    }
}
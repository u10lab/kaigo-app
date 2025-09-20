<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 管理者アカウント
        User::create([
            'name' => '管理者',
            'email' => 'admin@test.com',
            'employee_id' => 'ADMIN001',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // ヘルパーアカウント
        User::create([
            'name' => '田中太郎',
            'email' => 'helper1@test.com',
            'employee_id' => 'HELPER001',
            'password' => Hash::make('password'),
            'pin' => '1234',
            'role' => 'helper',
            'is_active' => true,
        ]);

        User::create([
            'name' => '佐藤花子',
            'email' => 'helper2@test.com',
            'employee_id' => 'HELPER002',
            'password' => Hash::make('password'),
            'pin' => '5678',
            'role' => 'helper',
            'is_active' => true,
        ]);

        User::create([
            'name' => '鈴木次郎',
            'email' => 'helper3@test.com',
            'employee_id' => 'HELPER003',
            'password' => Hash::make('password'),
            'pin' => '9012',
            'role' => 'helper',
            'is_active' => true,
        ]);
    }
}

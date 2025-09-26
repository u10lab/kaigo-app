<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // サービス種別マスタデータ（重複を防ぐためfirstOrCreateを使用）
        ServiceType::firstOrCreate(
            ['care_insurance_code' => '111'],
            [
                'name' => '入浴介護',
                'hourly_rate' => 1500,
            ]
        );

        ServiceType::firstOrCreate(
            ['care_insurance_code' => '112'],
            [
                'name' => 'お掃除',
                'hourly_rate' => 1200,
            ]
        );

        ServiceType::firstOrCreate(
            ['care_insurance_code' => '113'],
            [
                'name' => '生活援助',
                'hourly_rate' => 1300,
            ]
        );
    }
}

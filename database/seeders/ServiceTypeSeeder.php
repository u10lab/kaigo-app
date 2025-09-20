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
        // サービス種別マスタデータ
        ServiceType::create([
            'name' => '入浴介護',
            'care_insurance_code' => '111',
            'hourly_rate' => 1500,
        ]);

        ServiceType::create([
            'name' => 'お掃除',
            'care_insurance_code' => '112',
            'hourly_rate' => 1200,
        ]);

        ServiceType::create([
            'name' => '生活援助',
            'care_insurance_code' => '113',
            'hourly_rate' => 1300,
        ]);
    }
}

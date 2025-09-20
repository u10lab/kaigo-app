<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 利用者マスタデータ
        Client::create([
            'client_number' => 'A001',
            'name' => '山田一郎',
            'care_level' => '要介護2',
            'is_active' => true,
        ]);

        Client::create([
            'client_number' => 'A002',
            'name' => '田中美子',
            'care_level' => '要介護1',
            'is_active' => true,
        ]);

        Client::create([
            'client_number' => 'A003',
            'name' => '佐々木健一',
            'care_level' => '要介護3',
            'is_active' => true,
        ]);
    }
}

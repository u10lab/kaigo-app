<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 環境に応じて適切なSeederを実行
     */
    public function run(): void
    {
        $environment = app()->environment();

        $this->command->info("現在の環境: {$environment}");

        switch ($environment) {
            case 'production':
                $this->command->info('本番環境用のSeederを実行します...');
                $this->call(ProductionSeeder::class);
                break;

            case 'staging':
                $this->command->info('ステージング環境用のSeederを実行します...');
                $this->call(ProductionSeeder::class); // ステージングも本番と同様
                break;

            case 'local':
            case 'testing':
            default:
                $this->command->info('開発環境用のSeederを実行します...');
                $this->call(DevelopmentSeeder::class);
                break;
        }
    }
}

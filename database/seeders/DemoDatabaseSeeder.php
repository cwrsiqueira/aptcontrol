<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDatabaseSeeder extends Seeder
{
    public function run()
    {
        // Carrega a base padrão antes dos dados de exemplo.
        $this->call([
            DatabaseSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}

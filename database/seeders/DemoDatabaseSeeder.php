<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            DatabaseSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}

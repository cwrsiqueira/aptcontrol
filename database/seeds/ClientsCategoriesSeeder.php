<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientsCategoriesSeeder extends Seeder
{
    public function run()
    {
        $cats = ['Padrão', 'Construtor', 'Revendedor', 'Consumidor final'];

        foreach ($cats as $name) {
            DB::table('clients_categories')->updateOrInsert(
                ['name' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}

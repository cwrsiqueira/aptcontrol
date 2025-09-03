<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientsCategoriesSeeder extends Seeder
{
    public function run()
    {
        $cats = ['Padrão', 'Indústria Cerâmica', 'Atacado', 'Varejo'];

        foreach ($cats as $name) {
            DB::table('clients_categories')->updateOrInsert(
                ['name' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}

<?php

use Illuminate\Database\Seeder;

class StatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('states')->truncate();
        $json = File::get("database/data/states.json");
        $data = json_decode($json);
        foreach ($data as $obj) {
            DB::table('states')->insert([
                'id' => $obj->id,
                'name' => $obj->name,
               'country_id' => $obj->country_id,

            ]);
        }
    }
}

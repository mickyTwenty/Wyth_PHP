<?php

use Illuminate\Database\Seeder;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('countries')->truncate();
        $json = File::get("database/data/countries.json");
        $data = json_decode($json);
        foreach ($data as $obj) {
            DB::table('countries')->insert([
                'id' => $obj->id,
                'sortname' => $obj->sortname,
                'name' => $obj->name,
                'phonecode' => $obj->phonecode,

            ]);
        }
    }
}

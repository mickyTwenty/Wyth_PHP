<?php

use Illuminate\Database\Seeder;

class CitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('cities')->truncate();
        $json = File::get("database/data/cities.json");
        $data = json_decode($json);
        foreach ($data as $obj) {
            DB::table('cities')->insert([
                'id' => $obj->id,
                'name' => $obj->name,
                'state_id' => $obj->state_id,

            ]);
        }
    }
}

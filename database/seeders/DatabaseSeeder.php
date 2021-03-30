<?php

use Database\Seeders\PointAddGoogiesSeeder;
use Database\Seeders\PointSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
         $this->call([
             // PointSeeder::class,
             PointAddGoogiesSeeder::class,
         ]);
    }
}

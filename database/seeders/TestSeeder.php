<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserSeeder::class);
        $this->call(OperatingCountrySeeder::class);
        $this->call(RequiredDocumentSeeder::class);
        $this->call(BankSeeder::class);
    }
}

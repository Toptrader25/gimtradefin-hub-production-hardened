<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Production / staging demo content (idempotent, clearly marked [DEMO]).
        $this->call(DemoSeeder::class);
    }
}

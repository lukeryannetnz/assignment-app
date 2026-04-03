<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Foundation\ApplicationSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ApplicationSeeder::class);
    }
}

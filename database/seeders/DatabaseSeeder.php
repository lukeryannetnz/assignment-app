<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CourseSeeder::class,
        ]);

        // Create an admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
            'is_student' => false,
        ]);

        // Create a student user
        User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@example.com',
            'is_admin' => false,
            'is_student' => true,
        ]);

        // Create a user with both roles
        User::factory()->create([
            'name' => 'Admin Student User',
            'email' => 'both@example.com',
            'is_admin' => true,
            'is_student' => true,
        ]);

        // Create additional students
        User::factory()->count(5)->create([
            'is_admin' => false,
            'is_student' => true,
        ]);
    }
}

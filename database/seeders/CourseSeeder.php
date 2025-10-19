<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample courses
        Course::create([
            'name' => 'Learn PHP',
            'description' => 'This course teaches you PHP fundamentals and best practices',
        ]);

        Course::create([
            'name' => 'Advanced Laravel',
            'description' => 'Master Laravel framework with advanced patterns and techniques',
        ]);

        Course::create([
            'name' => 'Database Design',
            'description' => 'Learn to design efficient and scalable database schemas',
        ]);
    }
}

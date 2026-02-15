<?php

declare(strict_types=1);

namespace Database\Seeders\CourseCatalog;

use App\Models\CourseCatalog\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(int $tenantId): void
    {
        // Create sample courses
        Course::create([
            'tenant_id' => $tenantId,
            'name' => 'Learn PHP',
            'description' => 'This course teaches you PHP fundamentals and best practices',
        ]);

        Course::create([
            'tenant_id' => $tenantId,
            'name' => 'Advanced Laravel',
            'description' => 'Master Laravel framework with advanced patterns and techniques',
        ]);

        Course::create([
            'tenant_id' => $tenantId,
            'name' => 'Database Design',
            'description' => 'Learn to design efficient and scalable database schemas',
        ]);
    }
}

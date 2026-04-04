<?php

declare(strict_types=1);

namespace Database\Seeders\CourseCatalog;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(int $tenantId): void
    {
        DB::insert(
            'INSERT INTO courses (tenant_id, name, description, created_at, updated_at)
             VALUES
                (?, ?, ?, ?, ?),
                (?, ?, ?, ?, ?),
                (?, ?, ?, ?, ?)',
            [
                $tenantId,
                'Learn PHP',
                'This course teaches you PHP fundamentals and best practices',
                now(),
                now(),
                $tenantId,
                'Advanced Laravel',
                'Master Laravel framework with advanced patterns and techniques',
                now(),
                now(),
                $tenantId,
                'Database Design',
                'Learn to design efficient and scalable database schemas',
                now(),
                now(),
            ],
        );
    }
}

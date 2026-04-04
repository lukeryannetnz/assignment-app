<?php

declare(strict_types=1);

namespace App\Domains\Enrollment\Services;

use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    public function isUserEnrolled(int $tenantId, int $userId, int $courseId): bool
    {
        $row = DB::selectOne(
            'SELECT 1 AS present
             FROM course_user
             WHERE tenant_id = ?
               AND user_id = ?
               AND course_id = ?
             LIMIT 1',
            [$tenantId, $userId, $courseId],
        );

        return $row !== null;
    }

    public function enroll(int $tenantId, int $userId, int $courseId): void
    {
        DB::insert(
            'INSERT INTO course_user (tenant_id, user_id, course_id, enrolled_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$tenantId, $userId, $courseId, now(), now(), now()],
        );
    }

    public function unenroll(int $tenantId, int $userId, int $courseId): void
    {
        DB::delete(
            'DELETE FROM course_user
             WHERE tenant_id = ?
               AND user_id = ?
               AND course_id = ?',
            [$tenantId, $userId, $courseId],
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\CourseCatalog\Services;

use App\Domains\CourseCatalog\Data\CourseData;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourseCatalogService
{
    /**
     * @return LengthAwarePaginator<int, CourseData>
     */
    public function paginateStudentCourses(int $tenantId, int $perPage = 4, ?int $page = null): LengthAwarePaginator
    {
        return $this->paginateCourses($tenantId, $perPage, $page);
    }

    /**
     * @return list<int>
     */
    public function enrolledCourseIds(int $tenantId, int $userId): array
    {
        /** @var list<object{id: int}> $rows */
        $rows = DB::select(
            'SELECT c.id
             FROM courses c
             INNER JOIN course_user cu
                ON cu.course_id = c.id
               AND cu.tenant_id = c.tenant_id
             WHERE c.tenant_id = ?
               AND cu.user_id = ?
             ORDER BY c.id ASC',
            [$tenantId, $userId],
        );

        return array_map(static fn (object $row): int => (int) $row->id, $rows);
    }

    /**
     * @return CourseData
     */
    public function findCourse(int $tenantId, int $courseId): CourseData
    {
        /** @var object{id: int, tenant_id: int, name: string, description: string, users_count: int|string}|null $row */
        $row = DB::selectOne(
            'SELECT
                c.id,
                c.tenant_id,
                c.name,
                c.description,
                COUNT(cu.user_id) AS users_count
             FROM courses c
             LEFT JOIN course_user cu
                ON cu.course_id = c.id
               AND cu.tenant_id = c.tenant_id
             WHERE c.tenant_id = ?
               AND c.id = ?
             GROUP BY c.id, c.tenant_id, c.name, c.description',
            [$tenantId, $courseId],
        );

        if ($row === null) {
            throw $this->courseNotFound($courseId);
        }

        return $this->mapCourseRow($row);
    }

    public function isUserEnrolled(int $tenantId, int $userId, int $courseId): bool
    {
        $row = DB::selectOne(
            'SELECT 1 AS is_enrolled
             FROM course_user
             WHERE tenant_id = ?
               AND user_id = ?
               AND course_id = ?
             LIMIT 1',
            [$tenantId, $userId, $courseId],
        );

        return $row !== null;
    }

    /**
     * @return LengthAwarePaginator<int, CourseData>
     */
    public function paginateAdminCourses(int $tenantId, int $perPage = 10, ?int $page = null): LengthAwarePaginator
    {
        return $this->paginateCourses($tenantId, $perPage, $page);
    }

    public function createCourse(int $tenantId, string $name, string $description): int
    {
        DB::insert(
            'INSERT INTO courses (tenant_id, name, description, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)',
            [$tenantId, $name, $description, now(), now()],
        );

        return (int) DB::getPdo()->lastInsertId();
    }

    /**
     * @return CourseData
     */
    public function findAdminCourse(int $tenantId, int $courseId): CourseData
    {
        return $this->findCourse($tenantId, $courseId);
    }

    public function updateCourse(int $tenantId, int $courseId, string $name, string $description): void
    {
        $affected = DB::update(
            'UPDATE courses
             SET name = ?, description = ?, updated_at = ?
             WHERE tenant_id = ?
               AND id = ?',
            [$name, $description, now(), $tenantId, $courseId],
        );

        if ($affected === 0) {
            throw $this->courseNotFound($courseId);
        }
    }

    public function deleteCourse(int $tenantId, int $courseId): void
    {
        $affected = DB::delete(
            'DELETE FROM courses
             WHERE tenant_id = ?
               AND id = ?',
            [$tenantId, $courseId],
        );

        if ($affected === 0) {
            throw $this->courseNotFound($courseId);
        }
    }

    /**
     * @return LengthAwarePaginator<int, CourseData>
     */
    private function paginateCourses(int $tenantId, int $perPage, ?int $page): LengthAwarePaginator
    {
        $resolvedPage = max($page ?? Paginator::resolveCurrentPage('page'), 1);
        $offset = ($resolvedPage - 1) * $perPage;

        $countRow = DB::selectOne(
            'SELECT COUNT(*) AS aggregate
             FROM courses
             WHERE tenant_id = ?',
            [$tenantId],
        );

        /** @var object{aggregate: int|string}|null $countRow */
        $total = $countRow !== null ? (int) $countRow->aggregate : 0;

        /** @var list<object{id: int, tenant_id: int, name: string, description: string, users_count: int|string}> $rows */
        $rows = DB::select(
            'SELECT
                c.id,
                c.tenant_id,
                c.name,
                c.description,
                COUNT(cu.user_id) AS users_count
             FROM courses c
             LEFT JOIN course_user cu
                ON cu.course_id = c.id
               AND cu.tenant_id = c.tenant_id
             WHERE c.tenant_id = ?
             GROUP BY c.id, c.tenant_id, c.name, c.description
             ORDER BY c.id ASC
             LIMIT ? OFFSET ?',
            [$tenantId, $perPage, $offset],
        );

        $items = array_map(fn (object $row): CourseData => $this->mapCourseRow($row), $rows);
        $request = request();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $resolvedPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => $request !== null ? $request->query() : [],
            ],
        );
    }

    /**
     * @param  object{id: int, tenant_id: int, name: string, description: string, users_count: int|string}  $row
     * @return CourseData
     */
    private function mapCourseRow(object $row): CourseData
    {
        return new CourseData(
            id: (int) $row->id,
            tenant_id: (int) $row->tenant_id,
            name: (string) $row->name,
            description: (string) $row->description,
            users_count: (int) $row->users_count,
        );
    }

    private function courseNotFound(int $courseId): NotFoundHttpException
    {
        return new NotFoundHttpException("Course {$courseId} was not found.");
    }
}

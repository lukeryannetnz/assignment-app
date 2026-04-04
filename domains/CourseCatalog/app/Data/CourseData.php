<?php

declare(strict_types=1);

namespace App\Domains\CourseCatalog\Data;

final class CourseData
{
    public function __construct(
        public readonly int $id,
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $description,
        public readonly int $users_count,
    ) {
    }
}

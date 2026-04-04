<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Data;

final readonly class CurriculumCourseData
{
    public function __construct(
        public int $id,
        public int $tenant_id,
        public string $name,
    ) {
    }
}

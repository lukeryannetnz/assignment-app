<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Data;

use Illuminate\Support\Collection;

final readonly class CurriculumSectionData
{
    /**
     * @param Collection<int, CurriculumItemData> $curriculumItems
     */
    public function __construct(
        public int $id,
        public int $tenant_id,
        public int $course_id,
        public string $title,
        public int $order,
        public CurriculumCourseData $course,
        public Collection $curriculumItems,
    ) {
    }
}

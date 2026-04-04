<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Data;

enum CurriculumItemType: string
{
    case Video = 'video';
    case Assignment = 'assignment';
    case Quiz = 'quiz';
}

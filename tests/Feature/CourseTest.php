<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function testTheCoursesPageReturnsASuccessfulResult(): void
    {
        $this->seed();
        $response = $this->get('/courses');

        $response->assertOk();
        $response->assertViewHas('courses');
        $response->assertSee('Learn PHP');
    }

    public function testTheCoursesPageWithEmptyData(): void
    {
        $response = $this->get('/courses');

        $response->assertOk();
        $response->assertSee('No courses');
    }
}

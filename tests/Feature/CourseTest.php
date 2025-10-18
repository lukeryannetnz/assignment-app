<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;
    public function test_the_courses_page_returns_a_successful_result(): void
    {
        $this->seed();
        $response = $this->get('/courses');

        $response->assertOk();
        $response->assertViewHas('courses');
        $response->assertSee("Learn PHP");
    }

    public function test_the_courses_page_empty_data(): void
    {
        $response = $this->get('/courses');

        $response->assertOk();
        $response->assertSee("No courses");
    }
}

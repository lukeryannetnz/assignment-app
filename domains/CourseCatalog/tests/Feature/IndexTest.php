<?php

declare(strict_types=1);

namespace Tests\Domains\CourseCatalog\Feature;

use Tests\Domains\Foundation\TestCase;

class IndexTest extends TestCase
{
    public function testTheApplicationReturnsASuccessfulResponse(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

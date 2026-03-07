<?php

declare(strict_types=1);

namespace Tests\Domain\CourseCatalog\Feature;

use Tests\Foundation\TestCase;

class IndexTest extends TestCase
{
    public function testTheApplicationReturnsASuccessfulResponse(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}

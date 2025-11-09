<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class GraduationCapLogoComponentTest extends TestCase
{
    public function testComponentRendersWithDefaultClass(): void
    {
        $content = Blade::render('<x-graduation-cap-logo />');

        // Should have the default fill-current class
        $this->assertStringContainsString('fill-current', $content);
        // Should be an SVG
        $this->assertStringContainsString('<svg', $content);
        $this->assertStringContainsString('viewBox="0 0 120 120"', $content);
    }

    public function testComponentRendersWithWhiteTextColor(): void
    {
        $content = Blade::render('<x-graduation-cap-logo class="text-white" />');

        // Should merge both fill-current (default) and text-white (passed)
        $this->assertStringContainsString('<svg class="fill-current text-white', $content);
    }

    public function testComponentSvgUsesCurrentColor(): void
    {
        $content = Blade::render('<x-graduation-cap-logo />');

        // SVG paths should use currentColor to inherit from text color
        $this->assertStringContainsString('fill="currentColor"', $content);
        $this->assertStringContainsString('stroke="currentColor"', $content);
    }
}

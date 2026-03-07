<?php

declare(strict_types=1);

namespace Tests\Foundation\Architecture;

use PHPUnit\Framework\TestCase;

class DomainStructureGuardTest extends TestCase
{
    public function testAppPhpFilesOnlyExistInsideDomainOrFoundation(): void
    {
        $appPath = realpath(__DIR__ . '/../../../app');
        self::assertNotFalse($appPath);

        $files = $this->findPhpFiles($appPath);

        $violations = array_values(array_filter(
            $files,
            static fn (string $path): bool => !str_starts_with(
                $path,
                $appPath . DIRECTORY_SEPARATOR . 'Domain' . DIRECTORY_SEPARATOR,
            )
                && !str_starts_with($path, $appPath . DIRECTORY_SEPARATOR . 'Foundation' . DIRECTORY_SEPARATOR),
        ));

        self::assertSame(
            [],
            $violations,
            "PHP files found outside app/Domain or app/Foundation:\n" . implode("\n", $violations),
        );
    }

    /**
     * @return list<string>
     */
    private function findPhpFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getRealPath();

            if ($path === false) {
                continue;
            }

            $files[] = $path;
        }

        sort($files);

        return $files;
    }
}

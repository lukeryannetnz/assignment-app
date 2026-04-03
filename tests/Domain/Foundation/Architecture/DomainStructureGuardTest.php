<?php

declare(strict_types=1);

namespace Tests\Domain\Foundation\Architecture;

use PHPUnit\Framework\TestCase;

class DomainStructureGuardTest extends TestCase
{
    public function testAppPhpFilesOnlyExistInsideDomainOrFoundation(): void
    {
        $appPath = realpath(__DIR__ . '/../../../../app');
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

    public function testTestPhpFilesOnlyExistInsideTestsDomain(): void
    {
        $testsPath = realpath(__DIR__ . '/../../../../tests');
        self::assertNotFalse($testsPath);

        $files = $this->findPhpFiles($testsPath);

        $violations = array_values(array_filter(
            $files,
            static fn (string $path): bool => !str_starts_with(
                $path,
                $testsPath . DIRECTORY_SEPARATOR . 'Domain' . DIRECTORY_SEPARATOR,
            ),
        ));

        self::assertSame(
            [],
            $violations,
            "PHP test files found outside tests/Domain:\n" . implode("\n", $violations),
        );
    }

    public function testResourceFilesOnlyExistInsideResourcesDomains(): void
    {
        $resourcesPath = realpath(__DIR__ . '/../../../../resources');
        self::assertNotFalse($resourcesPath);

        $files = $this->findFiles($resourcesPath);

        $violations = array_values(array_filter(
            $files,
            static fn (string $path): bool => !str_starts_with(
                $path,
                $resourcesPath . DIRECTORY_SEPARATOR . 'domains' . DIRECTORY_SEPARATOR,
            ),
        ));

        self::assertSame(
            [],
            $violations,
            "Resource files found outside resources/domains:\n" . implode("\n", $violations),
        );
    }

    public function testDatabaseSourceFilesOnlyExistInsideDomainFolders(): void
    {
        $databasePath = realpath(__DIR__ . '/../../../../database');
        self::assertNotFalse($databasePath);

        $files = array_merge(
            $this->findPhpFiles($databasePath . DIRECTORY_SEPARATOR . 'factories'),
            $this->findPhpFiles($databasePath . DIRECTORY_SEPARATOR . 'migrations'),
            $this->findPhpFiles($databasePath . DIRECTORY_SEPARATOR . 'seeders'),
        );

        $allowedSeederEntrypoint = $databasePath
            . DIRECTORY_SEPARATOR
            . 'seeders'
            . DIRECTORY_SEPARATOR
            . 'DatabaseSeeder.php';

        $violations = array_values(array_filter(
            $files,
            static fn (string $path): bool => !preg_match(
                '#^'
                . preg_quote($databasePath, '#')
                . preg_quote(DIRECTORY_SEPARATOR, '#')
                . '(factories|migrations|seeders)'
                . preg_quote(DIRECTORY_SEPARATOR, '#')
                . '[^' . preg_quote(DIRECTORY_SEPARATOR, '#') . ']+'
                . preg_quote(DIRECTORY_SEPARATOR, '#')
                . '#',
                $path,
            ) && $path !== $allowedSeederEntrypoint,
        ));

        self::assertSame(
            [],
            $violations,
            "Database source files found outside domain folders:\n" . implode("\n", $violations),
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

    /**
     * @return list<string>
     */
    private function findFiles(string $directory): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
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

<?php

declare(strict_types=1);

namespace Tests\Domains\Foundation\Architecture;

use PHPUnit\Framework\TestCase;

class DomainStructureGuardTest extends TestCase
{
    public function testDomainOwnedPhpFilesLiveUnderDomainsDirectory(): void
    {
        $domainsPath = realpath(__DIR__ . '/../../../../domains');
        self::assertNotFalse($domainsPath);

        $files = $this->findPhpFiles($domainsPath);
        $files = array_values(array_filter(
            $files,
            static fn (string $path): bool => !str_ends_with($path, '.blade.php'),
        ));

        $violations = array_values(array_filter(
            $files,
            static fn (string $path): bool => !preg_match(
                '#^'
                . preg_quote($domainsPath, '#')
                . preg_quote(DIRECTORY_SEPARATOR, '#')
                . '[^' . preg_quote(DIRECTORY_SEPARATOR, '#') . ']+'
                . preg_quote(DIRECTORY_SEPARATOR, '#')
                . '(app|database|tests)'
                . preg_quote(DIRECTORY_SEPARATOR, '#')
                . '#',
                $path,
            ),
        ));

        self::assertSame(
            [],
            $violations,
            "Domain-owned PHP files found outside domains/<Domain>/(app|database|tests):\n"
            . implode("\n", $violations),
        );
    }

    public function testDomainOwnedResourceFilesLiveUnderDomainsDirectory(): void
    {
        $domainsPath = realpath(__DIR__ . '/../../../../domains');
        self::assertNotFalse($domainsPath);

        $files = $this->findFiles($domainsPath);
        $resourceFiles = array_values(array_filter(
            $files,
            static fn (string $path): bool => str_contains(
                $path,
                DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR,
            ),
        ));

        $violations = array_values(array_filter(
            $resourceFiles,
            static fn (string $path): bool => !preg_match(
                '#^'
                . preg_quote($domainsPath, '#')
                . preg_quote(DIRECTORY_SEPARATOR, '#')
                . '[^' . preg_quote(DIRECTORY_SEPARATOR, '#') . ']+'
                . preg_quote(DIRECTORY_SEPARATOR, '#')
                . 'resources'
                . preg_quote(DIRECTORY_SEPARATOR, '#')
                . '#',
                $path,
            ),
        ));

        self::assertSame(
            [],
            $violations,
            "Domain-owned resource files found outside domains/<Domain>/resources:\n"
            . implode("\n", $violations),
        );
    }

    public function testLegacyLaravelRootsDoNotContainDomainFiles(): void
    {
        $legacyDirectories = [
            realpath(__DIR__ . '/../../../../app'),
            realpath(__DIR__ . '/../../../../tests'),
            realpath(__DIR__ . '/../../../../resources'),
        ];

        $violations = [];

        foreach ($legacyDirectories as $directory) {
            if ($directory === false) {
                continue;
            }

            $violations = array_merge($violations, $this->findPhpFiles($directory));
            $violations = array_merge($violations, $this->findBladeFiles($directory));
        }

        sort($violations);

        self::assertSame(
            [],
            $violations,
            "Legacy Laravel roots still contain domain-owned files:\n" . implode("\n", $violations),
        );
    }

    public function testRootFrameworkEntrypointsRemainThinAndExplicit(): void
    {
        $rootEntrypoints = [
            realpath(__DIR__ . '/../../../../bootstrap/app.php'),
            realpath(__DIR__ . '/../../../../bootstrap/providers.php'),
            realpath(__DIR__ . '/../../../../routes/web.php'),
            realpath(__DIR__ . '/../../../../routes/console.php'),
            realpath(__DIR__ . '/../../../../database/seeders/DatabaseSeeder.php'),
        ];

        foreach ($rootEntrypoints as $path) {
            self::assertNotFalse($path);
        }
    }

    /**
     * @return list<string>
     */
    private function findPhpFiles(string $directory): array
    {
        return $this->findFilesByExtension($directory, 'php');
    }

    /**
     * @return list<string>
     */
    private function findBladeFiles(string $directory): array
    {
        $files = $this->findFiles($directory);

        return array_values(array_filter(
            $files,
            static fn (string $path): bool => str_ends_with($path, '.blade.php'),
        ));
    }

    /**
     * @return list<string>
     */
    private function findFilesByExtension(string $directory, string $extension): array
    {
        $files = $this->findFiles($directory);

        return array_values(array_filter(
            $files,
            static fn (string $path): bool => pathinfo($path, PATHINFO_EXTENSION) === $extension,
        ));
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

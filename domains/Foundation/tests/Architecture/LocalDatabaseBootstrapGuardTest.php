<?php

declare(strict_types=1);

namespace Tests\Domains\Foundation\Architecture;

use PHPUnit\Framework\TestCase;

class LocalDatabaseBootstrapGuardTest extends TestCase
{
    public function testEnvExampleMatchesDockerComposeMariaDbCredentials(): void
    {
        self::assertSame(
            $this->dockerComposeDatabaseSettings(),
            $this->envExampleDatabaseSettings(),
        );
    }

    public function testMariaDbConfigFallbacksMatchDockerComposeMariaDbCredentials(): void
    {
        self::assertSame(
            $this->dockerComposeDatabaseSettings(),
            $this->configMariaDbFallbacks(),
        );
    }

    /**
     * @return array{DB_DATABASE: string, DB_USERNAME: string, DB_PASSWORD: string}
     */
    private function dockerComposeDatabaseSettings(): array
    {
        $contents = $this->readFile('/../../../../docker-compose.yml');

        return [
            'DB_DATABASE' => $this->matchValue(
                $contents,
                '/^\s*MYSQL_DATABASE:\s*([^\s#]+)\s*$/m',
                'docker-compose.yml',
            ),
            'DB_USERNAME' => $this->matchValue(
                $contents,
                '/^\s*MYSQL_USER:\s*([^\s#]+)\s*$/m',
                'docker-compose.yml',
            ),
            'DB_PASSWORD' => $this->matchValue(
                $contents,
                '/^\s*MYSQL_PASSWORD:\s*([^\s#]+)\s*$/m',
                'docker-compose.yml',
            ),
        ];
    }

    /**
     * @return array{DB_DATABASE: string, DB_USERNAME: string, DB_PASSWORD: string}
     */
    private function envExampleDatabaseSettings(): array
    {
        $contents = $this->readFile('/../../../../.env.example');

        return [
            'DB_DATABASE' => $this->matchValue($contents, '/^DB_DATABASE=(.+)$/m', '.env.example'),
            'DB_USERNAME' => $this->matchValue($contents, '/^DB_USERNAME=(.+)$/m', '.env.example'),
            'DB_PASSWORD' => $this->matchValue($contents, '/^DB_PASSWORD=(.*)$/m', '.env.example'),
        ];
    }

    /**
     * @return array{DB_DATABASE: string, DB_USERNAME: string, DB_PASSWORD: string}
     */
    private function configMariaDbFallbacks(): array
    {
        $contents = $this->readFile('/../../../../config/database.php');
        $mariadbConfig = $this->matchValue(
            $contents,
            "/'mariadb'\s*=>\s*\[(.*?)\n\s*\],/s",
            'config/database.php',
        );

        return [
            'DB_DATABASE' => $this->matchValue(
                $mariadbConfig,
                "/'database'\s*=>\s*env\('DB_DATABASE',\s*'([^']+)'\)/",
                'config/database.php mariadb connection',
            ),
            'DB_USERNAME' => $this->matchValue(
                $mariadbConfig,
                "/'username'\s*=>\s*env\('DB_USERNAME',\s*'([^']+)'\)/",
                'config/database.php mariadb connection',
            ),
            'DB_PASSWORD' => $this->matchValue(
                $mariadbConfig,
                "/'password'\s*=>\s*env\('DB_PASSWORD',\s*'([^']*)'\)/",
                'config/database.php mariadb connection',
            ),
        ];
    }

    private function readFile(string $relativePath): string
    {
        $path = __DIR__ . $relativePath;
        $contents = file_get_contents($path);

        self::assertNotFalse($contents, "Failed to read {$path}.");

        return $contents;
    }

    private function matchValue(string $contents, string $pattern, string $source): string
    {
        $result = preg_match($pattern, $contents, $matches);

        self::assertSame(1, $result, "Failed to match {$pattern} in {$source}.");

        return trim($matches[1]);
    }
}

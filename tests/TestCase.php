<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /** Databases the suite may destroy. */
    private const array TEST_DATABASES = ['testing', ':memory:'];

    /** Seed roles/permissions once per migration, then roll back per test. */
    protected bool $seed = true;

    protected string $seeder = RolesAndPermissionsSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Behave like the SPA: Sanctum only starts a session for stateful (first-party) origins.
        $this->withHeader('Origin', config()->string('app.url'));
    }

    /**
     * Runs after the app boots but before RefreshDatabase — the only point where the check can
     * still prevent the migration from touching the wrong database.
     */
    protected function setUpTraits(): array
    {
        $this->guardAgainstNonTestDatabase();

        return parent::setUpTraits();
    }

    /**
     * A cached config (bootstrap/cache/config.php) wins over phpunit.xml's environment, which
     * would point the suite — and RefreshDatabase — at the development database and wipe it.
     * Fail loudly instead.
     */
    private function guardAgainstNonTestDatabase(): void
    {
        $connection = config()->string('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (! in_array($database, self::TEST_DATABASES, true)) {
            throw new RuntimeException(sprintf(
                'Refusing to run tests against the "%s" database. Expected one of: %s. '
                .'This usually means a cached config is in use — run `php artisan config:clear`.',
                $database,
                implode(', ', self::TEST_DATABASES),
            ));
        }
    }
}

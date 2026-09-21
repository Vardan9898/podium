<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Seed roles/permissions once per migration, then roll back per test. */
    protected bool $seed = true;

    protected string $seeder = RolesAndPermissionsSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Behave like the SPA: Sanctum only starts a session for stateful (first-party) origins.
        $this->withHeader('Origin', config()->string('app.url'));
    }
}

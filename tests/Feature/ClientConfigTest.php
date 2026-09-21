<?php

declare(strict_types=1);

it('exposes the settings the SPA needs, straight from config', function (): void {
    config(['proposals.rating.max' => 5, 'auth.allow_admin_registration' => false]);

    $this->getJson('/api/config')
        ->assertOk()
        ->assertExactJson(['data' => [
            'allow_admin_registration' => false,
            'rating' => ['min' => 1, 'max' => 5],
            'attachment_max_kilobytes' => 4096,
            'tags_max_per_proposal' => 10,
        ]]);
});

<?php

declare(strict_types=1);

it('exposes the settings the SPA needs, straight from config', function (): void {
    config(['proposals.rating.max' => 5, 'auth.self_registration_roles' => ['speaker']]);

    $this->getJson('/api/config')
        ->assertOk()
        ->assertExactJson(['data' => [
            'registerable_roles' => ['speaker'],
            'rating' => ['min' => 1, 'max' => 5],
            'attachment_max_kilobytes' => 4096,
            'tags_max_per_proposal' => 10,
        ]]);
});

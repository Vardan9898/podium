<?php

declare(strict_types=1);

return [

    'rating' => [
        'min' => 1,
        'max' => 10,
    ],

    'attachment' => [
        'disk' => 'local',
        'max_kilobytes' => 4096,
    ],

    'tags' => [
        'max_per_proposal' => 10,
        'autocomplete_limit' => 20,
    ],

    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 50,
        'notifications_per_page' => 20,
    ],

];

<?php

declare(strict_types=1);

namespace App\Data;

final readonly class NotificationSelection
{
    /**
     * @param  list<string>|null  $ids  Null selects every unread notification.
     */
    public function __construct(
        public ?array $ids,
    ) {}
}

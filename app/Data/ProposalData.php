<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Http\UploadedFile;

final readonly class ProposalData
{
    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        public string $title,
        public string $description,
        public array $tags = [],
        public ?UploadedFile $attachment = null,
    ) {}
}

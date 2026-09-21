<?php

declare(strict_types=1);

namespace App\Http\Controllers\Proposals;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProposalAttachmentController extends Controller
{
    /**
     * Download a proposal's PDF attachment.
     *
     * Files live on a private disk; this endpoint is the only way to read them.
     */
    public function __invoke(Proposal $proposal): StreamedResponse
    {
        $disk = Storage::disk(config()->string('proposals.attachment.disk'));
        $path = (string) $proposal->attachment_path;

        abort_unless($proposal->hasAttachment() && $disk->exists($path), 404);

        return $disk->download($path, $proposal->attachment_original_name);
    }
}

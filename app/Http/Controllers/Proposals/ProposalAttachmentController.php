<?php

declare(strict_types=1);

namespace App\Http\Controllers\Proposals;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        // Guard older rows: an ASCII-empty name would make Content-Disposition throw.
        $name = (string) $proposal->attachment_original_name;

        return $disk->download($path, Str::ascii($name) === '' ? "proposal-{$proposal->id}.pdf" : $name);
    }
}

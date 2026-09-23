<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Actions\Tags\SyncProposalTags;
use App\Data\ProposalData;
use App\Events\ProposalSubmitted;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class SubmitProposal
{
    public function __construct(
        private readonly SyncProposalTags $syncTags,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(User $author, ProposalData $data): Proposal
    {
        $disk = Storage::disk(config()->string('proposals.attachment.disk'));

        return DB::transaction(function () use ($author, $data, $disk): Proposal {
            $proposal = $author->proposals()->create([
                'title' => $data->title,
                'description' => $data->description,
            ]);

            $this->addDetails($proposal, $data, $disk);

            // Listeners are queued and run after commit, so a notification outage can never undo this write.
            ProposalSubmitted::dispatch($proposal, $author);

            return $proposal;
        });
    }

    /**
     * Keeps the client-supplied name usable as a download filename: bounded in length and
     * never empty once reduced to ASCII (Content-Disposition needs an ASCII fallback).
     */
    private static function safeFileName(UploadedFile $file): string
    {
        $name = Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 200, '');

        return Str::ascii($name) === '' ? 'proposal.pdf' : "{$name}.pdf";
    }

    /**
     * Attachment and tags, written inside the transaction. If either step throws, the row is
     * rolled back and any stored file removed, so neither is ever left without the other.
     */
    private function addDetails(Proposal $proposal, ProposalData $data, Filesystem $disk): void
    {
        $storedPath = null;

        try {
            if ($data->attachment !== null) {
                $storedPath = $disk->putFile("proposals/{$proposal->id}", $data->attachment)
                    ?: throw new RuntimeException('The attachment could not be stored.');

                $proposal->update([
                    'attachment_path' => $storedPath,
                    'attachment_original_name' => self::safeFileName($data->attachment),
                ]);
            }

            $this->syncTags->handle($proposal, $data->tags);
        } catch (Throwable $e) {
            if (is_string($storedPath)) {
                $disk->delete($storedPath);
            }

            throw $e;
        }
    }
}

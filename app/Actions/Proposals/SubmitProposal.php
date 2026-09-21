<?php

declare(strict_types=1);

namespace App\Actions\Proposals;

use App\Actions\Tags\SyncProposalTags;
use App\Data\ProposalData;
use App\Events\ProposalSubmitted;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $storedPath = null;

        try {
            return DB::transaction(function () use ($author, $data, $disk, &$storedPath): Proposal {
                $proposal = $author->proposals()->create([
                    'title' => $data->title,
                    'description' => $data->description,
                ]);

                if ($data->attachment !== null) {
                    $storedPath = $disk->putFile("proposals/{$proposal->id}", $data->attachment)
                        ?: throw new RuntimeException('The attachment could not be stored.');

                    $proposal->update([
                        'attachment_path' => $storedPath,
                        'attachment_original_name' => $data->attachment->getClientOriginalName(),
                    ]);
                }

                $this->syncTags->handle($proposal, $data->tags);

                ProposalSubmitted::dispatch($proposal, $author);

                return $proposal;
            });
        } catch (Throwable $e) {
            if (is_string($storedPath)) {
                $disk->delete($storedPath);
            }

            throw $e;
        }
    }
}

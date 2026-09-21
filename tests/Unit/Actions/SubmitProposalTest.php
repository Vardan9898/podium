<?php

declare(strict_types=1);

use App\Actions\Proposals\SubmitProposal;
use App\Data\ProposalData;
use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Events\ProposalSubmitted;
use App\Models\Proposal;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

it('creates a pending proposal with tags and attachment and dispatches an event', function (): void {
    Event::fake([ProposalSubmitted::class]);
    $author = userWithRole(Role::Speaker);

    $proposal = app(SubmitProposal::class)->handle($author, new ProposalData(
        title: 'Talk',
        description: 'Body',
        tags: ['PHP'],
        attachment: UploadedFile::fake()->create('deck.pdf', 10, 'application/pdf'),
    ));

    expect($proposal->status)->toBe(ProposalStatus::Pending)
        ->and($proposal->author()->is($author))->toBeTrue()
        ->and($proposal->tags()->pluck('name')->all())->toBe(['PHP']);
    Storage::disk('local')->assertExists((string) $proposal->attachment_path);
    Event::assertDispatched(ProposalSubmitted::class);
});

it('rolls back and deletes the stored file when a later step fails', function (): void {
    Event::fake([ProposalSubmitted::class]);

    // A 31+ character tag overflows the column after the file has already been written.
    expect(fn () => app(SubmitProposal::class)->handle(userWithRole(Role::Speaker), new ProposalData(
        title: 'Talk',
        description: 'Body',
        tags: [str_repeat('x', 40)],
        attachment: UploadedFile::fake()->create('deck.pdf', 10, 'application/pdf'),
    )))->toThrow(QueryException::class);

    expect(Proposal::query()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
    Event::assertNotDispatched(ProposalSubmitted::class);
});

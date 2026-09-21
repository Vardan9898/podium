<?php

declare(strict_types=1);

use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Events\ProposalSubmitted;
use App\Models\Proposal;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

function realUpload(string $contents, string $clientName): UploadedFile
{
    $path = (string) tempnam(sys_get_temp_dir(), 'upload');
    file_put_contents($path, $contents);

    return new UploadedFile($path, $clientName, test: true);
}

beforeEach(function (): void {
    Storage::fake('local');
    $this->speaker = userWithRole(Role::Speaker);
});

it('submits a proposal as pending without tags or file', function (): void {
    Event::fake([ProposalSubmitted::class]);

    $this->actingAs($this->speaker)
        ->postJson('/api/proposals', ['title' => '  Scaling Laravel  ', 'description' => 'How we did it.'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Scaling Laravel')
        ->assertJsonPath('data.status', ProposalStatus::Pending->value)
        ->assertJsonPath('data.author.id', $this->speaker->id)
        ->assertJsonPath('data.tags', [])
        ->assertJsonPath('data.attachment', null);

    Event::assertDispatched(ProposalSubmitted::class, fn (ProposalSubmitted $e) => $e->actor->is($this->speaker));
});

it('creates new tags and reuses existing ones case-insensitively', function (): void {
    $existing = Tag::factory()->named('Laravel')->create();

    $response = $this->actingAs($this->speaker)->postJson('/api/proposals', [
        'title' => 'Tags',
        'description' => 'Body',
        'tags' => ['laravel', 'Vue  3'],
    ])->assertCreated();

    expect(Tag::query()->count())->toBe(2)
        ->and($response->json('data.tags.*.name'))->toEqualCanonicalizing(['Laravel', 'Vue 3'])
        ->and(Proposal::query()->sole()->tags->pluck('id'))->toContain($existing->id);
});

it('stores a real PDF attachment on the private disk', function (): void {
    $response = $this->actingAs($this->speaker)->post('/api/proposals', [
        'title' => 'With slides',
        'description' => 'Body',
        'attachment' => realUpload((string) file_get_contents(database_path('seeders/files/sample-proposal.pdf')), 'slides.pdf'),
    ], ['Accept' => 'application/json'])->assertCreated();

    $proposal = Proposal::query()->sole();

    expect($proposal->attachment_original_name)->toBe('slides.pdf')
        ->and($proposal->attachment_path)->toStartWith("proposals/{$proposal->id}/");
    Storage::disk('local')->assertExists((string) $proposal->attachment_path);
    $response->assertJsonPath('data.attachment.url', "/api/proposals/{$proposal->id}/attachment");
});

it('rejects invalid submissions', function (array $payload, string $field): void {
    $this->actingAs($this->speaker)
        ->post('/api/proposals', ['title' => 'T', 'description' => 'D', ...$payload], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect(Proposal::query()->count())->toBe(0);
    Storage::disk('local')->assertDirectoryEmpty('/');
})->with([
    'missing title' => fn () => [['title' => ''], 'title'],
    'missing description' => fn () => [['description' => ''], 'description'],
    'title too long' => fn () => [['title' => str_repeat('a', 256)], 'title'],
    'non-PDF file' => fn () => [['attachment' => UploadedFile::fake()->create('notes.docx', 10, 'application/msword')], 'attachment'],
    // Fake uploads report a MIME type from the extension; a real file proves content sniffing.
    'image renamed to .pdf' => fn () => [['attachment' => realUpload(UploadedFile::fake()->image('x.png')->getContent(), 'slides.pdf')], 'attachment'],
    'file over 4 MB' => fn () => [['attachment' => UploadedFile::fake()->create('big.pdf', 4097, 'application/pdf')], 'attachment'],
    'too many tags' => fn () => [['tags' => array_map(fn ($i) => "tag {$i}", range(1, 11))], 'tags'],
    'duplicate tags ignoring case' => fn () => [['tags' => ['PHP', 'php']], 'tags.1'],
    'tag without letters' => fn () => [['tags' => ['!!']], 'tags.0'],
]);

it('forbids users without the create permission from submitting', function (Role $role): void {
    $this->actingAs(userWithRole($role))
        ->postJson('/api/proposals', ['title' => 'T', 'description' => 'D'])
        ->assertForbidden();
})->with([Role::Reviewer, Role::Admin]);

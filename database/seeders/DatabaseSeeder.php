<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

final class DatabaseSeeder extends Seeder
{
    private const array TAGS = [
        'Laravel', 'Vue.js', 'PHP', 'DevOps', 'Security',
        'Testing', 'Architecture', 'Performance', 'Accessibility', 'Career',
    ];

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $speaker = $this->demoUser('Sam Speaker', 'speaker@example.com', Role::Speaker);
        $reviewer = $this->demoUser('Riley Reviewer', 'reviewer@example.com', Role::Reviewer);
        $this->demoUser('Alex Admin', 'admin@example.com', Role::Admin);

        $speakers = User::factory()->count(3)->speaker()->create()->prepend($speaker);
        $reviewers = User::factory()->count(2)->reviewer()->create()->prepend($reviewer);

        $tags = collect(self::TAGS)->map(fn (string $name): Tag => Tag::query()->firstOrCreate(
            ['slug' => Tag::slugFor($name)],
            ['name' => $name],
        ));

        $statuses = ProposalStatus::cases();

        foreach (range(1, 25) as $i) {
            $proposal = Proposal::factory()
                ->for($speakers->random(), 'author')
                ->status($statuses[$i % count($statuses)])
                ->withTags($tags->random(random_int(1, 3)))
                ->create(['created_at' => now()->subHours($i * 5)]);

            foreach ($reviewers->random(random_int(0, $reviewers->count())) as $author) {
                Review::factory()->for($proposal)->for($author, 'author')->create();
            }
        }

        $this->attachSamplePdf($speaker);
    }

    private function demoUser(string $name, string $email, Role $role): User
    {
        return User::factory()->withRole($role)->create(['name' => $name, 'email' => $email]);
    }

    private function attachSamplePdf(User $speaker): void
    {
        $proposal = $speaker->proposals()->latest()->firstOrFail();
        $path = "proposals/{$proposal->id}/sample-proposal.pdf";

        Storage::disk(config()->string('proposals.attachment.disk'))
            ->put($path, (string) file_get_contents(database_path('seeders/files/sample-proposal.pdf')));

        $proposal->update([
            'attachment_path' => $path,
            'attachment_original_name' => 'sample-proposal.pdf',
        ]);
    }
}

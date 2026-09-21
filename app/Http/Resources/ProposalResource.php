<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Proposal */
final class ProposalResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        // Scores are review data: hidden from anyone who may not read reviews (e.g. the author).
        $canSeeReviews = $request->user()?->can('viewReviews', $this->resource) ?? false;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'author' => new UserResource($this->whenLoaded('author')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'attachment' => $this->hasAttachment() ? [
                'name' => $this->attachment_original_name,
                'url' => route('proposals.attachment', $this->resource, absolute: false),
            ] : null,
            'reviews_count' => $this->when($canSeeReviews, fn () => $this->whenCounted('reviews')),
            'average_rating' => $this->when($canSeeReviews, fn () => $this->whenAggregated(
                'reviews',
                'rating',
                'avg',
                fn (mixed $average): ?float => $average === null ? null : round((float) $average, 1),
            )),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

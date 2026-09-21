<?php

declare(strict_types=1);

namespace App\Http\Requests\Proposals;

use App\Data\ProposalFilters;
use App\Enums\ProposalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexProposalsRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array', 'max:'.config()->integer('proposals.tags.max_per_proposal')],
            'tags.*' => ['string', 'max:40'],
            'status' => ['nullable', Rule::enum(ProposalStatus::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config()->integer('proposals.pagination.max_per_page')],
        ];
    }

    public function toData(): ProposalFilters
    {
        /** @var list<string> $tags */
        $tags = array_values($this->array('tags'));

        return new ProposalFilters(
            search: $this->string('search')->value() ?: null,
            tags: $tags,
            status: $this->enum('status', ProposalStatus::class),
            perPage: $this->integer('per_page', config()->integer('proposals.pagination.per_page')),
        );
    }
}

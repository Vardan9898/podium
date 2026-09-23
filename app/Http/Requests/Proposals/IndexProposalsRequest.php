<?php

declare(strict_types=1);

namespace App\Http\Requests\Proposals;

use App\Data\ProposalFilters;
use App\Enums\ProposalStatus;
use App\Models\Tag;
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
            'tags.*' => ['string', 'max:30'],
            'status' => ['nullable', Rule::enum(ProposalStatus::class)],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.config()->integer('proposals.pagination.max_page')],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config()->integer('proposals.pagination.max_per_page')],
        ];
    }

    public function toData(): ProposalFilters
    {
        /** @var list<string> $names */
        $names = $this->array('tags');
        $keys = array_values(array_unique(array_map(Tag::keyFor(...), $names)));

        $search = $this->string('search')->trim()->value();

        return new ProposalFilters(
            search: $search === '' ? null : $search,
            tags: $keys,
            status: $this->enum('status', ProposalStatus::class),
            perPage: $this->integer('per_page', config()->integer('proposals.pagination.per_page')),
        );
    }
}

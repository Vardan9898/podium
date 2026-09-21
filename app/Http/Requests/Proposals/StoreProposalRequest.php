<?php

declare(strict_types=1);

namespace App\Http\Requests\Proposals;

use App\Data\ProposalData;
use App\Models\Proposal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Proposal::class) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'tags' => ['nullable', 'array', 'max:'.config()->integer('proposals.tags.max_per_proposal')],
            'tags.*' => ['string', 'min:2', 'max:30', 'distinct:ignore_case', 'regex:/[\pL\pN]/u'],
            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf',
                'mimetypes:application/pdf',
                'max:'.config()->integer('proposals.attachment.max_kilobytes'),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tags.*.regex' => 'Each tag must contain at least one letter or number.',
            'tags.*.distinct' => 'Tags must be unique.',
        ];
    }

    public function toData(): ProposalData
    {
        /** @var list<string> $tags */
        $tags = array_values($this->array('tags'));

        return new ProposalData(
            title: $this->string('title')->trim()->value(),
            description: $this->string('description')->trim()->value(),
            tags: $tags,
            attachment: $this->file('attachment'),
        );
    }
}

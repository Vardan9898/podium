<?php

declare(strict_types=1);

namespace App\Http\Requests\Proposals;

use App\Data\ReviewData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ReviewProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('proposal')) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $min = config()->integer('proposals.rating.min');
        $max = config()->integer('proposals.rating.max');

        return [
            'rating' => ['required', 'integer', "between:{$min},{$max}"],
            'comment' => ['required', 'string', 'max:2000'],
        ];
    }

    public function toData(): ReviewData
    {
        return new ReviewData(
            rating: $this->integer('rating'),
            comment: $this->string('comment')->trim()->value(),
        );
    }
}

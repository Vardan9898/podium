<?php

declare(strict_types=1);

namespace App\Http\Requests\Proposals;

use App\Enums\ProposalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeProposalStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeStatus', $this->route('proposal')) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ProposalStatus::class)],
        ];
    }

    public function newStatus(): ProposalStatus
    {
        return ProposalStatus::from($this->string('status')->value());
    }
}

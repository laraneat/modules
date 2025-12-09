<?php

namespace Modules\Author\UI\WEB\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Author\DTO\CreateAuthorDTO;
use Modules\Author\Models\Author;

class CreateAuthorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // TODO: add fields here
        ];
    }

    public function authorize(): bool
    {
        return Gate::check('create', Author::class);
    }

    public function toDTO(): CreateAuthorDTO
    {
        return CreateAuthorDTO::from($this->validated());
    }
}

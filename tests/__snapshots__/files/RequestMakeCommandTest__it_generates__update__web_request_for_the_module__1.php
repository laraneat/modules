<?php

namespace Modules\Author\UI\WEB\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Author\DTO\UpdateAuthorDTO;

class UpdateAuthorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // TODO: add fields here
        ];
    }

    public function authorize(): bool
    {
        $author = $this->route('author');
        return $author && Gate::check('update', $author);
    }

    public function toDTO(): UpdateAuthorDTO
    {
        return UpdateAuthorDTO::from($this->validated());
    }
}

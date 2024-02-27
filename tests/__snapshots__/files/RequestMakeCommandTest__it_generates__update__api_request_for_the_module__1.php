<?php

namespace Modules\Author\UI\API\Requests;

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
        return new UpdateAuthorDTO($this->validated());
    }
}

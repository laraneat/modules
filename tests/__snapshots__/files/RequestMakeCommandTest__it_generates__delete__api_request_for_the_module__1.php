<?php

namespace Modules\Author\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteAuthorRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        $author = $this->route('author');
        return $author && Gate::check('delete', $author);
    }
}

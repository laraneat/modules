<?php

namespace Modules\Author\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Author\Models\Author;

class ListAuthorsRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return Gate::check('viewAny', Author::class);
    }
}

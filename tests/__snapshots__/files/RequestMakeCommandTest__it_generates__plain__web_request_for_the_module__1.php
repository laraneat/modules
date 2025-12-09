<?php

namespace Modules\Author\UI\WEB\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlainAuthorRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return true;
    }
}

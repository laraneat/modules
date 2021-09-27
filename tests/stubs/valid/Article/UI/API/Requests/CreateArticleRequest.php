<?php

namespace App\Modules\Article\UI\API\Requests;

use App\Modules\Article\Models\Article;
use Illuminate\Foundation\Http\FormRequest;

class CreateArticleRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return $this->user()->can('create', Article::class);
    }
}

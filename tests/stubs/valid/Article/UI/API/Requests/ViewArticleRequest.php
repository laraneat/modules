<?php

namespace App\Modules\Article\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ViewArticleRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        $article = $this->route('article');
        return $article && $this->user()->can('view', $article);
    }
}

<?php

namespace Modules\ArticleComment\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\ArticleComment\Models\ArticleComment;

class ListArticleCommentsRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return Gate::check('viewAny', ArticleComment::class);
    }
}

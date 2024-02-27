<?php

namespace Modules\ArticleComment\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ViewArticleCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        $articleComment = $this->route('articleComment');
        return $articleComment && Gate::check('view', $articleComment);
    }
}

<?php

namespace Modules\ArticleComment\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\ArticleComment\DTO\UpdateArticleCommentDTO;

class UpdateArticleCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // TODO: add fields here
        ];
    }

    public function authorize(): bool
    {
        $articleComment = $this->route('articleComment');
        return $articleComment && Gate::check('update', $articleComment);
    }

    public function toDTO(): UpdateArticleCommentDTO
    {
        return new UpdateArticleCommentDTO($this->validated());
    }
}

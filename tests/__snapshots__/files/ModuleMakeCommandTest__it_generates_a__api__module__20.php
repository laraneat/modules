<?php

namespace Modules\ArticleComment\UI\API\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\ArticleComment\DTO\CreateArticleCommentDTO;
use Modules\ArticleComment\Models\ArticleComment;

class CreateArticleCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // TODO: add fields here
        ];
    }

    public function authorize(): bool
    {
        return Gate::check('create', ArticleComment::class);
    }

    public function toDTO(): CreateArticleCommentDTO
    {
        return new CreateArticleCommentDTO($this->validated());
    }
}

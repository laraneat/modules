<?php

namespace Modules\ArticleComment\Actions;

use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ArticleComment\Models\ArticleComment;
use Modules\ArticleComment\UI\API\Requests\DeleteArticleCommentRequest;

class DeleteArticleCommentAction
{
    use AsAction;

    public function handle(ArticleComment $articleComment): bool
    {
        return $articleComment->delete();
    }

    public function asController(DeleteArticleCommentRequest $request, ArticleComment $articleComment): JsonResponse
    {
        $this->handle($articleComment);

        return $this->noContent();
    }
}

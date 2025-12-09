<?php

namespace Modules\ArticleComment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ArticleComment\Models\ArticleComment;
use Modules\ArticleComment\UI\API\Requests\DeleteArticleCommentRequest;
use Symfony\Component\HttpFoundation\Response;

class DeleteArticleCommentAction
{
    use AsAction;

    public function handle(ArticleComment $articleComment): bool
    {
        return $articleComment->delete();
    }

    public function asController(DeleteArticleCommentRequest $request, ArticleComment $articleComment): Response
    {
        $this->handle($articleComment);

        return $this->noContent();
    }
}

<?php

namespace Modules\ArticleComment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ArticleComment\DTO\UpdateArticleCommentDTO;
use Modules\ArticleComment\Models\ArticleComment;
use Modules\ArticleComment\UI\API\Requests\UpdateArticleCommentRequest;
use Modules\ArticleComment\UI\API\Resources\ArticleCommentResource;

class UpdateArticleCommentAction
{
    use AsAction;

    public function handle(ArticleComment $articleComment, UpdateArticleCommentDTO $dto): ArticleComment
    {
        $data = $dto->all();

        if ($data) {
            $articleComment->update($data);
        }

        return $articleComment;
    }

    public function asController(UpdateArticleCommentRequest $request, ArticleComment $articleComment): ArticleCommentResource
    {
        $articleComment = $this->handle($articleComment, $request->toDTO());

        return new ArticleCommentResource($articleComment);
    }
}

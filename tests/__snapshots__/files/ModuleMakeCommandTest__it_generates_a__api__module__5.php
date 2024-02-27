<?php

namespace Modules\ArticleComment\Actions;

use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ArticleComment\DTO\CreateArticleCommentDTO;
use Modules\ArticleComment\Models\ArticleComment;
use Modules\ArticleComment\UI\API\Requests\CreateArticleCommentRequest;
use Modules\ArticleComment\UI\API\Resources\ArticleCommentResource;

class CreateArticleCommentAction
{
    use AsAction;

    public function handle(CreateArticleCommentDTO $dto): ArticleComment
    {
        return ArticleComment::create($dto->all());
    }

    public function asController(CreateArticleCommentRequest $request): JsonResponse
    {
        $articleComment = $this->handle($request->toDTO());

        return (new ArticleCommentResource($articleComment))->created();
    }
}

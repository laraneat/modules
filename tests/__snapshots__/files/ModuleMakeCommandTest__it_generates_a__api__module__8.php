<?php

namespace Modules\ArticleComment\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ArticleComment\Models\ArticleComment;
use Modules\ArticleComment\UI\API\QueryWizards\ArticleCommentsQueryWizard;
use Modules\ArticleComment\UI\API\Requests\ListArticleCommentsRequest;
use Modules\ArticleComment\UI\API\Resources\ArticleCommentResource;

class ListArticleCommentsAction
{
    use AsAction;

    public function handle(ListArticleCommentsRequest $request): LengthAwarePaginator
    {
        return ArticleCommentsQueryWizard::for(ArticleComment::query())
            ->build()
            ->paginate();
    }

    public function asController(ListArticleCommentsRequest $request): ResourceCollection
    {
        return ArticleCommentResource::collection($this->handle($request));
    }
}

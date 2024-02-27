<?php

namespace Modules\ArticleComment\Actions;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ArticleComment\Models\ArticleComment;
use Modules\ArticleComment\UI\API\QueryWizards\ArticleCommentQueryWizard;
use Modules\ArticleComment\UI\API\Requests\ViewArticleCommentRequest;
use Modules\ArticleComment\UI\API\Resources\ArticleCommentResource;

class ViewArticleCommentAction
{
    use AsAction;

    public function handle(ViewArticleCommentRequest $request, ArticleComment $articleComment): Model
    {
        return ArticleCommentQueryWizard::for($articleComment)->build();
    }

    public function asController(ViewArticleCommentRequest $request, ArticleComment $articleComment): ArticleCommentResource
    {
        return new ArticleCommentResource($this->handle($request, $articleComment));
    }
}

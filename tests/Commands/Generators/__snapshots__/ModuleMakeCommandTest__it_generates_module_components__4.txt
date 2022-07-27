<?php

namespace App\Modules\Article\Actions;

use App\Modules\Article\Models\Article;
use App\Modules\Article\UI\API\QueryWizards\ArticleQueryWizard;
use App\Modules\Article\UI\API\Requests\ViewArticleRequest;
use App\Modules\Article\UI\API\Resources\ArticleResource;
use App\Ship\Abstracts\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class ViewArticleAction extends Action
{
    public function handle(ViewArticleRequest $request, Article $article): Model
    {
        return ArticleQueryWizard::for($article)->build();
    }

    public function asController(ViewArticleRequest $request, Article $article): ArticleResource
    {
        return new ArticleResource($this->handle($request, $article));
    }
}

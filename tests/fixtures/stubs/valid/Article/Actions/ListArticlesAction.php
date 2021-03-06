<?php

namespace App\Modules\Article\Actions;

use App\Modules\Article\Models\Article;
use App\Modules\Article\UI\API\QueryWizards\ArticlesQueryWizard;
use App\Modules\Article\UI\API\Requests\ListArticlesRequest;
use App\Modules\Article\UI\API\Resources\ArticleResource;
//use App\Ship\Abstracts\Actions\Action;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class ListArticlesAction
{
    /**
     * @param ListArticlesRequest $request
     *
     * @return AbstractPaginator
     */
    public function handle(ListArticlesRequest $request): AbstractPaginator
    {
        return ArticlesQueryWizard::for(Article::query(), $request)
            ->build()
            ->jsonPaginate();
    }

    /**
     * @param ListArticlesRequest $request
     *
     * @return ResourceCollection
     */
    public function asController(ListArticlesRequest $request): ResourceCollection
    {
        return ArticleResource::collection($this->handle($request));
    }
}

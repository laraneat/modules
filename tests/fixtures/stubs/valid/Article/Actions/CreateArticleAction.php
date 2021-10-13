<?php

namespace App\Modules\Article\Actions;

use App\Modules\Article\Models\Article;
use App\Modules\Article\UI\API\Requests\CreateArticleRequest;
use App\Modules\Article\UI\API\Resources\ArticleResource;
//use App\Ship\Abstracts\Actions\Action;
use Illuminate\Http\JsonResponse;

class CreateArticleAction
{
    /**
     * @return Article
     */
    public function handle(
        //
    ): Article {
        return Article::create([
            //
        ]);
    }

    /**
     * @param CreateArticleRequest $request
     *
     * @return JsonResponse
     */
    public function asController(CreateArticleRequest $request): JsonResponse
    {
        $article = $this->handle(
            //
        );

        return (new ArticleResource($article))->created();
    }
}

<?php

namespace App\Modules\Article\Actions;

use App\Modules\Article\Models\Article;
use App\Modules\Article\UI\API\Requests\DeleteArticleRequest;
//use App\Ship\Abstracts\Actions\Action;
use Illuminate\Http\JsonResponse;

class DeleteArticleAction
{
    /**
     * @param Article $article
     *
     * @return bool
     */
    public function handle(Article $article): bool
    {
        return $article->delete();
    }

    /**
     * @param DeleteArticleRequest $request
     * @param Article $article
     *
     * @return JsonResponse
     */
    public function asController(DeleteArticleRequest $request, Article $article): JsonResponse
    {
        $this->handle($article);

        return $this->noContent();
    }
}

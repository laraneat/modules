<?php

namespace App\Modules\Article\Actions;

use App\Modules\Article\Models\Article;
use App\Modules\Article\UI\API\Requests\DeleteArticleRequest;
use App\Ship\Abstracts\Actions\Action;
use Illuminate\Http\JsonResponse;

class DeleteArticleAction extends Action
{
    public function handle(Article $article): bool
    {
        return $article->delete();
    }

    public function asController(DeleteArticleRequest $request, Article $article): JsonResponse
    {
        $this->handle($article);

        return $this->noContent();
    }
}

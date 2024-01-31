<?php

namespace App\Modules\Article\Actions;

use App\Modules\Article\DTO\CreateArticleDTO;
use App\Modules\Article\Models\Article;
use App\Modules\Article\UI\API\Requests\CreateArticleRequest;
use App\Modules\Article\UI\API\Resources\ArticleResource;
use App\Ship\Abstracts\Actions\Action;
use Illuminate\Http\JsonResponse;

class CreateArticleAction extends Action
{
    public function handle(CreateArticleDTO $dto): Article
    {
        return Article::create($dto->all());
    }

    public function asController(CreateArticleRequest $request): JsonResponse
    {
        $article = $this->handle($request->toDTO());

        return (new ArticleResource($article))->created();
    }
}

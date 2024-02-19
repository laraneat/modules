<?php

namespace App\Modules\Article\Actions;

use App\Modules\Article\DTO\UpdateArticleDTO;
use App\Modules\Article\Models\Article;
use App\Modules\Article\UI\API\Requests\UpdateArticleRequest;
use App\Modules\Article\UI\API\Resources\ArticleResource;
use App\Ship\Abstracts\Actions\Action;
use App\Ship\Exceptions\UpdateResourceFailedException;

class UpdateArticleAction extends Action
{
    public function handle(Article $article, UpdateArticleDTO $dto): Article
    {
        $data = $dto->all();

        if (empty($data)) {
            throw new UpdateResourceFailedException();
        }

        $article->update($data);

        return $article;
    }

    public function asController(UpdateArticleRequest $request, Article $article): ArticleResource
    {
        $article = $this->handle($article, $request->toDTO());

        return new ArticleResource($article);
    }
}

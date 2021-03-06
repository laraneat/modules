<?php

namespace App\Modules\Article\Actions;

use App\Modules\Article\Models\Article;
use App\Modules\Article\UI\API\Requests\UpdateArticleRequest;
use App\Modules\Article\UI\API\Resources\ArticleResource;
//use App\Ship\Abstracts\Actions\Action;
//use App\Ship\Exceptions\UpdateResourceFailedException;

class UpdateArticleAction
{
    /**
     * @param Article $article
     * @param array $data
     *
     * @return Article
     */
    public function handle(Article $article, array $data): Article
    {
//        if (empty($data)) {
//            throw new UpdateResourceFailedException();
//        }

        $article->update($data);

        return $article;
    }

    /**
     * @param UpdateArticleRequest $request
     * @param Article $article
     *
     * @return ArticleResource
     */
    public function asController(UpdateArticleRequest $request, Article $article): ArticleResource
    {
        $sanitizedData = $request->sanitizeInput([
            //
        ]);

        $article = $this->handle($article, $sanitizedData);

        return new ArticleResource($article);
    }
}

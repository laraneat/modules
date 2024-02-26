<?php

namespace Modules\Author\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Author\DTO\UpdateAuthorDTO;
use Modules\Author\Models\Author;
use Modules\Author\UI\API\Requests\UpdateAuthorRequest;
use Modules\Author\UI\API\Resources\AuthorResource;

class UpdateAuthorAction
{
    use AsAction;

    public function handle(Author $author, UpdateAuthorDTO $dto): Author
    {
        $data = $dto->all();

        if ($data) {
            $author->update($data);
        }

        return $author;
    }

    public function asController(UpdateAuthorRequest $request, Author $author): AuthorResource
    {
        $author = $this->handle($author, $request->toDTO());

        return new AuthorResource($author);
    }
}

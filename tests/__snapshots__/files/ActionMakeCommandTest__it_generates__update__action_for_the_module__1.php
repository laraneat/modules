<?php

namespace App\Modules\Author\Actions;

use App\Modules\Author\DTO\UpdateAuthorDTO;
use App\Modules\Author\Models\Author;
use App\Modules\Author\UI\API\Requests\UpdateAuthorRequest;
use App\Modules\Author\UI\API\Resources\AuthorResource;
use Lorisleiva\Actions\Concerns\AsAction;

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

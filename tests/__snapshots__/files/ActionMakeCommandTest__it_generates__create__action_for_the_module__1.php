<?php

namespace Modules\Author\Actions;

use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Author\DTO\CreateAuthorDTO;
use Modules\Author\Models\Author;
use Modules\Author\UI\API\Requests\CreateAuthorRequest;
use Modules\Author\UI\API\Resources\AuthorResource;

class CreateAuthorAction
{
    use AsAction;

    public function handle(CreateAuthorDTO $dto): Author
    {
        return Author::create($dto->all());
    }

    public function asController(CreateAuthorRequest $request): JsonResponse
    {
        $author = $this->handle($request->toDTO());

        return (new AuthorResource($author))->created();
    }
}

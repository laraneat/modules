<?php

namespace App\Modules\Author\Actions;

use App\Modules\Author\DTO\CreateAuthorDTO;
use App\Modules\Author\Models\Author;
use App\Modules\Author\UI\API\Requests\CreateAuthorRequest;
use App\Modules\Author\UI\API\Resources\AuthorResource;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

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

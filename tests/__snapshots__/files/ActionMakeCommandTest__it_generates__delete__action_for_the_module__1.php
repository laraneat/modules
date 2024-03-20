<?php

namespace Modules\Author\Actions;

use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Author\Models\Author;
use Modules\Author\UI\API\Requests\DeleteAuthorRequest;

class DeleteAuthorAction
{
    use AsAction;

    public function handle(Author $author): bool
    {
        return $author->delete();
    }

    public function asController(DeleteAuthorRequest $request, Author $author): JsonResponse
    {
        $this->handle($author);

        return $this->noContent();
    }
}

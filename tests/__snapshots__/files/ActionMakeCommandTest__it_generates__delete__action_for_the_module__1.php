<?php

namespace App\Modules\Author\Actions;

use App\Modules\Author\Models\Author;
use App\Modules\Author\UI\API\Requests\DeleteAuthorRequest;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;

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

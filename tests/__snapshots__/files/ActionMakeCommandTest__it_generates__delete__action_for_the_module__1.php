<?php

namespace Modules\Author\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Author\Models\Author;
use Modules\Author\UI\API\Requests\DeleteAuthorRequest;
use Symfony\Component\HttpFoundation\Response;

class DeleteAuthorAction
{
    use AsAction;

    public function handle(Author $author): bool
    {
        return $author->delete();
    }

    public function asController(DeleteAuthorRequest $request, Author $author): Response
    {
        $this->handle($author);

        return $this->noContent();
    }
}

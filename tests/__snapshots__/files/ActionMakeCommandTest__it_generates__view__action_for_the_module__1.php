<?php

namespace Modules\Author\Actions;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Author\Models\Author;
use Modules\Author\UI\API\QueryWizards\AuthorQueryWizard;
use Modules\Author\UI\API\Requests\ViewAuthorRequest;
use Modules\Author\UI\API\Resources\AuthorResource;

class ViewAuthorAction
{
    use AsAction;

    public function handle(ViewAuthorRequest $request, Author $author): Model
    {
        return AuthorQueryWizard::for($author)->build();
    }

    public function asController(ViewAuthorRequest $request, Author $author): AuthorResource
    {
        return new AuthorResource($this->handle($request, $author));
    }
}

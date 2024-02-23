<?php

namespace App\Modules\Author\Actions;

use App\Modules\Author\Models\Author;
use App\Modules\Author\UI\API\QueryWizards\AuthorQueryWizard;
use App\Modules\Author\UI\API\Requests\ViewAuthorRequest;
use App\Modules\Author\UI\API\Resources\AuthorResource;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

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

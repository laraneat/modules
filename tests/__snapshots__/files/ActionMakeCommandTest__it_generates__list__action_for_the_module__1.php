<?php

namespace Modules\Author\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Author\Models\Author;
use Modules\Author\UI\API\QueryWizards\AuthorsQueryWizard;
use Modules\Author\UI\API\Requests\ListAuthorsRequest;
use Modules\Author\UI\API\Resources\AuthorResource;

class ListAuthorsAction
{
    use AsAction;

    public function handle(ListAuthorsRequest $request): LengthAwarePaginator
    {
        return AuthorsQueryWizard::for(Author::query())
            ->build()
            ->paginate();
    }

    public function asController(ListAuthorsRequest $request): ResourceCollection
    {
        return AuthorResource::collection($this->handle($request));
    }
}

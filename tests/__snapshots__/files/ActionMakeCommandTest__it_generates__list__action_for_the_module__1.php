<?php

namespace App\Modules\Author\Actions;

use App\Modules\Author\Models\Author;
use App\Modules\Author\UI\API\QueryWizards\AuthorsQueryWizard;
use App\Modules\Author\UI\API\Requests\ListAuthorsRequest;
use App\Modules\Author\UI\API\Resources\AuthorResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Lorisleiva\Actions\Concerns\AsAction;

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

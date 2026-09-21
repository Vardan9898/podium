<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Tags\SearchTags;
use App\Http\Requests\TagSearchRequest;
use App\Http\Resources\TagResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TagController extends Controller
{
    /**
     * Search tags for autocomplete (max 20 results).
     */
    public function __invoke(TagSearchRequest $request, SearchTags $searchTags): AnonymousResourceCollection
    {
        return TagResource::collection($searchTags->handle($request->string('search')->value()));
    }
}

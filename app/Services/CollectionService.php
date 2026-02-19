<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CollectionService
{
    public function list(User $user): EloquentCollection
    {
        return $user->collections()
            ->withCount('publications')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    public function show(Collection $collection): Collection
    {
        return $collection->load([
            'publications' => fn ($q) => $q->with([
                'author' => fn ($q) => $q->withCount(['followers', 'following']),
                'categories',
                'tags',
                'city',
                'media',
            ])->withCount('comments', 'likes')->latest(),
        ])->loadCount('publications');
    }

    public function create(User $user, string $name): Collection
    {
        return $user->collections()->create(['name' => $name]);
    }

    public function update(Collection $collection, string $name): Collection
    {
        $collection->update(['name' => $name]);

        return $collection;
    }

    public function delete(Collection $collection): void
    {
        $collection->delete();
    }

    public function getOrCreateDefault(User $user): Collection
    {
        return $user->collections()->firstOrCreate(
            ['is_default' => true],
            ['name' => 'Salvos']
        );
    }

    public function togglePublication(Collection $collection, Publication $publication): bool
    {
        $exists = $collection->publications()->where('publication_id', $publication->id)->exists();

        if ($exists) {
            $collection->publications()->detach($publication->id);

            return false;
        }

        $collection->publications()->attach($publication->id);

        return true;
    }
}

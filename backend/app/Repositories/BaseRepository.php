<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BaseRepository
{
    private array $with = [];

    public function __construct(
        protected Model $model
    ) {}

    /**
     * Set relationships to eager load.
     */
    public function withRelations(array $relations): static
    {
        $this->with = $relations;
        return $this;
    }

    /**
     * Base query with any defined relations.
     */
    protected function newQueryWithRelations(): Builder
    {
        return $this->model->newQuery()->with($this->with);
    }

    /**
     * Find a record or throw.
     */
    public function findBy(int $id): Model
    {
        return $this->newQueryWithRelations()->find($id);
    }
}

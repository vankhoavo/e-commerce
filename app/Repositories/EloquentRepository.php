<?php

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class EloquentRepository implements RepositoryInterface
{
    public function __construct(protected Model $model)
    {
    }

    public function find(int $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function all(): Collection
    {
        return $this->model->newQuery()->get();
    }

    public function create(array $attributes): Model
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(Model $model, array $attributes): bool
    {
        return $model->update($attributes);
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}

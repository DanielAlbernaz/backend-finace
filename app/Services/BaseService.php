<?php

namespace App\Services;

use App\Exceptions\InvalidContextException;
use Closure;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Prettus\Repository\Contracts\RepositoryInterface;

class BaseService implements BaseServiceInterface
{
    protected $repository;
    protected $defaultRelations = [];
    protected $cacheTime;
    protected $extraContext = ['paginate'];

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->cacheTime = 60 * 60 * 6;
    }

    public static function constrainedTransaction(Closure $closure)
    {
        return DB::transaction($closure);
    }

    public function paginate(array $data): Paginator
    {
        return Cache::tags($this->makeContext('paginate'))
            ->remember(
                Arr::query(request()->query()),
                $this->cacheTime,
                fn () => $this->repository->with($this->defaultRelations)
                    ->paginate($data['limit'] ?? null, $data['columns'] ?? ['*'])
            );
    }

    public function index(): Collection
    {
        return Cache::tags($this->cacheContext())->remember(
            'all',
            $this->cacheTime,
            fn () => $this->repository->with($this->defaultRelations)->all()
        );
    }

    public function delete(Model $model): void
    {
        $this->flushPaginateCache();
        Cache::tags($this->cacheContext())->forget($model->id);
        $this->repository->delete($model->id);
    }

    public function asyncDelete(Model $model): void
    {
        $this->flushPaginateCache($model->office_id);
        Cache::tags($this->cacheContext($model->office_id))->forget($model->id);
        $this->repository->skipCriteria()->delete($model->id);
    }

    public function forceDelete(Model $model): void
    {
        $this->flushPaginateCache();
        Cache::tags($this->cacheContext())->forget($model->id);
        $this->repository->forceDelete($model->id);
    }

    public function store(array $data): Model
    {
        $this->flushPaginateCache();
        $model = $this->repository->create($data);
        return $this->find($model);
    }

    public function find(Model $model): Model
    {
        return Cache::tags($this->cacheContext())->remember(
            $model->id,
            $this->cacheTime,
            fn () => $this->repository->with($this->defaultRelations)->find($model->id)
        );
    }

    public function update(array $data, Model $model): Model
    {
        $this->flushPaginateCache();
        Cache::tags($this->cacheContext())->forget($model->id);
        $model = $this->repository->update($data, $model->id);
        return $this->find($model);
    }

    public function deleteIds(array $ids): int
    {
        $this->flushPaginateCache();
        foreach ($ids as $id) {
            Cache::tags($this->cacheContext())->forget($id);
        }
        return $this->repository->whereIn('id', $ids)->delete();
    }

    public function updateIds(array $ids, array $data)
    {
        $this->flushPaginateCache();
        foreach ($ids as $id) {
            Cache::tags($this->cacheContext())->forget($id);
        }
        return $this->repository->whereIn('id', $ids)->update($data);
    }

    public function updateIdsWhere(array $ids, array $data, array $where)
    {
        $this->flushPaginateCache();
        foreach ($ids as $id) {
            Cache::tags($this->cacheContext())->forget($id);
        }
        return $this->repository->whereIn('id', $ids)
            ->where($where)->update($data);
    }

    protected function makeContext(string $extraContext): array
    {
        array_search($extraContext, $this->extraContext) === false
        && throw new InvalidContextException($extraContext);
        return array_merge(
            $this->cacheContext(),
            [$this->repository->model() . "-${extraContext}-" . auth()->user()->office_id]
        );
    }

    protected function cacheContext(?int $office_id = null)
    {
        if ($office_id === null) {
            $office_id = auth()->user()->office_id;
        }
        return [$office_id, $this->repository->model()];
    }

    protected function flushPaginateCache(?int $office_id = null)
    {
        if ($office_id === null) {
            $office_id = auth()->user()->office_id;
        }
        Cache::tags($this->cacheContext($office_id))->forget('all');
        foreach ($this->extraContext as $extra) {
            Cache::tags([
                $this->repository->model() . "-${extra}-" . $office_id,
            ])->flush();
        }
    }
}

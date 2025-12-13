<?php
namespace App\Repositories;

use App\DataTransferObjects\OrderFilterData;
use App\Models\Order;

class OrderRepository extends BaseRepository
{
    const RELATIONS = ['assetType', 'user'];

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        parent::__construct(new Order());
    }

    public function create(array $data): Order
    {
        return $this->model->create($data)->load(self::RELATIONS);
    }

    public function paginate(OrderFilterData $filter, int $perPage)
    {
        return $this->withRelations(self::RELATIONS)
            ->newQueryWithRelations()
            // Use the filter object to conditionally build the query
            ->when($filter->symbol, fn($q, $symbol) => $q->where('symbol', $symbol))
            ->when($filter->side, fn($q, $side) => $q->where('side', $side->value))
            ->when($filter->status, fn($q, $status) => $q->where('status', $status->value))
            ->paginate($perPage);
    }

}

<?php

namespace App\Http\Queries;

use App\TsOperator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class OperatorsQuery extends QueryBuilder
{
	public function __construct(?Request $request = null)
	{
		parent::__construct(
			TsOperator::query()
				->select('ts_operators.*')
				->with('user')
				->join('users', 'ts_operators.user_id', 'users.id'),
			$request
		);

		$this->allowedSorts([
			AllowedSort::field('id', 'ts_operators.id'),
			AllowedSort::field('name', 'users.name'),
			AllowedSort::field('email', 'users.email'),
			AllowedSort::field('zendesk_id', 'users.zendesk_id'),
			'is_available',
			'tickets_limit',
			AllowedSort::field('created_at', 'ts_operators.created_at'),
			AllowedSort::field('updated_at', 'ts_operators.updated_at'),
		]);

		$this->allowedFilters([
			AllowedFilter::exact('id'),
			AllowedFilter::exact('is_available'),
			AllowedFilter::exact('tickets_limit'),
			AllowedFilter::callback('keyword', function (Builder $query, $value) {
				$query->where(function (Builder $query) use ($value) {
					$query
						->orWhere('users.name', 'LIKE', '%' . $value . '%')
						->orWhere('users.email', 'LIKE', '%' . $value . '%');
				});
			}),
		]);
	}
}

<?php

namespace App\Http\Queries;

use App\TsTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TicketsQuery extends QueryBuilder
{
	public function __construct(?Request $request = null)
	{
		parent::__construct(TsTicket::query(), $request);

		$this->allowedSorts([
			'id',
			'request_type',
			'item_id',
			'status',
			'author_id',
			'awaiting_reply',
			'closure_requested',
			'is_pending',
			'created_at',
			'updated_at',
		]);

		$this->allowedFilters([
			AllowedFilter::scope('queue'),
			AllowedFilter::scope('awaiting_staff'),
			AllowedFilter::scope('pending'),
			AllowedFilter::scope('awaiting_closure'),
			AllowedFilter::scope('created_from'),
			AllowedFilter::scope('created_to'),
			AllowedFilter::scope('updated_from'),
			AllowedFilter::scope('updated_to'),
			AllowedFilter::callback('keyword', function (Builder $query, $value) {
				$query->where(function (Builder $query) use ($value) {
					$query->where('id', 'LIKE', $value . '%')
						->orWhere('subject', 'LIKE', '%' . $value . '%')
						->orWhere('content', 'LIKE', '%' . $value . '%');
				});
			}),
			AllowedFilter::callback('operator_replied', function (Builder $query, $value) {
				$id = $value;
				$from = $to = null;
				if (is_array($value)) {
					$value = $value + [1 => null, 2 => null];
					list ($id, $from, $to) = $value;
				}

				$query->whereHas('replies', function (Builder $query) use ($id, $from, $to) {
					$query->where('author_id', $id);
					if ($from) {
						$query->where('created_at', '>=', $from);
					}
					if ($to) {
						$query->where('created_at', '<', $to);
					}
				});
			}),
			AllowedFilter::exact('id'),
			AllowedFilter::exact('request_type'),
			AllowedFilter::exact('item_id'),
			AllowedFilter::exact('status'),
			AllowedFilter::exact('author_id'),
			AllowedFilter::exact('awaiting_reply'),
			AllowedFilter::exact('closure_requested'),
			AllowedFilter::exact('is_pending'),
		]);

		$this->allowedIncludes([
			'author',
			'item',
		]);
	}
}

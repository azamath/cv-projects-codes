<?php

namespace App\Http\Queries;

use App\StmItem;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ItemsQuery extends QueryBuilder
{
	public function __construct(?Request $request = null)
	{
		parent::__construct(StmItem::query(), $request);

		$this->allowedSorts([
			'id',
			'title',
			'short_name',
			'slug',
			'order',
			'created_at',
			'updated_at',
		]);

		$this->allowedFilters([
			AllowedFilter::exact('id'),
			'title',
			'slug',
			AllowedFilter::exact('item_type'),
			AllowedFilter::exact('is_supported'),
			AllowedFilter::exact('is_ticket_allowed'),
			AllowedFilter::exact('envato_id'),
			AllowedFilter::exact('freemius_id'),
		]);
	}
}

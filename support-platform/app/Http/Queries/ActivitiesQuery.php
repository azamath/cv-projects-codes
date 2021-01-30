<?php

namespace App\Http\Queries;

use App\TsTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ActivitiesQuery extends QueryBuilder
{
	public function __construct(?Request $request = null)
	{
		parent::__construct(Activity::query(), $request);

		$this->allowedSorts([
			'id',
			'created_at',
			'updated_at',
		]);

		$this->allowedFilters([
			AllowedFilter::exact('id'),
			AllowedFilter::exact('log_name'),
			AllowedFilter::exact('subject_id'),
			AllowedFilter::exact('subject_type'),
			AllowedFilter::exact('causer_id'),
			AllowedFilter::exact('causer_type'),
			'description'
		]);

		$this->allowedIncludes([
			'subject',
			'causer',
		]);
	}
}

<?php

namespace App\Components;

use App\TsOperator;
use App\TsTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Assigner
{

	public function run()
	{
		$unassigned = TsTicket::unassigned()
			->whereNotNull('zendesk_id')
			->orderBy('created_at', 'asc')
			->with('item')
			->get();

		if ($unassigned->isEmpty()) {
			return;
		}

		$operators = TsOperator::available()
			->whereHas('user', function (Builder $userQuery) {
				$userQuery->whereNotNull('zendesk_id');
			})
			->with(['user', 'items'])
			->get();

		$this->fillOpenTicketsCounts($operators);

		/** @var \App\TsTicket $ticket */
		foreach ($unassigned as $ticket) {
			$candidates = $this->ticketCandidates($ticket, $operators);
			if ($candidates->isNotEmpty()) {
				$this->assign($ticket, $this->selectOneFromCandidates($candidates));
			}
		}
	}

	public function fillOpenTicketsCounts(Collection $operators)
	{
		$counts = TsTicket::assigned()
			->getQuery()
			->whereNotNull('zendesk_id')
			->select(['assignee_id', DB::raw('count(*) as count')])
			->groupBy('assignee_id')
			->pluck('count', 'assignee_id');

		/** @var \App\TsOperator $operator */
		foreach ($operators as $operator) {
			$operator->open_tickets_count = intval($counts->get($operator->user->id));
		}
	}

	public function ticketCandidates(TsTicket $ticket, Collection $operators): Collection
	{
		return $operators->filter(function (TsOperator $operator) use ($ticket) {
			return $this->isOperatorCandidate($operator, $ticket);
		});
	}

	public function isOperatorCandidate(TsOperator $operator, TsTicket $ticket): bool
	{
		if (!$operator->is_available || $this->isTicketsLimitReached($operator)) {
			return false;
		}

		return $this->isOperatorSpecializesOnItem($operator, $ticket);
	}

	protected function isTicketsLimitReached(TsOperator $operator): bool
	{
		return $operator->open_tickets_count >= $operator->tickets_limit;
	}

	protected function isOperatorSpecializesOnItem(TsOperator $operator, TsTicket $ticket): bool
	{
		/** @var \App\StmItem $item */
		$item = $operator->items->firstWhere('id', $ticket->item_id);
		if (!$item) {
			return false;
		}

		if ($ticket->request_type->isSupport()) {
			return (bool) $item->pivot->support;
		}
		elseif ($ticket->request_type->isPreSale()) {
			return (bool) $item->pivot->pre_sale;
		}

		return false;
	}

	protected function selectOneFromCandidates(Collection $candidates): TsOperator
	{
		return $candidates->sortBy('open_tickets_count', SORT_REGULAR, false)
			->first();
	}

	protected function assign(TsTicket $ticket, TsOperator $operator)
	{
		try {
			zendeskApi()->updateTicket($ticket->zendesk_id, [
				'assignee_id' => $operator->user->zendesk_id,
			]);
			$ticket->assignee_id = $operator->user->id;
			$ticket->timestamps = false;
			$ticket->save();
			$operator->open_tickets_count ++;
		}
		catch (\Exception $e) {
			if (!app()->runningUnitTests()) {
				report($e);
			}
			else {
				throw $e;
			}
		}
	}
}

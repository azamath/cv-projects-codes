<?php

namespace Tests\Unit\Components;

use App\Components\Assigner;
use App\Components\ZendeskApi;
use App\Enums\TicketRequestType;
use App\StmItem;
use App\TsOperator;
use App\TsTicket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AssignerTest extends TestCase
{
	use DatabaseMigrations;

	public function testIsOperatorCandidate()
	{
		/**
		 * @var \App\TsOperator $operator
		 * @var \App\TsOperator $operator2
		 */
		$operators = collect([
			$operator = factory(TsOperator::class)->create([
				'tickets_limit' => 1,
				'is_available' => true,
			]),
			factory(TsOperator::class)->create([
				'tickets_limit' => 0,
				'is_available' => true,
			]),
		]);
		$item = factory(StmItem::class)->create();
		$item2 = factory(StmItem::class)->create();
		$ticket = factory(TsTicket::class)->create([
			'request_type' => TicketRequestType::SUPPORT,
			'item_id' => $item->id,
		]);
		$ticket2 = factory(TsTicket::class)->create([
			'request_type' => TicketRequestType::PRE_SALE,
			'item_id' => $item2->id,
		]);

		$assigner = app(Assigner::class);

		// no items associated with operator
		$this->assertFalse($assigner->isOperatorCandidate($operator, $ticket));
		$this->assertFalse($assigner->isOperatorCandidate($operator, $ticket2));
		$this->assertCount(0, $assigner->ticketCandidates($ticket, $operators));
		$this->assertCount(0, $assigner->ticketCandidates($ticket2, $operators));

		// items associated with operator without ticket type
		$operator->items()->attach($item);
		$operator->items()->attach($item2);
		$operator->refresh();
		$this->assertFalse($assigner->isOperatorCandidate($operator, $ticket));
		$this->assertFalse($assigner->isOperatorCandidate($operator, $ticket2));

		// items associated with operator with specific ticket type
		$operator->items()->updateExistingPivot($item, ['support' => true]);
		$operator->items()->updateExistingPivot($item2, ['pre_sale' => true]);
		$operator->refresh();
		$this->assertTrue($assigner->isOperatorCandidate($operator, $ticket));
		$this->assertTrue($assigner->isOperatorCandidate($operator, $ticket2));
		$this->assertCount(1, $assigner->ticketCandidates($ticket, $operators));
		$this->assertCount(1, $assigner->ticketCandidates($ticket2, $operators));

		// tickets limit reached (current 1)
		$operator->open_tickets_count = 1;
		$this->assertFalse($assigner->isOperatorCandidate($operator, $ticket));
		$this->assertFalse($assigner->isOperatorCandidate($operator, $ticket2));

		$operator->is_available = false;
		$operator->save();
		$this->assertFalse($assigner->isOperatorCandidate($operator, $ticket));
	}

	public function testFillOpenTicketsCounts()
	{
		/**
		 * @var \App\TsOperator $operator1
		 * @var \App\TsOperator $operator2
		 */
		$operators = Collection::make([
			$operator1 = factory(TsOperator::class)->create(),
			$operator2 = factory(TsOperator::class)->create(),
		]);
		factory(TsTicket::class, 2)->states('zendesk')->create([
			'status' => 'open',
			'assignee_id' => $operator1->user->id,
		]);
		factory(TsTicket::class)->states('zendesk')->create([
			'status' => 'pending',
			'assignee_id' => $operator1->user->id,
		]);
		factory(TsTicket::class)->states('zendesk', 'closed')->create([
			'assignee_id' => $operator1->user->id,
		]);
		factory(TsTicket::class, 3)->states('zendesk')->create([
			'status' => 'open',
			'assignee_id' => $operator2->user->id,
		]);
		factory(TsTicket::class, 2)->states('zendesk', 'closed')->create([
			'assignee_id' => $operator2->user->id,
		]);

		app(Assigner::class)->fillOpenTicketsCounts($operators);
		$this->assertEquals(2, $operator1->open_tickets_count);
		$this->assertEquals(3, $operator2->open_tickets_count);
	}
}

<?php

namespace Tests\Unit\Components;

use App\Components\ZendeskApi;
use App\Components\ZendeskSync;
use App\StmItem;
use App\TsTicket;
use App\TsUpload;
use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ZendeskSyncTest extends TestCase
{
	use DatabaseMigrations;

	protected function setUp(): void
	{
		parent::setUp();
		factory(StmItem::class)->states(['supported', 'envato'])->create();
	}

	public function testUserCreate()
	{
		$userData = $this->stubUserData();
		$this->mock(ZendeskApi::class)
			->shouldReceive('user')
			->andReturn($this->toObject(['user' => $userData]));

		$user = app(ZendeskSync::class)->syncUser($userData['id']);

		$this->assertDatabaseHas('users', [
			'id' => $user->id,
			'zendesk_id' => $userData['id'],
			'email' => $userData['email'],
			'name' => $userData['name'],
		]);
	}

	public function testUserUpdate()
	{
		$userData = $this->stubUserData();

		/** @var \App\User $user */
		$user = factory(User::class)->create([
			'zendesk_id' => $userData['id'],
		]);

		$this->mock(ZendeskApi::class)
			->shouldReceive('user')
			->andReturn($this->toObject(['user' => $userData]));

		app(ZendeskSync::class)->syncUser($userData['id']);

		$user->refresh();
		$this->assertEquals($userData['id'], $user->zendesk_id);
		$this->assertEquals($userData['email'], $user->email);
		$this->assertEquals($userData['name'], $user->name);
	}

	public function testUserUpdateByEmail()
	{
		$userData = $this->stubUserData();

		/** @var \App\User $user */
		$user = factory(User::class)->create([
			'email' => $userData['email'],
		]);

		$this->mock(ZendeskApi::class)
			->shouldReceive('user')
			->andReturn($this->toObject(['user' => $userData]));

		app(ZendeskSync::class)->syncUser($userData['id']);

		$user->refresh();
		$this->assertEquals($userData['id'], $user->zendesk_id);
		$this->assertEquals($userData['email'], $user->email);
		$this->assertEquals($userData['name'], $user->name);
	}

	public function testTicketUpdate()
	{
		$ticketData = $this->stubTicketData();
		$userData = $this->stubUserData();
		$commentData = $this->stubCommentData();

		/** @var \App\User $author */
		$author = factory(User::class)->create([
			'zendesk_id' => $userData['id'],
		]);

		/** @var \App\TsTicket $ticket */
		$ticket = factory(TsTicket::class)->create([
			'author_id' => $author->id,
			'zendesk_id' => $ticketData['id'],
		]);

		$this->mock(ZendeskApi::class)
			->shouldReceive('ticket')
			->with($ticketData['id'])
			->andReturn($this->toObject(['ticket' => $ticketData, 'users' => [$userData]]))
			->getMock()
			->shouldReceive('ticketComments')
			->with($ticketData['id'])
			->andReturn($this->toObject(['comments' => [$commentData], 'users' => [$userData]]));

		app(ZendeskSync::class)->syncTicket($ticket);

		$ticket->refresh();
		$this->assertEquals($ticketData['status'], $ticket->status);
		$this->assertEquals($ticketData['subject'], $ticket->subject);
		$this->assertEquals($commentData['html_body'], $ticket->content);
		$this->assertCount(0, $ticket->replies);
	}

	public function testTicketNewComment()
	{
		$ticketData = $this->stubTicketData();
		$userData = $this->stubUserData();
		$agentData = $this->stubUserData(['id' => 23451, 'role' => 'agent']);
		$commentData = $this->stubCommentData();
		$agentCommentData = $this->stubCommentData(['id' => 7891,'author_id' => 23451]);
		$agentNoteData = $this->stubCommentData(['id' => 7892, 'author_id' => 23451, 'public' => false]);

		/** @var \App\User $author */
		$author = factory(User::class)->create([
			'zendesk_id' => $userData['id'],
		]);

		/** @var \App\User $operator */
		$operator = factory(User::class)->create([
			'zendesk_id' => $agentData['id'],
		]);

		/** @var \App\TsTicket $ticket */
		$ticket = factory(TsTicket::class)->create([
			'author_id' => $author->id,
			'zendesk_id' => $ticketData['id'],
		]);

		$this->mock(ZendeskApi::class)
			->shouldReceive([
				'ticket' => $this->toObject([
					'ticket' => $ticketData,
					'users' => [$userData, $agentData],
				]),
				'ticketComments' => $this->toObject([
					'comments' => [$commentData, $agentCommentData, $agentNoteData],
					'users' => [$userData, $agentData],
				]),
			]);

		app(ZendeskSync::class)->syncTicket($ticket);

		$ticket->refresh();
		$this->assertCount(2, $ticket->replies);

		$replies = $ticket->replies->keyBy('zendesk_id');

		$reply = $replies->get(7891);
		$this->assertEquals($operator->id, $reply->author_id);
		$this->assertEquals('Y', $reply->is_staff_reply);
		$this->assertEquals('N', $reply->is_note);

		$reply = $replies->get(7892);
		$this->assertEquals($operator->id, $reply->author_id);
		$this->assertEquals('Y', $reply->is_staff_reply);
		$this->assertEquals('Y', $reply->is_note);
	}

	public function testSyncAttachments()
	{
		$attachmentsData = $this->toObject([
			$this->stubAttachmentData(['id' => 34567]),
			$this->stubAttachmentData(['id' => 34568]),
		]);

		$owner = factory(User::class)->create();

		app(ZendeskSync::class)->syncAttachments($attachmentsData, $owner);
		$uploads = TsUpload::all();
		$this->assertCount(2, $uploads);

		// +1 upload
		$attachmentsData[] = $this->toObject($this->stubAttachmentData(['id' => 34569]));

		app(ZendeskSync::class)->syncAttachments($attachmentsData, $owner);
		$uploads = TsUpload::all();
		$this->assertCount(3, $uploads);
	}

	protected function stubUserData($override = []): array
	{
		return array_merge([
			'id' => 12345,
			'email' => 'johnny@example.com',
			'name' => 'Johnny End User',
			'time_zone' => 'Copenhagen',
			'organization_id' => 57542,
			'role' => 'end-user',
			'verified' => true,
			'created_at' => '2009-07-20T22:55:29Z',
			'updated_at' => '2011-05-05T10:38:52Z',
		], $override);
	}

	protected function stubTicketData(): array
	{
		return [
			'id' => 67890,
			'status' => 'open',
			'type' => 'incident',
			'subject' => 'Help, my printer is on fire!',
			'description' => 'The fire is very colorful.',
			'priority' => 'high',
			'requester_id' => 12345,
			'assignee_id' => 235323,
			'collaborator_ids' => [35334, 234],
			'due_at' => null,
			'external_id' => 'ahg35h3jh',
			'follower_ids' => [35334, 234],
			'group_id' => 98738,
			'has_incidents' => false,
			'organization_id' => 509974,
			'problem_id' => 9873764,
			'raw_subject' => '{{dc.printer_on_fire}}',
			'recipient' => 'support@company.com',
			'sharing_agreement_ids' => [84432],
			'submitter_id' => 76872,
			'tags' => ['enterprise', 'other_tag'],
			'custom_fields' => [
				['id' => config('services.zendesk.field_request_type'), 'value' => 'request-type-support'],
				['id' => config('services.zendesk.field_item_id'), 'value' => StmItem::supported()->get()->random()->slug],
			],
			'created_at' => '2009-07-20T22:55:29Z',
			'updated_at' => '2011-05-05T10:38:52Z',
			'url' => 'https://company.zendesk.com/api/v2/tickets/35436.json',
			'via' => [
				'channel' => 'web',
			],
			'satisfaction_rating' => [
				'comment' => 'Great support!',
				'id' => 1234,
				'score' => 'good',
			],
		];
	}

	protected function stubCommentData($override = []): array
	{
		return array_merge([
			'id' => 7890,
			'author_id' => 12345,
			'html_body' => '<p>The fire is very colorful.</p>',
			'public' => true,
			'attachments' => [],
			'created_at' => '2009-07-20T22:55:29Z',
			'updated_at' => '2011-05-05T10:38:52Z',
		], $override);
	}

	protected function stubAttachmentData($override = []): array
	{
		return array_merge([
			'id' => 34567,
			'content_url' => 'https://company.zendesk.com/attachments/my_funny_profile_pic.png',
			'file_name' => 'my_funny_profile_pic.png',
			'size' => 166144,
		], $override);
	}

	protected function toObject($data)
	{
		return json_decode(json_encode($data), false);
	}

}

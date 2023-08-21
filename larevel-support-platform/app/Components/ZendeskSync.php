<?php

namespace App\Components;

use App\Enums\TicketRequestType;
use App\StmItem;
use App\TsReply;
use App\TsTicket;
use App\TsUpload;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ZendeskSync
{

	public function syncUser($userId): User
	{
		$userData = zendeskApi()->user($userId)->user;

		return $this->syncUserData($userData);
	}

	public function syncUserData($userData): User
	{
		/** @var \App\User $user */
		$user = User::query()
			->where('zendesk_id', $userData->id)
			->orWhere('email', $userData->email)
			->firstOrNew([]);

		$user->forceFill([
			'zendesk_id' => $userData->id,
			'email' => $userData->email,
			'name' => $userData->name,
		]);

		if (!$user->exists) {
			$user->password = bcrypt(Str::random(12));
		}

		$user->save();

		return $user;
	}

	public function syncTicket(TsTicket $ticket): ?TsTicket
	{
		$ticketRes = zendeskApi()->ticket($ticket->zendesk_id);
		$commentsRes = zendeskApi()->ticketComments($ticket->zendesk_id);

		$zTicket = $ticketRes->ticket;
		$zUsers = collect($ticketRes->users)->keyBy('id')
			->union(collect($commentsRes->users)->keyBy('id'));
		$authors = $this->prepareAuthors($zUsers)->keyBy('zendesk_id');
		$zComments = collect($commentsRes->comments);

		/** @var \App\User $author */
		$author = $authors->get($zTicket->requester_id);
		/** @var \App\User $assignee */
		$assignee = $zTicket->assignee_id ? $authors->get($zTicket->assignee_id) : null;

		// shift is required, since 1st comment is the ticket's content
		$firstComment = $zComments->shift();
		$ticket->forceFill(array_merge([
			'author_id' => $author->id,
			'assignee_id' => optional($assignee)->id,
			'status' => $zTicket->status,
			'subject' => $zTicket->subject,
			'content' => $this->prepareCommentBody($firstComment),
			'attachment_ids' => serialize(
				$this->syncAttachments($firstComment->attachments, $author)
					->pluck('id')
					->all()
			),
			'created_at' => Carbon::parse($zTicket->created_at)->setTimezone(config('app.timezone')),
			'updated_at' => Carbon::parse($zTicket->updated_at)->setTimezone(config('app.timezone')),
		], $this->translateCustomFields($zTicket)));

		$ticket->save();

		// exclude already synced comments
		$zComments = $zComments->whereNotIn('id', $ticket->replies()->get()->pluck('zendesk_id')->all());

		foreach ($zComments as $zComment) {
			/** @var \App\User $author */
			$author = $authors->get($zComment->author_id);
			$reply = (new TsReply())->forceFill([
				'zendesk_id' => $zComment->id,
				'ticket_id' => $ticket->id,
				'author_id' => $author->id,
				'content' => $this->prepareCommentBody($zComment),
				'is_staff_reply' => ($zUsers[$zComment->author_id]->role == 'end-user') ? 'N' : 'Y',
				'is_note' => $zComment->public ? 'N' : 'Y',
				'attachment_ids' => serialize(
					$this->syncAttachments($zComment->attachments, $author)
						->pluck('id')
						->all()
				),
				'created_at' => Carbon::parse($zComment->created_at)->setTimezone(config('app.timezone')),
			]);
			$reply->save();
		}

		return $ticket;
	}

	/**
	 * @param \Illuminate\Support\Collection $authors
	 *
	 * @return \App\User[]|\Illuminate\Database\Eloquent\Collection
	 */
	protected function prepareAuthors($authors)
	{
		$author_ids = $authors->pluck('id')->all();
		$users = User::query()
			->whereIn('zendesk_id', $author_ids)
			->get();

		$missed = array_diff($author_ids, $users->pluck('zendesk_id')->all());
		foreach ($authors->whereIn('id', $missed) as $userData) {
			$users[] = $this->syncUserData($userData);
		}

		return $users->keyBy('zendesk_id');
	}

	protected function translateCustomFields($ticketData): array
	{
		$customFields = [];

		$field = collect($ticketData->custom_fields)->firstWhere('id', config('services.zendesk.field_request_type'));
		if ($field && $field->value) {
			$requestType = $field->value;
			$requestType = str_replace('request-type-', '', $requestType);
			if (TicketRequestType::has($requestType)) {
				$requestType = TicketRequestType::create($requestType);
				$customFields['request_type'] = $requestType->value();
			}
		}

		$field = collect($ticketData->custom_fields)->firstWhere('id', config('services.zendesk.field_item_id'));
		if ($field && $field->value) {
			$itemSlug = $field->value;
			/** @var \App\StmItem $item */
			$item = StmItem::query()->where('slug', $itemSlug)->first();
			if ($item) {
				$customFields['item_id'] = $item->id;
			}
		}

		return $customFields;
	}

	public function syncAttachments($zAttachments, User $owner)
	{
		$zAttachments = collect($zAttachments);
		$zIds = $zAttachments->pluck('id')->all();
		$uploads = TsUpload::query()
			->whereIn('zendesk_id', $zIds)
			->get();

		$missed = array_diff($zIds, $uploads->pluck('zendesk_id')->all());
		foreach ($zAttachments->whereIn('id', $missed) as $zAttachment) {
			$uploads[] = $this->syncAttachment($zAttachment, $owner);
		}

		return $uploads;
	}

	public function syncAttachment($zAttachment, User $owner): TsUpload
	{
		$upload = new TsUpload();
		$upload->url = $zAttachment->content_url;
		$upload->owner_id = $owner->id;
		$upload->zendesk_id = $zAttachment->id;
		$upload->encrypted_name = '';
		$upload->file_name = $zAttachment->file_name;
		$upload->file_ext = pathinfo($zAttachment->file_name, PATHINFO_EXTENSION);
		$upload->uploaded_at = now();
		$upload->save();

		return $upload;
	}

	protected function prepareCommentBody($zComment)
	{
		$starter = '<div class="zd-comment" dir="auto">';
		if (strpos($zComment->html_body, $starter) === false) {
			return $zComment->html_body;
		}

		// remove opening <div ...>
		return str_replace(
			$starter,
			'',
			// remove closing </div>
			substr(trim($zComment->html_body), 0, -6)
		);
	}

	public function saveUser(User $user)
	{
		$userRes = zendeskApi()->createOrUpdateUser([
			'name' => $user->name,
			'email' => $user->email,
		]);

		$user->zendesk_id = $userRes->user->id;
		$user->timestamps = false;
		$user->save();
	}
}

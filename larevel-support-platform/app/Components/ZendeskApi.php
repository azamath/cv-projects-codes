<?php

namespace App\Components;

/**
 * Wrapper class over Zendesk Http Client, that allows to mock and test it
 */
class ZendeskApi
{

	public function createOrUpdateUser($userData)
	{
		return $this->zendesk()->users()->createOrUpdate($userData);
	}

	public function user($userId)
	{
		return $this->zendesk()->users()->find($userId);
	}

	public function ticket($ticketId)
	{
		return $this->zendesk()->tickets()->sideload(['users'])->find($ticketId);
	}

	public function ticketComments($ticketId)
	{
		return $this->zendesk()->tickets($ticketId)->comments()->sideload(['users'])->findAll();
	}

	public function updateTicket($ticketId, $data)
	{
		return $this->zendesk()->tickets()->update($ticketId, $data);
	}

	/**
	 * @return \Zendesk\API\HttpClient
	 */
	protected function zendesk()
	{
		return app('zendesk');
	}
}

<?php

namespace App\Http\Controllers;

use App\TsTicket;
use Illuminate\Http\Request;

class ZendeskWebhookController extends Controller
{

	public function __invoke(Request $request)
	{
		switch ($request->event) {
			case 'ticket':
				$this->validate($request, [
					'ticket_id' => 'required|numeric',
				]);
				/** @var \App\TsTicket $ticket */
				if ($ticket = TsTicket::query()->where('zendesk_id', $request->ticket_id)->first()) {
					zendeskSync()->syncTicket($ticket);
				}
				break;
		}
	}
}

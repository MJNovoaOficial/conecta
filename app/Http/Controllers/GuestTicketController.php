<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redirect;

class GuestTicketController extends Controller
{
    /**
     * Show a ticket to a guest using the UUID token.
     */
    public function show(string $token)
    {
        $ticket = Ticket::where('guest_token', $token)->firstOrFail();
        // No authentication required, just display the ticket details view
        return view('tickets.guest_show', compact('ticket'));
    }
}
?>

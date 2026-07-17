<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Devuelve las notificaciones no leídas del usuario autenticado (para el navbar).
     */
    public function index()
    {
        $notificaciones = Notificacion::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $unreadCount = Notificacion::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return view('notifications.index', compact('notificaciones', 'unreadCount'));
    }

    /**
     * Endpoint AJAX: devuelve el count de no leídas (para el badge del navbar).
     */
    public function count()
    {
        $count = Notificacion::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Marca una notificación como leída y redirige al ticket.
     */
    public function markRead(Notificacion $notificacion)
    {
        if ($notificacion->user_id !== Auth::id()) {
            abort(403);
        }

        $notificacion->update(['read_at' => now()]);

        if ($notificacion->ticket_id) {
            return redirect()->route('tickets.show', $notificacion->ticket_id);
        }

        return back();
    }

    /**
     * Marca todas como leídas.
     */
    public function markAllRead()
    {
        Notificacion::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Todas las notificaciones marcadas como leídas.');
    }

    /**
     * Devuelve las últimas 5 notificaciones en JSON (para el dropdown del navbar).
     */
    public function recent()
    {
        $items = Notificacion::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($n) {
                return [
                    'id'        => $n->id,
                    'title'     => $n->title,
                    'body'      => $n->body,
                    'type'      => $n->type,
                    'ticket_id' => $n->ticket_id,
                    'read'      => !is_null($n->read_at),
                    'time'      => $n->created_at->diffForHumans(),
                    'url'       => $n->ticket_id ? route('notifications.read', $n->id) : '#',
                ];
            });

        return response()->json([
            'items'       => $items,
            'unread_count' => Notificacion::where('user_id', Auth::id())->whereNull('read_at')->count(),
        ]);
    }
}

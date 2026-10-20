<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketDelegation;
use App\Support\TicketDelegationAccess;
use App\Support\TicketDelegationWorkflow;
use Illuminate\Http\Request;

class TicketDelegationController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        abort_unless(TicketDelegationAccess::available(), 404);
        $this->authorize('delegate', $ticket);
        $data = $this->data($request, [
            'delegate_id' => 'required|integer|min:1', 'instructions' => 'required|string|min:10|max:2000',
            'expected_id' => 'required|integer|min:0', 'version' => 'required|integer|min:0',
        ]);
        TicketDelegationWorkflow::grant($ticket, $request->user(), $data);
        return back()->with('success', 'Encargo registrado. TI conserva la responsabilidad y revisará la propuesta de solución.');
    }

    public function revoke(Request $request, Ticket $ticket, string $delegation)
    {
        abort_unless(TicketDelegationAccess::available(), 404);
        $this->authorize('revokeDelegation', $ticket);
        $data = $this->data($request, ['version' => 'required|integer|min:1']);
        TicketDelegationWorkflow::revoke($ticket, $this->row($ticket, $delegation), $request->user(), (int) $data['version']);
        return back()->with('success', 'Encargo retirado. Se conserva la actividad y TI sigue como responsable.');
    }

    public function propose(Request $request, Ticket $ticket, string $delegation)
    {
        abort_unless(TicketDelegationAccess::available(), 404);
        $this->authorize('proposeDelegation', $ticket);
        $data = $this->data($request, ['version' => 'required|integer|min:1', 'solution_text' => 'required|string|min:10|max:5000']);
        TicketDelegationWorkflow::propose($ticket, $this->row($ticket, $delegation), $request->user(), $data);
        return back()->with('success', 'Propuesta enviada para revisión de TI. El ticket aún no está Resuelto.');
    }

    public function changes(Request $request, Ticket $ticket, string $delegation)
    {
        abort_unless(TicketDelegationAccess::available(), 404);
        $this->authorize('delegate', $ticket);
        $data = $this->data($request, ['version' => 'required|integer|min:1', 'feedback' => 'required|string|min:10|max:2000']);
        TicketDelegationWorkflow::requestChanges($ticket, $this->row($ticket, $delegation), $request->user(), $data);
        return back()->with('success', 'Ajustes solicitados. El caso sigue en atención, sin iniciar el cierre automático.');
    }

    private function data(Request $request, array $rules): array
    {
        return $request->validate($rules, [
            'required' => 'Completa el campo :attribute.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute debe ser un número entero válido.',
            'min.string' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max.string' => 'El campo :attribute no puede superar :max caracteres.',
            'min.numeric' => 'El campo :attribute debe ser al menos :min.',
            'delegate_id.required' => 'Selecciona la persona encargada.',
            'version.required' => 'Actualiza la página antes de enviar este formulario.',
        ], [
            'delegate_id' => 'persona encargada', 'instructions' => 'indicaciones del encargo',
            'solution_text' => 'propuesta de solución', 'feedback' => 'ajustes solicitados',
            'version' => 'versión del encargo', 'expected_id' => 'identificador del encargo',
        ]);
    }

    private function row(Ticket $ticket, string $id): TicketDelegation
    {
        // Resolver después de comprobar disponibilidad, no en el binding:
        // el código puede desplegarse con la tabla aún pendiente de crear.
        return TicketDelegation::where('ticket_id', $ticket->id)->whereKey($id)->firstOrFail();
    }
}

<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\PriorityRule;
use Carbon\Carbon;

class PriorityResolverService
{
    /**
     * Resolve and assign priority to a ticket based on priority_rules.
     */
    public function resolve(Ticket $ticket): void
    {
        // Find a matching rule (first match)
        $rule = PriorityRule::where(function ($q) use ($ticket) {
                $q->where('categoria_id', $ticket->categoria_id)
                  ->orWhereNull('categoria_id');
            })
            ->where(function ($q) use ($ticket) {
                $q->where('subcategoria_id', $ticket->subcategoria_id)
                  ->orWhereNull('subcategoria_id');
            })
            ->where(function ($q) use ($ticket) {
                $q->where('tipo_incidente_id', $ticket->tipo_incidente_id)
                  ->orWhereNull('tipo_incidente_id');
            })
            ->orderBy('priority_level', 'desc') // highest priority first
            ->first();

        if ($rule) {
            $ticket->priority = $rule->priority_level;
        }
    }
}
?>

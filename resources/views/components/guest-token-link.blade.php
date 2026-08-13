<!-- resources/views/components/guest-token-link.blade.php -->
@php
    /**
     * Expected variables:
     *  $ticket – Ticket model instance
     *  $guestToken – string token for guest access
     */
@endphp
<div class="guest-token-link my-4 text-center">
    <a href="{{ route('tickets.guest.show', ['token' => $guestToken]) }}"
       class="inline-block bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium py-2 px-4 rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-200"
       style="font-family: 'Inter', sans-serif;">
        Ver Ticket como Invitado
    </a>
</div>

<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = SupportTicket::with(['user', 'assignedTo', 'latestMessage.user'])
            ->where('conjunto_config_id', $user->conjunto_config_id);

        if (! $user->hasRole(['super-admin', 'administrador'])) {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $tickets = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Support/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'priority', 'category']),
            'canManage' => $user->hasRole(['super-admin', 'administrador']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Support/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'required|in:technical,billing,general,feature_request,bug_report',
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($request, $user) {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'conjunto_config_id' => $user->conjunto_config_id,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'category' => $request->category,
                'status' => 'open',
            ]);

            SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $request->description,
                'is_admin_reply' => false,
            ]);

            $this->notificationService->notifyByRole('administrador',
                new \App\Notifications\SupportTicketCreated($ticket)
            );
        });

        return redirect()->route('support.index')
            ->with('success', 'Ticket de soporte creado exitosamente');
    }

    public function show(SupportTicket $supportTicket)
    {
        $user = Auth::user();

        if (! $user->hasRole(['super-admin', 'administrador']) && $supportTicket->user_id !== $user->id) {
            abort(403);
        }

        $supportTicket->load([
            'user',
            'assignedTo',
            'messages' => function ($query) {
                $query->with('user')->orderBy('created_at');
            },
        ]);

        $supportTicket->messages()
            ->where('user_id', '!=', $user->id)
            ->unread()
            ->get()
            ->each->markAsRead();

        return Inertia::render('Support/Show', [
            'ticket' => $supportTicket,
            'canManage' => $user->hasRole(['super-admin', 'administrador']),
        ]);
    }

    public function update(Request $request, SupportTicket $supportTicket)
    {
        $user = Auth::user();

        if (! $user->hasRole(['super-admin', 'administrador'])) {
            abort(403);
        }

        $request->validate([
            'status' => 'sometimes|in:open,in_progress,resolved,closed',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'assigned_to' => 'sometimes|nullable|exists:users,id',
        ]);

        $supportTicket->update($request->only(['status', 'priority', 'assigned_to']));

        if ($request->has('status') && in_array($request->status, ['resolved', 'closed'])) {
            $supportTicket->update(['resolved_at' => now()]);
        }

        return back()->with('success', 'Ticket actualizado exitosamente');
    }

    public function addMessage(Request $request, SupportTicket $supportTicket)
    {
        $user = Auth::user();

        if (! $user->hasRole(['super-admin', 'administrador']) && $supportTicket->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        $isAdminReply = $user->hasRole(['super-admin', 'administrador']);

        $message = SupportMessage::create([
            'support_ticket_id' => $supportTicket->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'is_admin_reply' => $isAdminReply,
        ]);

        if ($isAdminReply && $supportTicket->status === 'open') {
            $supportTicket->update(['status' => 'in_progress']);
        }

        $targetUser = $isAdminReply ? $supportTicket->user : null;
        if ($targetUser) {
            $this->notificationService->notifyUser($targetUser,
                new \App\Notifications\SupportMessageReceived($supportTicket, $message)
            );
        } else {
            $this->notificationService->notifyByRole('administrador',
                new \App\Notifications\SupportMessageReceived($supportTicket, $message)
            );
        }

        return back()->with('success', 'Mensaje enviado exitosamente');
    }

    public function reopen(SupportTicket $supportTicket)
    {
        $user = Auth::user();

        if ($supportTicket->user_id !== $user->id && ! $user->hasRole(['super-admin', 'administrador'])) {
            abort(403);
        }

        if (! $supportTicket->canBeReopened()) {
            return back()->with('error', 'Este ticket no puede ser reabierto');
        }

        $supportTicket->reopen();

        return back()->with('success', 'Ticket reabierto exitosamente');
    }
}

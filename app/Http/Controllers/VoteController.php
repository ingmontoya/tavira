<?php

namespace App\Http\Controllers;

use App\Events\VoteCast;
use App\Models\Assembly;
use App\Models\Apartment;
use App\Models\ApartmentVote;
use App\Models\Vote;
use App\Models\VoteDelegate;
use App\Models\VoteOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VoteController extends Controller
{
    public function index(Request $request, Assembly $assembly)
    {
        $votes = $assembly->votes()
            ->with(['options', 'apartmentVotes'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->orderBy('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Votes/Index', [
            'assembly' => $assembly,
            'votes' => $votes,
            'filters' => $request->only(['status', 'type']),
        ]);
    }

    public function create(Assembly $assembly)
    {
        if ($assembly->status === 'closed') {
            return redirect()->route('assemblies.show', $assembly)
                ->with('error', 'No se pueden crear votaciones en asambleas cerradas.');
        }

        return Inertia::render('Votes/Create', [
            'assembly' => $assembly,
        ]);
    }

    public function store(Request $request, Assembly $assembly)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:yes_no,multiple_choice,quantitative',
            'opens_at' => 'nullable|date|after:now',
            'closes_at' => 'nullable|date|after:opens_at',
            'required_quorum_percentage' => 'required|integer|min:1|max:100',
            'required_approval_percentage' => 'required|integer|min:1|max:100',
            'allows_abstention' => 'boolean',
            'options' => 'required_if:type,multiple_choice|array|min:2',
            'options.*.title' => 'required_with:options|string|max:255',
            'options.*.description' => 'nullable|string',
            'options.*.value' => 'nullable|numeric',
            'metadata' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $assembly) {
            $vote = $assembly->votes()->create([
                ...$validated,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            if ($validated['type'] === 'multiple_choice' && !empty($validated['options'])) {
                foreach ($validated['options'] as $index => $optionData) {
                    $vote->options()->create([
                        ...$optionData,
                        'order' => $index,
                    ]);
                }
            }
        });

        return redirect()->route('assemblies.votes.index', $assembly)
            ->with('success', 'Votación creada exitosamente.');
    }

    public function show(Assembly $assembly, Vote $vote)
    {
        $vote->load(['options', 'apartmentVotes.apartment', 'apartmentVotes.castByUser']);
        
        $userApartment = Auth::user()->resident?->apartment;
        $userVote = null;
        $canVote = false;
        $delegateFor = [];

        if ($userApartment && $vote->can_vote) {
            $userVote = $vote->getUserVote($userApartment->id);
            $canVote = !$userVote;
        }

        // Check if user is a delegate for other apartments
        $activeDelegates = VoteDelegate::forAssembly($assembly->id)
            ->forDelegate(Auth::id())
            ->active()
            ->with('delegatorApartment')
            ->get();

        foreach ($activeDelegates as $delegate) {
            if (!$vote->hasUserVoted($delegate->delegator_apartment_id)) {
                $delegateFor[] = $delegate;
            }
        }

        return Inertia::render('Votes/Show', [
            'assembly' => $assembly,
            'vote' => $vote,
            'userApartment' => $userApartment,
            'userVote' => $userVote,
            'canVote' => $canVote,
            'delegateFor' => $delegateFor,
            'canManage' => Auth::user()->can('manage assemblies'),
        ]);
    }

    public function edit(Assembly $assembly, Vote $vote)
    {
        if ($vote->status !== 'draft') {
            return redirect()->route('assemblies.votes.show', [$assembly, $vote])
                ->with('error', 'Solo se pueden editar votaciones en borrador.');
        }

        $vote->load('options');

        return Inertia::render('Votes/Edit', [
            'assembly' => $assembly,
            'vote' => $vote,
        ]);
    }

    public function update(Request $request, Assembly $assembly, Vote $vote)
    {
        if ($vote->status !== 'draft') {
            return redirect()->route('assemblies.votes.show', [$assembly, $vote])
                ->with('error', 'Solo se pueden editar votaciones en borrador.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'opens_at' => 'nullable|date|after:now',
            'closes_at' => 'nullable|date|after:opens_at',
            'required_quorum_percentage' => 'required|integer|min:1|max:100',
            'required_approval_percentage' => 'required|integer|min:1|max:100',
            'allows_abstention' => 'boolean',
            'options' => 'required_if:type,multiple_choice|array|min:2',
            'options.*.title' => 'required_with:options|string|max:255',
            'options.*.description' => 'nullable|string',
            'options.*.value' => 'nullable|numeric',
            'metadata' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $vote) {
            $vote->update($validated);

            if ($vote->type === 'multiple_choice' && !empty($validated['options'])) {
                $vote->options()->delete();
                
                foreach ($validated['options'] as $index => $optionData) {
                    $vote->options()->create([
                        ...$optionData,
                        'order' => $index,
                    ]);
                }
            }
        });

        return redirect()->route('assemblies.votes.show', [$assembly, $vote])
            ->with('success', 'Votación actualizada exitosamente.');
    }

    public function destroy(Assembly $assembly, Vote $vote)
    {
        if ($vote->status === 'active') {
            return redirect()->route('assemblies.votes.show', [$assembly, $vote])
                ->with('error', 'No se puede eliminar una votación activa.');
        }

        $vote->delete();

        return redirect()->route('assemblies.votes.index', $assembly)
            ->with('success', 'Votación eliminada exitosamente.');
    }

    public function activate(Assembly $assembly, Vote $vote)
    {
        if (!$vote->activate()) {
            return redirect()->route('assemblies.votes.show', [$assembly, $vote])
                ->with('error', 'No se pudo activar la votación.');
        }

        return redirect()->route('assemblies.votes.show', [$assembly, $vote])
            ->with('success', 'Votación activada exitosamente.');
    }

    public function close(Assembly $assembly, Vote $vote)
    {
        if (!$vote->close()) {
            return redirect()->route('assemblies.votes.show', [$assembly, $vote])
                ->with('error', 'No se pudo cerrar la votación.');
        }

        return redirect()->route('assemblies.votes.show', [$assembly, $vote])
            ->with('success', 'Votación cerrada exitosamente.');
    }

    public function cast(Request $request, Assembly $assembly, Vote $vote)
    {
        if (!$vote->can_vote) {
            return response()->json([
                'message' => 'La votación no está disponible.',
            ], 422);
        }

        $validated = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'vote_option_id' => 'required_if:type,multiple_choice|nullable|exists:vote_options,id',
            'choice' => 'required_if:type,yes_no|nullable|in:yes,no,abstain',
            'quantitative_value' => 'required_if:type,quantitative|nullable|numeric|min:0',
            'on_behalf_of_apartment_id' => 'nullable|exists:apartments,id',
        ]);

        $apartmentId = $validated['apartment_id'];
        $onBehalfOf = $validated['on_behalf_of_apartment_id'] ?? null;

        // Verify user can vote for this apartment
        $canVote = $this->canUserVoteForApartment(Auth::id(), $apartmentId, $assembly->id, $onBehalfOf);

        if (!$canVote) {
            return response()->json([
                'message' => 'No tiene permisos para votar por este apartamento.',
            ], 403);
        }

        // Check if apartment already voted
        if ($vote->hasUserVoted($apartmentId)) {
            return response()->json([
                'message' => 'Este apartamento ya ha votado.',
            ], 422);
        }

        $apartment = Apartment::findOrFail($apartmentId);

        DB::transaction(function () use ($validated, $vote, $apartment, $onBehalfOf) {
            $voteData = [
                'vote_id' => $vote->id,
                'apartment_id' => $apartment->id,
                'weight' => 1.0000, // Could be based on apartment coefficient
                'cast_by_user_id' => Auth::id(),
                'on_behalf_of_user_id' => $onBehalfOf ? Auth::id() : null,
                'cast_at' => now(),
            ];

            if ($vote->type === 'yes_no') {
                $voteData['choice'] = $validated['choice'];
            } elseif ($vote->type === 'multiple_choice') {
                $voteData['vote_option_id'] = $validated['vote_option_id'];
            } elseif ($vote->type === 'quantitative') {
                $voteData['quantitative_value'] = $validated['quantitative_value'];
            }

            $apartmentVote = ApartmentVote::create($voteData);
            
            // Encrypt sensitive vote data
            $apartmentVote->encryptVoteData($validated);
            $apartmentVote->save();

            VoteCast::dispatch($apartmentVote, Auth::user());
        });

        return response()->json([
            'message' => 'Voto registrado exitosamente.',
            'participation_stats' => $vote->fresh()->participation_stats,
        ]);
    }

    private function canUserVoteForApartment(int $userId, int $apartmentId, int $assemblyId, ?int $onBehalfOf): bool
    {
        // Check if user is resident of the apartment
        if (Auth::user()->resident?->apartment_id === $apartmentId) {
            return true;
        }

        // Check if user is delegate for this apartment
        if ($onBehalfOf) {
            $delegate = VoteDelegate::forAssembly($assemblyId)
                ->forApartment($apartmentId)
                ->forDelegate($userId)
                ->active()
                ->first();

            return $delegate && $delegate->canVoteFor($apartmentId);
        }

        return false;
    }
}

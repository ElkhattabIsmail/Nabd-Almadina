<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegrouperSignalementsRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Models\Signalement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class IncidentController extends Controller
{
    /**
     * Display a listing of incidents.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Incident::with(['departement', 'signalements']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('departement_id')) {
            $query->where('departement_id', $request->departement_id);
        }

        $incidents = $query->latest()->paginate(15);

        return response()->json(IncidentResource::collection($incidents)->response()->getData(true));
    }

    /**
     * Store a newly created incident in storage (Agent only).
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Incident::class);

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:ouvert,en_cours,resolu,ferme'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'departement_id' => ['required', 'exists:departements,id'],
        ]);

        $incident = Incident::create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?? 'ouvert',
            'priority' => $request->priority ?? 'medium',
            'departement_id' => $request->departement_id,
        ]);

        return response()->json([
            'message' => 'Incident créé avec succès',
            'data' => new IncidentResource($incident->load(['departement', 'signalements'])),
        ], 201);
    }

    /**
     * Display the specified incident.
     */
    public function show(Incident $incident): JsonResponse
    {
        Gate::authorize('view', $incident);

        return response()->json([
            'data' => new IncidentResource($incident->load(['departement', 'signalements.user'])),
        ]);
    }

    /**
     * Validate AI proposal and group signalements into an Incident (Agent only).
     */
    public function regrouper(RegrouperSignalementsRequest $request): JsonResponse
    {
        Gate::authorize('regrouper', Incident::class);

        $signalementIds = $request->signalement_ids;
        $signalements = Signalement::whereIn('id', $signalementIds)->get();

        if ($signalements->isEmpty()) {
            return response()->json([
                'message' => 'Aucun signalement valide trouvé pour le regroupement.',
            ], 422);
        }

        $incident = null;

        if ($request->filled('incident_id')) {
            $incident = Incident::findOrFail($request->incident_id);
        } else {
            // Inherit department from first signalement or default to first department
            $firstDepartementId = $signalements->first()->departement_id ?? 1;

            $incident = Incident::create([
                'title' => $request->title ?? 'Incident groupé: ' . ($signalements->first()->summary ?? 'Problème urbain'),
                'description' => $request->description ?? 'Incident généré à partir du regroupement de ' . count($signalementIds) . ' signalements.',
                'status' => 'ouvert',
                'priority' => $request->priority ?? 'medium',
                'departement_id' => $firstDepartementId,
            ]);
        }

        // Attach signalements to the incident
        Signalement::whereIn('id', $signalementIds)->update([
            'incident_id' => $incident->id,
            'status' => 'en_cours',
        ]);

        return response()->json([
            'message' => 'Regroupement des signalements validé et rattaché à l\'incident avec succès',
            'data' => new IncidentResource($incident->load(['departement', 'signalements'])),
        ]);
    }

    /**
     * Update the specified incident in storage (Agent only).
     */
    public function update(Request $request, Incident $incident): JsonResponse
    {
        Gate::authorize('update', $incident);

        $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:ouvert,en_cours,resolu,ferme'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'departement_id' => ['sometimes', 'exists:departements,id'],
        ]);

        $incident->update($request->only([
            'title',
            'description',
            'status',
            'priority',
            'departement_id',
        ]));

        return response()->json([
            'message' => 'Incident mis à jour avec succès',
            'data' => new IncidentResource($incident->load(['departement', 'signalements'])),
        ]);
    }

    /**
     * Remove the specified incident from storage with referential integrity check.
     */
    public function destroy(Incident $incident): JsonResponse
    {
        Gate::authorize('delete', $incident);

        // Referential integrity check
        if ($incident->signalements()->count() > 0) {
            return response()->json([
                'message' => 'Intégrité référentielle respectée: Impossible de supprimer un incident contenant des signalements rattachés.',
            ], 422);
        }

        $incident->delete();

        return response()->json([
            'message' => 'Incident supprimé avec succès',
        ]);
    }
}

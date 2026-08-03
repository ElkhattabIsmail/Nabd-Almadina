<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignDepartementRequest;
use App\Http\Requests\StoreSignalementRequest;
use App\Http\Requests\UpdateSignalementStatusRequest;
use App\Http\Resources\SignalementResource;
use App\Models\Signalement;
use App\Services\SignalementAnalyzer;
use App\Services\SimilarityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class SignalementController extends Controller
{
    /**
     * Display a listing of signalements based on user role policy.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Signalement::with(['user', 'departement', 'incident']);

        if ($user->isCitoyen()) {
            $query->where('user_id', $user->id);
        } else if ($user->isAgent() && $user->departement_id && $request->boolean('department_only')) {
            $query->where('departement_id', $user->departement_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $signalements = $query->latest()->paginate(15);

        return response()->json(SignalementResource::collection($signalements)->response()->getData(true));
    }

    /**
     * Store a newly created signalement in storage and trigger AI classification.
     */
    public function store(StoreSignalementRequest $request, SignalementAnalyzer $analyzer): JsonResponse
    {
        $user = $request->user();

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('signalements', 'public');
        }

        // Create base signalement with user input
        $signalement = new Signalement([
            'user_id' => $user->id,
            'description' => $request->description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'photo' => $photoPath,
            'status' => 'nouveau',
        ]);

        // Call AI service to extract category, priority, urgency, summary, and department
        $aiData = $analyzer->analyze($request->description);

        $signalement->category = $aiData['category'];
        $signalement->priority = $aiData['priority'];
        $signalement->urgency = $aiData['urgency'];
        $signalement->summary = $aiData['summary'];
        $signalement->departement_id = $aiData['department_id'];

        $signalement->save();

        return response()->json([
            'message' => 'Signalement créé et analysé par l\'IA avec succès',
            'data' => new SignalementResource($signalement->load(['user', 'departement', 'incident'])),
        ], 201);
    }

    /**
     * Display the specified signalement.
     */
    public function show(Signalement $signalement): JsonResponse
    {
        Gate::authorize('view', $signalement);

        return response()->json([
            'data' => new SignalementResource($signalement->load(['user', 'departement', 'incident'])),
        ]);
    }

    /**
     * Update signalement status (Agent only).
     */
    public function updateStatus(UpdateSignalementStatusRequest $request, Signalement $signalement): JsonResponse
    {
        Gate::authorize('updateStatus', $signalement);

        $signalement->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Statut du signalement mis à jour avec succès',
            'data' => new SignalementResource($signalement->load(['user', 'departement', 'incident'])),
        ]);
    }

    /**
     * Assign signalement to a municipal department (Agent only).
     */
    public function assignDepartement(AssignDepartementRequest $request, Signalement $signalement): JsonResponse
    {
        Gate::authorize('assignDepartement', $signalement);

        $signalement->update([
            'departement_id' => $request->departement_id,
        ]);

        return response()->json([
            'message' => 'Département attribué au signalement avec succès',
            'data' => new SignalementResource($signalement->load(['user', 'departement', 'incident'])),
        ]);
    }

    /**
     * Remove the specified signalement from storage.
     */
    public function destroy(Signalement $signalement): JsonResponse
    {
        Gate::authorize('delete', $signalement);

        if ($signalement->photo) {
            Storage::disk('public')->delete($signalement->photo);
        }

        $signalement->delete();

        return response()->json([
            'message' => 'Signalement supprimé avec succès',
        ]);
    }

    /**
     * Detect potential duplicate signalements using AI & spatial comparison.
     */
    public function similaires(Signalement $signalement, SimilarityService $similarityService): JsonResponse
    {
        Gate::authorize('view', $signalement);

        $results = $similarityService->findDuplicates($signalement);

        return response()->json([
            'message' => 'Rapprochement des signalements similaires exécuté par l\'IA',
            'data' => $results,
        ]);
    }
}

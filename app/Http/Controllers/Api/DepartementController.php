<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartementResource;
use App\Models\Departement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartementController extends Controller
{
    /**
     * Display a listing of municipal departments.
     */
    public function index(): JsonResponse
    {
        $departements = Departement::withCount('signalements')->get();

        return response()->json([
            'data' => DepartementResource::collection($departements),
        ]);
    }

    /**
     * Display the specified department.
     */
    public function show(Departement $departement): JsonResponse
    {
        return response()->json([
            'data' => new DepartementResource($departement->loadCount('signalements')),
        ]);
    }
}

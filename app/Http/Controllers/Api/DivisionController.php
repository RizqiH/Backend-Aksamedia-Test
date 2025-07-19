<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DivisionResource;
use App\Models\Division;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of divisions with optional filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Division::query();

            // Apply search filter
            if ($request->filled('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            $divisions = $query->paginate(10);

            return $this->successWithPagination($divisions, 'Divisions retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve divisions', 500);
        }
    }
}

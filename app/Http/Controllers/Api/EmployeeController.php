<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of employees with optional filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Employee::with('division');

            // Apply search filter - check for both 'name' and 'search' parameters
            if ($request->filled('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            } elseif ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Apply division filter - check for both parameter names
            if ($request->filled('division_id')) {
                $query->where('division_id', $request->division_id);
            } elseif ($request->filled('division')) {
                $query->where('division_id', $request->division);
            }

            // Get pagination parameters
            $perPage = $request->get('per_page', 10);
            $perPage = min(50, max(1, (int)$perPage)); // Limit between 1-50

            $employees = $query->paginate($perPage);

            return $this->successWithPagination($employees, 'Employees retrieved successfully', 'employees');
        } catch (\Exception $e) {
            \Log::error('Employee index error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve employees: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created employee
     */
    public function store(EmployeeRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $data = [
                'name' => $validatedData['name'],
                'phone' => $validatedData['phone'],
                'division_id' => $validatedData['division'],
                'position' => $validatedData['position'],
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('employees', 'public');
                $data['image'] = $imagePath;
            }

            $employee = Employee::create($data);

            // Load relationship for response
            $employee->load('division');

            return $this->createdResponse(
                new EmployeeResource($employee),
                'Employee created successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create employee', 500);
        }
    }

    /**
     * Update the specified employee
     */
    public function update(EmployeeRequest $request, Employee $employee): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            $data = [
                'name' => $validatedData['name'],
                'phone' => $validatedData['phone'],
                'division_id' => $validatedData['division'],
                'position' => $validatedData['position'],
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($employee->image && Storage::disk('public')->exists($employee->image)) {
                    Storage::disk('public')->delete($employee->image);
                }

                $imagePath = $request->file('image')->store('employees', 'public');
                $data['image'] = $imagePath;
            }

            $employee->update($data);

            // Load relationship for response
            $employee->load('division');

            return $this->updatedResponse(
                new EmployeeResource($employee),
                'Employee updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update employee', 500);
        }
    }

    /**
     * Remove the specified employee
     */
    public function destroy(Employee $employee): JsonResponse
    {
        try {
            // Delete associated image
            if ($employee->image && Storage::disk('public')->exists($employee->image)) {
                Storage::disk('public')->delete($employee->image);
            }

            $employee->delete();

            return $this->deletedResponse('Employee deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete employee', 500);
        }
    }
}

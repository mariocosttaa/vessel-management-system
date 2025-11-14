<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HashesIds;
use App\Http\Requests\StoreMovimentationCategoryRequest;
use App\Models\MovimentationCategory;
use Illuminate\Support\Facades\Log;

class MovimentationCategoryController extends Controller
{
    use HashesIds;

    /**
     * Store a newly created category for the vessel.
     */
    public function store(StoreMovimentationCategoryRequest $request)
    {
        try {
            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            /** @var int $vesselId */
            $vesselId = $request->attributes->get('vessel_id');
            if (! $vesselId) {
                // Fallback to route parameter if attributes not set
                $vesselParam = $request->route('vessel');
                if (is_object($vesselParam)) {
                    $vesselId = $vesselParam->id;
                } else {
                    // Try to unhash if it's a hashed string
                    $vessel = (new \App\Models\Vessel())->resolveRouteBinding($vesselParam);
                    if (! $vessel) {
                        abort(404, 'Vessel not found.');
                    }
                    $vesselId = $vessel->id;
                }
            }

            // Generate a default color if not provided
            $color = $request->input('color') ?? $this->generateRandomColor();

            // Create the category
            $category = MovimentationCategory::create([
                'name'        => $request->input('name'),
                'type'        => $request->input('type'),
                'color'       => $color,
                'description' => $request->input('description'),
                'vessel_id'   => $vesselId,
                'is_system'   => false, // Custom categories are not system categories
            ]);

            // Return the category with hashed ID as JSON response
            return response()->json([
                'category' => [
                    'id'          => $this->hashId($category->id, 'transactioncategory'),
                    'name'        => $category->translated_name,
                    'type'        => $category->type,
                    'color'       => $category->color,
                    'description' => $category->description,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create category', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create category. Please try again.',
            ], 500);
        }
    }

    /**
     * Generate a random color for the category.
     */
    private function generateRandomColor(): string
    {
        // Generate a random hex color
        $colors = [
            '#ef4444', '#f59e0b', '#eab308', '#84cc16', '#22c55e',
            '#10b981', '#14b8a6', '#06b6d4', '#3b82f6', '#6366f1',
            '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e',
        ];

        return $colors[array_rand($colors)];
    }
}

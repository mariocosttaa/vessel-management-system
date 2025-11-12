<?php

namespace App\Http\Controllers;

use App\Models\MareaDistributionProfile;
use App\Models\MareaDistributionProfileItem;
use App\Actions\AuditLogAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MareaDistributionProfileController extends Controller
{
    /**
     * Display a listing of distribution profiles.
     */
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Get profiles for current user's vessels (or all if admin)
        $profiles = MareaDistributionProfile::with(['items' => function ($query) {
            $query->orderBy('order_index');
        }])
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return Inertia::render('MareaDistributionProfiles/Index', [
            'profiles' => $profiles->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'description' => $profile->description,
                    'is_default' => $profile->is_default,
                    'is_system' => $profile->is_system,
                    'items_count' => $profile->items->count(),
                    'created_by' => $profile->createdBy ? [
                        'id' => $profile->createdBy->id,
                        'name' => $profile->createdBy->name,
                    ] : null,
                    'created_at' => $profile->created_at ? $profile->created_at->format('Y-m-d H:i:s') : null,
                ];
            }),
        ]);
    }

    /**
     * Show the form for creating a new distribution profile.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('MareaDistributionProfiles/Create');
    }

    /**
     * Store a newly created distribution profile.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.order_index' => 'required|integer|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.value_type' => 'required|in:base_total_income,base_total_expense,fixed_amount,percentage_of_income,percentage_of_expense,reference_item',
            'items.*.value_amount' => 'nullable|numeric',
            'items.*.reference_item_id' => 'nullable|integer',
            'items.*.reference_item_order_index' => 'nullable|integer', // Alternative: use order_index
            'items.*.operation' => 'required|in:set,add,subtract,multiply,divide',
            'items.*.reference_operation_item_id' => 'nullable|integer',
            'items.*.reference_operation_item_order_index' => 'nullable|integer', // Alternative: use order_index
        ]);

        try {
            DB::beginTransaction();

            // If this is set as default, unset other defaults
            if ($validated['is_default'] ?? false) {
                MareaDistributionProfile::where('is_default', true)->update(['is_default' => false]);
            }

            // Create profile
            $profile = MareaDistributionProfile::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'is_system' => false,
                'created_by' => $user->id,
            ]);

            // Create items in two passes: first create all, then update references
            $createdItems = [];
            $orderToIdMap = [];

            // First pass: create all items
            foreach ($validated['items'] as $itemData) {
                $item = MareaDistributionProfileItem::create([
                    'distribution_profile_id' => $profile->id,
                    'order_index' => $itemData['order_index'],
                    'name' => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'value_type' => $itemData['value_type'],
                    'value_amount' => $itemData['value_amount'] ?? null,
                    'reference_item_id' => null, // Will be set in second pass
                    'operation' => $itemData['operation'],
                    'reference_operation_item_id' => null, // Will be set in second pass
                ]);
                $createdItems[] = ['item' => $item, 'data' => $itemData];
                $orderToIdMap[$itemData['order_index']] = $item->id;
            }

            // Second pass: update references using order_index mapping
            foreach ($createdItems as $createdItemData) {
                $item = $createdItemData['item'];
                $data = $createdItemData['data'];
                $updates = [];

                // Map reference_item_id from order_index (preferred) or use direct ID
                if (isset($data['reference_item_order_index']) && $data['reference_item_order_index']) {
                    $refOrderIndex = $data['reference_item_order_index'];
                    if (isset($orderToIdMap[$refOrderIndex])) {
                        $updates['reference_item_id'] = $orderToIdMap[$refOrderIndex];
                    }
                } elseif (isset($data['reference_item_id']) && $data['reference_item_id']) {
                    // Direct ID (shouldn't happen on create, but handle it)
                    if (isset($orderToIdMap[$data['reference_item_id']])) {
                        $updates['reference_item_id'] = $orderToIdMap[$data['reference_item_id']];
                    }
                }

                // Map reference_operation_item_id from order_index (preferred) or use direct ID
                if (isset($data['reference_operation_item_order_index']) && $data['reference_operation_item_order_index']) {
                    $refOrderIndex = $data['reference_operation_item_order_index'];
                    if (isset($orderToIdMap[$refOrderIndex])) {
                        $updates['reference_operation_item_id'] = $orderToIdMap[$refOrderIndex];
                    }
                } elseif (isset($data['reference_operation_item_id']) && $data['reference_operation_item_id']) {
                    // Direct ID (shouldn't happen on create, but handle it)
                    if (isset($orderToIdMap[$data['reference_operation_item_id']])) {
                        $updates['reference_operation_item_id'] = $orderToIdMap[$data['reference_operation_item_id']];
                    }
                }

                if (!empty($updates)) {
                    $item->update($updates);
                }
            }

            DB::commit();

            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            $vesselId = $request->attributes->get('vessel_id');

            // Log the create action
            AuditLogAction::logCreate(
                $profile,
                'Distribution Profile',
                $profile->name,
                null // Distribution profiles are not vessel-scoped (they're global entities)
            );

            return redirect()
                ->route('panel.marea-distribution-profiles.index', ['vessel' => $vesselId])
                ->with('success', 'Distribution profile created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create distribution profile: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified distribution profile.
     */
    public function show(Request $request, $id): Response
    {
        // Get ID from route parameter to avoid parameter binding conflicts
        $id = (int) $request->route('id');

        $profile = MareaDistributionProfile::with(['items' => function ($query) {
            $query->orderBy('order_index');
        }])->findOrFail($id);

        return Inertia::render('MareaDistributionProfiles/Show', [
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'description' => $profile->description,
                'is_default' => $profile->is_default,
                'is_system' => $profile->is_system,
                'items' => $profile->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'order_index' => $item->order_index,
                        'name' => $item->name,
                        'description' => $item->description,
                        'value_type' => $item->value_type,
                        'value_amount' => $item->value_amount,
                        'reference_item_id' => $item->reference_item_id,
                        'operation' => $item->operation,
                        'reference_operation_item_id' => $item->reference_operation_item_id,
                    ];
                }),
                'created_by' => $profile->createdBy ? [
                    'id' => $profile->createdBy->id,
                    'name' => $profile->createdBy->name,
                ] : null,
                'created_at' => $profile->created_at ? $profile->created_at->format('Y-m-d H:i:s') : null,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified distribution profile.
     */
    public function edit(Request $request, $id): Response
    {
        // Get ID from route parameter to avoid parameter binding conflicts
        $id = (int) $request->route('id');

        $profile = MareaDistributionProfile::with(['items' => function ($query) {
            $query->orderBy('order_index');
        }])->findOrFail($id);

        // System profiles cannot be edited
        if ($profile->is_system) {
            abort(403, 'System profiles cannot be edited.');
        }

        return Inertia::render('MareaDistributionProfiles/Edit', [
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'description' => $profile->description,
                'is_default' => $profile->is_default,
                'is_system' => $profile->is_system,
                'items' => $profile->items->map(function ($item) use ($profile) {
                    return [
                        'id' => $item->id,
                        'order_index' => $item->order_index,
                        'name' => $item->name,
                        'description' => $item->description,
                        'value_type' => $item->value_type,
                        'value_amount' => $item->value_amount,
                        'reference_item_id' => $item->reference_item_id,
                        'operation' => $item->operation,
                        'reference_operation_item_id' => $item->reference_operation_item_id,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the specified distribution profile.
     */
    public function update(Request $request, $id)
    {
        // Get ID from route parameter to avoid parameter binding conflicts
        $id = (int) $request->route('id');
        $profile = MareaDistributionProfile::findOrFail($id);

        // System profiles cannot be updated
        if ($profile->is_system) {
            abort(403, 'System profiles cannot be updated.');
        }

        // Store original state for change detection
        $originalProfile = $profile->replicate();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.order_index' => 'required|integer|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.value_type' => 'required|in:base_total_income,base_total_expense,fixed_amount,percentage_of_income,percentage_of_expense,reference_item',
            'items.*.value_amount' => 'nullable|numeric',
            'items.*.reference_item_id' => 'nullable|integer',
            'items.*.operation' => 'required|in:set,add,subtract,multiply,divide',
            'items.*.reference_operation_item_id' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            // If this is set as default, unset other defaults
            if ($validated['is_default'] ?? false) {
                MareaDistributionProfile::where('is_default', true)
                    ->where('id', '!=', $profile->id)
                    ->update(['is_default' => false]);
            }

            // Update profile
            $profile->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
            ]);

            // Store old items to map IDs by order_index before deletion
            $oldItems = $profile->items()->orderBy('order_index')->get()->keyBy('order_index');

            // Delete existing items
            $profile->items()->delete();

            // Create new items and build order_index to ID map
            $orderToIdMap = [];
            $createdItems = [];

            foreach ($validated['items'] as $itemData) {
                // Determine reference_item_id: if reference_item_order_index is provided, use it
                // Otherwise, try to use reference_item_id if it exists
                $referenceItemId = null;
                if (isset($itemData['reference_item_order_index']) && $itemData['reference_item_order_index']) {
                    // Map will be populated after creation
                    $referenceItemId = $itemData['reference_item_order_index']; // Temporary: order_index
                } elseif (isset($itemData['reference_item_id']) && $itemData['reference_item_id']) {
                    // Try to find the old item by order_index and use its mapping
                    // This won't work since we deleted them, so we need order_index approach
                    $referenceItemId = null; // Will be set in second pass
                }

                $referenceOperationItemId = null;
                if (isset($itemData['reference_operation_item_order_index']) && $itemData['reference_operation_item_order_index']) {
                    $referenceOperationItemId = $itemData['reference_operation_item_order_index']; // Temporary: order_index
                } elseif (isset($itemData['reference_operation_item_id']) && $itemData['reference_operation_item_id']) {
                    $referenceOperationItemId = null; // Will be set in second pass
                }

                $item = MareaDistributionProfileItem::create([
                    'distribution_profile_id' => $profile->id,
                    'order_index' => $itemData['order_index'],
                    'name' => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'value_type' => $itemData['value_type'],
                    'value_amount' => $itemData['value_amount'] ?? null,
                    'reference_item_id' => null, // Will be updated in second pass
                    'operation' => $itemData['operation'],
                    'reference_operation_item_id' => null, // Will be updated in second pass
                ]);

                $orderToIdMap[$itemData['order_index']] = $item->id;
                $createdItems[] = [
                    'item' => $item,
                    'data' => $itemData,
                ];
            }

            // Second pass: update references using order_index mapping
            foreach ($createdItems as $createdItemData) {
                $item = $createdItemData['item'];
                $data = $createdItemData['data'];
                $updates = [];

                // Map reference_item_id from order_index
                if (isset($data['reference_item_order_index']) && $data['reference_item_order_index']) {
                    $refOrderIndex = $data['reference_item_order_index'];
                    if (isset($orderToIdMap[$refOrderIndex])) {
                        $updates['reference_item_id'] = $orderToIdMap[$refOrderIndex];
                    }
                } elseif (isset($data['reference_item_id']) && $data['reference_item_id']) {
                    // Legacy: try to find by old order_index (if frontend sends old ID, we can't map it)
                    // Skip for now - frontend should send order_index
                }

                // Map reference_operation_item_id from order_index
                if (isset($data['reference_operation_item_order_index']) && $data['reference_operation_item_order_index']) {
                    $refOrderIndex = $data['reference_operation_item_order_index'];
                    if (isset($orderToIdMap[$refOrderIndex])) {
                        $updates['reference_operation_item_id'] = $orderToIdMap[$refOrderIndex];
                    }
                } elseif (isset($data['reference_operation_item_id']) && $data['reference_operation_item_id']) {
                    // Legacy: skip
                }

                if (!empty($updates)) {
                    $item->update($updates);
                }
            }

            DB::commit();

            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            $vesselId = $request->attributes->get('vessel_id');

            // Get changed fields and log the update action
            $changedFields = AuditLogAction::getChangedFields($profile, $originalProfile);
            AuditLogAction::logUpdate(
                $profile,
                $changedFields,
                'Distribution Profile',
                $profile->name,
                null // Distribution profiles are not vessel-scoped (they're global entities)
            );

            return redirect()
                ->route('panel.marea-distribution-profiles.index', ['vessel' => $vesselId])
                ->with('success', 'Distribution profile updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update distribution profile: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified distribution profile.
     */
    public function destroy(Request $request, $id)
    {
        // Get ID from route parameter to avoid parameter binding conflicts
        $id = (int) $request->route('id');
        $profile = MareaDistributionProfile::findOrFail($id);

        // System profiles cannot be deleted
        if ($profile->is_system) {
            abort(403, 'System profiles cannot be deleted.');
        }

        // Check if profile is in use
        $inUse = \App\Models\Marea::where('distribution_profile_id', $profile->id)->exists();
        if ($inUse) {
            return back()
                ->with('error', 'Cannot delete profile that is in use by one or more mareas.');
        }

        try {
            $profileName = $profile->name;

            // Log the delete action BEFORE deletion
            AuditLogAction::logDelete(
                $profile,
                'Distribution Profile',
                $profileName,
                null // Distribution profiles are not vessel-scoped (they're global entities)
            );

            $profile->delete();

            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            $vesselId = $request->attributes->get('vessel_id');

            return redirect()
                ->route('panel.marea-distribution-profiles.index', ['vessel' => $vesselId])
                ->with('success', 'Distribution profile deleted successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete distribution profile: ' . $e->getMessage());
        }
    }
}


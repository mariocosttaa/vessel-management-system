<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Actions\AuditLogAction;
use App\Traits\HasTranslations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    use HasTranslations;
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id');

        // Check if user has permission to view suppliers (moderator and administrator only)
        // Normal users should not have access to this page
        if (!$user || !$user->hasVesselPermission($vesselId, 'edit_vessel_basic')) {
            abort(403, $this->transFrom('notifications', 'You do not have permission to perform this action.'));
        }

        $query = Supplier::query()->where('vessel_id', $vesselId);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $suppliers = $query->paginate(15)
                          ->withQueryString();

        // Transform the data manually without using JsonResource
        $suppliers->through(function ($supplier) {
            return (new SupplierResource($supplier))->resolve();
        });

        return inertia('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function create(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id');

        // Check if user has permission to view suppliers (moderator and administrator only)
        if (!$user || !$user->hasVesselPermission($vesselId, 'edit_vessel_basic')) {
            abort(403, 'You do not have permission to view suppliers.');
        }

        return inertia('Suppliers/Create');
    }

    public function store(StoreSupplierRequest $request)
    {
        try {
            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            /** @var \Illuminate\Http\Request $request */
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id');

            $supplier = Supplier::create([
                'company_name' => $request->company_name,
                'description' => $request->description,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'notes' => $request->notes,
                'vessel_id' => $vesselId,
            ]);

            // Log the create action
            AuditLogAction::logCreate(
                $supplier,
                'Supplier',
                $supplier->company_name,
                $vesselId
            );

            return redirect()
                ->route('panel.suppliers.index', ['vessel' => $vesselId])
                ->with('success', $this->transFrom('notifications', "Supplier ':name' has been created successfully.", [
                    'name' => $supplier->company_name
                ]))
                ->with('notification_delay', 3); // 3 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to create supplier. Please try again.'))
                ->with('notification_delay', 0); // Persistent error (0 = no auto-dismiss)
        }
    }

    public function show(Request $request, $vessel, Supplier $supplier)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id');

        // Check if user has permission to view suppliers (moderator and administrator only)
        if (!$user || !$user->hasVesselPermission($vesselId, 'edit_vessel_basic')) {
            abort(403, 'You do not have permission to view suppliers.');
        }

        return inertia('Suppliers/Show', [
            'supplier' => new SupplierResource($supplier),
        ]);
    }

    public function edit(Request $request, $vessel, Supplier $supplier)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id');

        // Check if user has permission to view suppliers (moderator and administrator only)
        if (!$user || !$user->hasVesselPermission($vesselId, 'edit_vessel_basic')) {
            abort(403, 'You do not have permission to view suppliers.');
        }

        return inertia('Suppliers/Edit', [
            'supplier' => new SupplierResource($supplier),
        ]);
    }

    public function update(UpdateSupplierRequest $request, $vessel, Supplier $supplier)
    {
        try {
            // Verify supplier belongs to current vessel
            /** @var \Illuminate\Http\Request $request */
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id');
            if ($supplier->vessel_id !== $vesselId) {
                abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
            }

            // Store original state for change detection
            $originalSupplier = $supplier->replicate();

            // Access validated values directly as properties (never use validated())
            $supplier->update([
                'company_name' => $request->company_name,
                'description' => $request->description,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'notes' => $request->notes,
            ]);

            // Get changed fields and log the update action
            $changedFields = AuditLogAction::getChangedFields($supplier, $originalSupplier);
            AuditLogAction::logUpdate(
                $supplier,
                $changedFields,
                'Supplier',
                $supplier->company_name,
                $vesselId
            );

            return redirect()
                ->route('panel.suppliers.index', ['vessel' => $vesselId])
                ->with('success', $this->transFrom('notifications', "Supplier ':name' has been updated successfully.", [
                    'name' => $supplier->company_name
                ]))
                ->with('notification_delay', 4); // 4 seconds delay
        } catch (\Exception $e) {
            // Log the exception for debugging
            Log::error('Supplier update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'supplier_id' => $supplier->id,
                'vessel_id' => $vesselId ?? null,
            ]);

            return back()
                ->withInput()
                ->with('error', $this->transFrom('notifications', 'Failed to update supplier. Please try again.'))
                ->with('notification_delay', 0); // Persistent error
        }
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        try {
            // Verify supplier belongs to current vessel
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id');
            if ($supplier->vessel_id !== $vesselId) {
                abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
            }

            // Check if supplier has transactions
            if ($supplier->transactions()->count() > 0) {
                return back()->with('error', $this->transFrom('notifications', "Cannot delete supplier ':name' because they have transactions. Please remove all transactions first.", [
                    'name' => $supplier->company_name
                ]))
                    ->with('notification_delay', 0); // Persistent error
            }

            $supplierName = $supplier->company_name;

            // Log the delete action BEFORE deletion
            AuditLogAction::logDelete(
                $supplier,
                'Supplier',
                $supplierName,
                $vesselId
            );

            $supplier->delete();

            return redirect()
                ->route('panel.suppliers.index', ['vessel' => $vesselId])
                ->with('success', $this->transFrom('notifications', "Supplier ':name' has been deleted successfully.", [
                    'name' => $supplierName
                ]))
                ->with('notification_delay', 5); // 5 seconds delay
        } catch (\Exception $e) {
            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to delete supplier. Please try again.'))
                ->with('notification_delay', 0); // Persistent error
        }
    }

    public function search(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id');

        // Check if user has permission to view suppliers (moderator and administrator only)
        if (!$user || !$user->hasVesselPermission($vesselId, 'edit_vessel_basic')) {
            abort(403, 'You do not have permission to search suppliers.');
        }

        $query = $request->get('q');

        $suppliers = Supplier::where('vessel_id', $vesselId)
                            ->where(function ($q) use ($query) {
                                $q->where('company_name', 'like', "%{$query}%")
                                  ->orWhere('email', 'like', "%{$query}%")
                                  ->orWhere('phone', 'like', "%{$query}%");
                            })
                            ->limit(10)
                            ->get(['id', 'company_name', 'email', 'phone']);

        return response()->json($suppliers);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = (int) $request->attributes->get('vessel_id');

        $query = Supplier::query()->where('vessel_id', $vesselId);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
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

    public function create()
    {
        return inertia('Suppliers/Create');
    }

    public function store(StoreSupplierRequest $request)
    {
        try {
            // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id');

            $supplier = Supplier::create([
                'company_name' => $request->company_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'notes' => $request->notes,
                'vessel_id' => $vesselId,
            ]);

            return redirect()
                ->route('panel.suppliers.index', ['vessel' => $vesselId])
                ->with('success', "Supplier '{$supplier->company_name}' has been created successfully.")
                ->with('notification_delay', 3); // 3 seconds delay
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create supplier. Please try again.')
                ->with('notification_delay', 0); // Persistent error (0 = no auto-dismiss)
        }
    }

    public function show($vessel, Supplier $supplier)
    {
        return inertia('Suppliers/Show', [
            'supplier' => new SupplierResource($supplier),
        ]);
    }

    public function edit($vessel, Supplier $supplier)
    {
        return inertia('Suppliers/Edit', [
            'supplier' => new SupplierResource($supplier),
        ]);
    }

    public function update(UpdateSupplierRequest $request, $vessel, Supplier $supplier)
    {
        try {
            // Verify supplier belongs to current vessel
            /** @var int $vesselId */
            $vesselId = (int) $request->attributes->get('vessel_id');
            if ($supplier->vessel_id !== $vesselId) {
                abort(403, 'Unauthorized access to supplier.');
            }

            // Access validated values directly as properties (never use validated())
            $supplier->update([
                'company_name' => $request->company_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'notes' => $request->notes,
            ]);

            return redirect()
                ->route('panel.suppliers.index', ['vessel' => $vesselId])
                ->with('success', "Supplier '{$supplier->company_name}' has been updated successfully.")
                ->with('notification_delay', 4); // 4 seconds delay
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error('Supplier update failed: ' . $e->getMessage(), [
                'exception' => $e,
                'supplier_id' => $supplier->id,
                'vessel_id' => $vesselId ?? null,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update supplier. Please try again.')
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
                abort(403, 'Unauthorized access to supplier.');
            }

            // Check if supplier has transactions
            if ($supplier->transactions()->count() > 0) {
                return back()->with('error', "Cannot delete supplier '{$supplier->company_name}' because they have transactions. Please remove all transactions first.")
                    ->with('notification_delay', 0); // Persistent error
            }

            $supplierName = $supplier->company_name;
            $supplier->delete();

            return redirect()
                ->route('panel.suppliers.index', ['vessel' => $vesselId])
                ->with('success', "Supplier '{$supplierName}' has been deleted successfully.")
                ->with('notification_delay', 5); // 5 seconds delay
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete supplier. Please try again.')
                ->with('notification_delay', 0); // Persistent error
        }
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        $suppliers = Supplier::where('company_name', 'like', "%{$query}%")
                            ->orWhere('email', 'like', "%{$query}%")
                            ->orWhere('phone', 'like', "%{$query}%")
                            ->limit(10)
                            ->get(['id', 'company_name', 'email', 'phone']);

        return response()->json($suppliers);
    }
}

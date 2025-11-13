<?php
namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Marea;
use App\Models\Movimentation;
use App\Models\RecurringMovimentation;
use App\Models\Supplier;
use App\Traits\HasTranslations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class RecycleBinController extends Controller
{
    use HasTranslations;
    /**
     * Display the recycle bin index page.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Get vessel_id from request attributes
        $vesselId = $request->attributes->get('vessel_id');

        // Check permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, $this->transFrom('notifications', 'You do not have access to this vessel.'));
        }

        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));

        // Check if user can view recycle bin (typically admin/manager)
        if (! ($permissions['recycle_bin.view'] ?? false)) {
            abort(403, 'You do not have permission to view the recycle bin.');
        }

        $type   = $request->get('type', 'all'); // all, transactions, suppliers, recurring_transactions, mareas, maintenances
        $search = $request->get('search', '');

        // Get soft-deleted transactions
        $transactions = collect();
        if ($type === 'all' || $type === 'transactions') {
            $transactionQuery = Movimentation::onlyTrashed()
                ->where('vessel_id', $vesselId)
                ->with(['category', 'supplier', 'vessel'])
                ->orderBy('deleted_at', 'desc');

            if ($search) {
                $transactionQuery->where(function ($q) use ($search) {
                    $q->where('transaction_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $transactions = $transactionQuery->get()->map(function ($transaction) {
                return [
                    'id'          => $transaction->id,
                    'type'        => 'transaction',
                    'type_label'  => 'Transaction',
                    'name'        => $transaction->transaction_number,
                    'description' => $transaction->description,
                    'deleted_at'  => $transaction->deleted_at ? $transaction->deleted_at->format('Y-m-d H:i:s') : null,
                    'category'    => $transaction->category ? $transaction->category->name : null,
                    'amount'      => $transaction->total_amount,
                    'currency'    => $transaction->currency,
                ];
            });
        }

        // Get soft-deleted suppliers
        $suppliers = collect();
        if ($type === 'all' || $type === 'suppliers') {
            $supplierQuery = Supplier::onlyTrashed()
                ->where('vessel_id', $vesselId)
                ->orderBy('deleted_at', 'desc');

            if ($search) {
                $supplierQuery->where(function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $suppliers = $supplierQuery->get()->map(function ($supplier) {
                return [
                    'id'          => $supplier->id,
                    'type'        => 'supplier',
                    'type_label'  => 'Supplier',
                    'name'        => $supplier->company_name,
                    'description' => $supplier->description,
                    'deleted_at'  => $supplier->deleted_at ? $supplier->deleted_at->format('Y-m-d H:i:s') : null,
                ];
            });
        }

        // Get soft-deleted recurring transactions
        $recurringTransactions = collect();
        if ($type === 'all' || $type === 'recurring_transactions') {
            $recurringQuery = RecurringMovimentation::onlyTrashed()
                ->where('vessel_id', $vesselId)
                ->with(['category'])
                ->orderBy('deleted_at', 'desc');

            if ($search) {
                $recurringQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $recurringTransactions = $recurringQuery->get()->map(function ($recurring) {
                return [
                    'id'          => $recurring->id,
                    'type'        => 'recurring_transaction',
                    'type_label'  => 'Recurring Transaction',
                    'name'        => $recurring->name,
                    'description' => $recurring->description,
                    'deleted_at'  => $recurring->deleted_at ? $recurring->deleted_at->format('Y-m-d H:i:s') : null,
                    'category'    => $recurring->category ? $recurring->category->name : null,
                ];
            });
        }

        // Get soft-deleted mareas
        $mareas = collect();
        if ($type === 'all' || $type === 'mareas') {
            $mareaQuery = Marea::onlyTrashed()
                ->where('vessel_id', $vesselId)
                ->with(['vessel'])
                ->orderBy('deleted_at', 'desc');

            if ($search) {
                $mareaQuery->where(function ($q) use ($search) {
                    $q->where('marea_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $mareas = $mareaQuery->get()->map(function ($marea) {
                // Count transactions associated with this marea
                $transactionCount = Movimentation::onlyTrashed()
                    ->where('marea_id', $marea->id)
                    ->count();

                return [
                    'id'                => $marea->id,
                    'type'              => 'marea',
                    'type_label'        => 'Marea',
                    'name'              => $marea->marea_number,
                    'description'       => $marea->name ?: $marea->description,
                    'deleted_at'        => $marea->deleted_at ? $marea->deleted_at->format('Y-m-d H:i:s') : null,
                    'transaction_count' => $transactionCount,
                    'status'            => $marea->status,
                ];
            });
        }

        // Get soft-deleted maintenances
        $maintenances = collect();
        if ($type === 'all' || $type === 'maintenances') {
            $maintenanceQuery = Maintenance::onlyTrashed()
                ->where('vessel_id', $vesselId)
                ->with(['vessel'])
                ->orderBy('deleted_at', 'desc');

            if ($search) {
                $maintenanceQuery->where(function ($q) use ($search) {
                    $q->where('maintenance_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $maintenances = $maintenanceQuery->get()->map(function ($maintenance) {
                // Count transactions associated with this maintenance
                $transactionCount = Movimentation::onlyTrashed()
                    ->where('maintenance_id', $maintenance->id)
                    ->count();

                return [
                    'id'                => $maintenance->id,
                    'type'              => 'maintenance',
                    'type_label'        => 'Maintenance',
                    'name'              => $maintenance->maintenance_number,
                    'description'       => $maintenance->name ?: $maintenance->description,
                    'deleted_at'        => $maintenance->deleted_at ? $maintenance->deleted_at->format('Y-m-d H:i:s') : null,
                    'transaction_count' => $transactionCount,
                    'status'            => $maintenance->status,
                ];
            });
        }

        // Combine all items and sort by deleted_at
        $allItems = $transactions->concat($suppliers)->concat($recurringTransactions)->concat($mareas)->concat($maintenances)
            ->sortByDesc('deleted_at')
            ->values();

        return Inertia::render('RecycleBin/Index', [
            'items'   => $allItems,
            'filters' => [
                'type'   => $type,
                'search' => $search,
            ],
            'counts'  => [
                'transactions'           => Movimentation::onlyTrashed()->where('vessel_id', $vesselId)->count(),
                'suppliers'              => Supplier::onlyTrashed()->where('vessel_id', $vesselId)->count(),
                'recurring_transactions' => RecurringMovimentation::onlyTrashed()->where('vessel_id', $vesselId)->count(),
                'mareas'                 => Marea::onlyTrashed()->where('vessel_id', $vesselId)->count(),
                'maintenances'           => Maintenance::onlyTrashed()->where('vessel_id', $vesselId)->count(),
            ],
        ]);
    }

    /**
     * Restore a soft-deleted item.
     */
    public function restore(Request $request, $vessel, $type, $id)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $vesselId = $request->attributes->get('vessel_id');

            // Check permissions
            if (! $user || ! $user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole    = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));

            if (! ($permissions['recycle_bin.restore'] ?? false)) {
                abort(403, 'You do not have permission to restore items.');
            }

            $item     = null;
            $itemName = '';

            switch ($type) {
                case 'transaction':
                    $item     = Movimentation::onlyTrashed()->where('vessel_id', $vesselId)->findOrFail($id);
                    $itemName = $item->transaction_number;
                    break;
                case 'supplier':
                    $item     = Supplier::onlyTrashed()->where('vessel_id', $vesselId)->findOrFail($id);
                    $itemName = $item->company_name;
                    break;
                case 'recurring_transaction':
                    $item     = RecurringMovimentation::onlyTrashed()->where('vessel_id', $vesselId)->findOrFail($id);
                    $itemName = $item->name;
                    break;
                case 'marea':
                    $item     = Marea::onlyTrashed()->where('vessel_id', $vesselId)->findOrFail($id);
                    $itemName = $item->marea_number;
                    // Also restore transactions associated with this marea
                    Movimentation::onlyTrashed()
                        ->where('marea_id', $item->id)
                        ->restore();
                    break;
                case 'maintenance':
                    $item     = Maintenance::onlyTrashed()->where('vessel_id', $vesselId)->findOrFail($id);
                    $itemName = $item->maintenance_number;
                    // Also restore transactions associated with this maintenance
                    Movimentation::onlyTrashed()
                        ->where('maintenance_id', $item->id)
                        ->restore();
                    break;
                default:
                    abort(404, $this->transFrom('notifications', 'Invalid item type.'));
            }

            $item->restore();

            return redirect()
                ->route('panel.recycle-bin.index', ['vessel' => $vesselId])
                ->with('success', $this->transFrom('notifications', ":type ':name' has been restored successfully.", [
                    'type' => $type,
                    'name' => $itemName,
                ]));
        } catch (\Exception $e) {
            Log::error('Recycle bin restore failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to restore item: :message', [
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /**
     * Permanently delete an item.
     */
    public function destroy(Request $request, $vessel, $type, $id)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $vesselId = $request->attributes->get('vessel_id');

            // Check permissions
            if (! $user || ! $user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole    = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));

            if (! ($permissions['recycle_bin.delete'] ?? false)) {
                abort(403, 'You do not have permission to permanently delete items.');
            }

            $item     = null;
            $itemName = '';

            switch ($type) {
                case 'transaction':
                    $item     = Movimentation::onlyTrashed()->where('vessel_id', $vesselId)->findOrFail($id);
                    $itemName = $item->transaction_number;
                    break;
                case 'supplier':
                    $item     = Supplier::onlyTrashed()->where('vessel_id', $vesselId)->findOrFail($id);
                    $itemName = $item->company_name;
                    break;
                case 'recurring_transaction':
                    $item     = RecurringMovimentation::onlyTrashed()->where('vessel_id', $vesselId)->findOrFail($id);
                    $itemName = $item->name;
                    break;
                case 'marea':
                    $item     = Marea::onlyTrashed()->where('vessel_id', $vesselId)->findOrFail($id);
                    $itemName = $item->marea_number;
                    // Permanently delete transactions associated with this marea
                    Movimentation::onlyTrashed()
                        ->where('marea_id', $item->id)
                        ->forceDelete();
                    // Permanently delete related data
                    \App\Models\MareaQuantityReturn::where('marea_id', $item->id)->delete();
                    \App\Models\MareaCrew::where('marea_id', $item->id)->delete();
                    \App\Models\MareaDistributionItem::where('marea_id', $item->id)->delete();
                    break;
                case 'maintenance':
                    $item     = Maintenance::onlyTrashed()->where('vessel_id', $vesselId)->findOrFail($id);
                    $itemName = $item->maintenance_number;
                    // Permanently delete transactions associated with this maintenance
                    Movimentation::onlyTrashed()
                        ->where('maintenance_id', $item->id)
                        ->forceDelete();
                    break;
                default:
                    abort(404, $this->transFrom('notifications', 'Invalid item type.'));
            }

            $item->forceDelete();

            return redirect()
                ->route('panel.recycle-bin.index', ['vessel' => $vesselId])
                ->with('success', $this->transFrom('notifications', ":type ':name' has been permanently deleted.", [
                    'type' => $type,
                    'name' => $itemName,
                ]));
        } catch (\Exception $e) {
            Log::error('Recycle bin permanent delete failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to permanently delete item: :message', [
                    'message' => $e->getMessage(),
                ]));
        }
    }

    /**
     * Empty the recycle bin (permanently delete all items).
     */
    public function empty(Request $request, $vessel)
    {
        try {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $vesselId = $request->attributes->get('vessel_id');

            // Check permissions
            if (! $user || ! $user->hasAccessToVessel($vesselId)) {
                abort(403, 'You do not have access to this vessel.');
            }

            $userRole    = $user->getRoleForVessel($vesselId);
            $permissions = config('permissions.' . $userRole, config('permissions.default', []));

            if (! ($permissions['recycle_bin.delete'] ?? false)) {
                abort(403, 'You do not have permission to empty the recycle bin.');
            }

            $transactionCount = Movimentation::onlyTrashed()->where('vessel_id', $vesselId)->count();
            $supplierCount    = Supplier::onlyTrashed()->where('vessel_id', $vesselId)->count();
            $recurringCount   = RecurringMovimentation::onlyTrashed()->where('vessel_id', $vesselId)->count();
            $mareaCount       = Marea::onlyTrashed()->where('vessel_id', $vesselId)->count();
            $maintenanceCount = Maintenance::onlyTrashed()->where('vessel_id', $vesselId)->count();

            // Permanently delete all soft-deleted items
            Movimentation::onlyTrashed()->where('vessel_id', $vesselId)->forceDelete();
            Supplier::onlyTrashed()->where('vessel_id', $vesselId)->forceDelete();
            RecurringMovimentation::onlyTrashed()->where('vessel_id', $vesselId)->forceDelete();

            // For mareas, also delete related data
            $softDeletedMareas = Marea::onlyTrashed()->where('vessel_id', $vesselId)->get();
            foreach ($softDeletedMareas as $marea) {
                // Permanently delete transactions
                Movimentation::onlyTrashed()->where('marea_id', $marea->id)->forceDelete();
                // Permanently delete related data
                \App\Models\MareaQuantityReturn::where('marea_id', $marea->id)->delete();
                \App\Models\MareaCrew::where('marea_id', $marea->id)->delete();
                \App\Models\MareaDistributionItem::where('marea_id', $marea->id)->delete();
            }
            Marea::onlyTrashed()->where('vessel_id', $vesselId)->forceDelete();

            // For maintenances, also delete related transactions
            $softDeletedMaintenances = Maintenance::onlyTrashed()->where('vessel_id', $vesselId)->get();
            foreach ($softDeletedMaintenances as $maintenance) {
                // Permanently delete transactions
                Movimentation::onlyTrashed()->where('maintenance_id', $maintenance->id)->forceDelete();
            }
            Maintenance::onlyTrashed()->where('vessel_id', $vesselId)->forceDelete();

            $totalCount = $transactionCount + $supplierCount + $recurringCount + $mareaCount + $maintenanceCount;

            return redirect()
                ->route('panel.recycle-bin.index', ['vessel' => $vesselId])
                ->with('success', $this->transFrom('notifications', 'Recycle bin has been emptied. :count item(s) have been permanently deleted.', [
                    'count' => $totalCount,
                ]));
        } catch (\Exception $e) {
            Log::error('Recycle bin empty failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', $this->transFrom('notifications', 'Failed to empty recycle bin: :message', [
                    'message' => $e->getMessage(),
                ]));
        }
    }
}

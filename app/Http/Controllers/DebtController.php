<?php

namespace App\Http\Controllers;

use App\Actions\General\EasyHashAction;
use App\Http\Controllers\Concerns\HashesIds;
use App\Models\Debt;
use App\Services\DebtService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DebtController extends Controller
{
    use HashesIds;

    protected $debtService;

    public function __construct(DebtService $debtService)
    {
        $this->debtService = $debtService;
    }

    public function index(Request $request, $vessel)
    {
        $vesselId = $this->unhashId($vessel, 'vessel');
        
        $query = Debt::where('vessel_id', $vesselId)
            ->with('supplier');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function($q) use ($search) {
                      $q->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $debts = $query->latest()->paginate(10)->withQueryString();

        // Transform for frontend
        $debts->getCollection()->transform(function ($debt) {
            return [
                'id' => $debt->getRouteKey(),
                'description' => $debt->description,
                'amount' => $debt->amount,
                'paid_amount' => $debt->paid_amount,
                'remaining_amount' => $debt->remaining_amount,
                'status' => $debt->status,
                'due_date' => $debt->due_date,
                'supplier' => $debt->supplier ? [
                    'id' => $debt->supplier->getRouteKey(),
                    'name' => $debt->supplier->company_name,
                ] : null,
            ];
        });

        return Inertia::render('panel/debts/Index', [
            'debts' => $debts,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create(Request $request, $vessel)
    {
        $vesselId = $this->unhashId($vessel, 'vessel');
        
        // Get suppliers for dropdown
        $suppliers = \App\Models\Supplier::where('vessel_id', $vesselId)
            ->orderBy('company_name')
            ->get()
            ->map(function ($supplier) {
                return [
                    'value' => $supplier->getRouteKey(),
                    'label' => $supplier->company_name,
                ];
            });

        return Inertia::render('panel/debts/Create', [
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request, $vessel)
    {
        $vesselId = $this->unhashId($vessel, 'vessel');
        
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|string',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['supplier_id'])) {
            $validated['supplier_id'] = EasyHashAction::decode($validated['supplier_id'], 'supplier-id');
        }

        $this->debtService->createDebt($request->user(), $vesselId, $validated);

        return redirect()->route('panel.debts.index', ['vessel' => $vessel])
            ->with('success', 'Debt created successfully.');
    }

    public function show(Request $request, $vessel, $debtId)
    {
        $vesselId = $this->unhashId($vessel, 'vessel');
        $debtId = $this->unhashId($debtId, 'debt');

        $debt = Debt::where('vessel_id', $vesselId)
            ->where('id', $debtId)
            ->with(['supplier', 'payments' => function($q) {
                $q->latest();
            }])
            ->firstOrFail();

        return Inertia::render('panel/debts/Show', [
            'debt' => [
                'id' => $debt->getRouteKey(),
                'description' => $debt->description,
                'amount' => $debt->amount,
                'paid_amount' => $debt->paid_amount,
                'remaining_amount' => $debt->remaining_amount,
                'status' => $debt->status,
                'due_date' => $debt->due_date,
                'notes' => $debt->notes,
                'supplier' => $debt->supplier ? [
                    'id' => $debt->supplier->getRouteKey(),
                    'name' => $debt->supplier->company_name,
                ] : null,
                'payments' => $debt->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'payment_date' => $payment->payment_date,
                        'notes' => $payment->notes,
                    ];
                }),
            ],
        ]);
    }

    public function addPayment(Request $request, $vessel, $debtId)
    {
        $vesselId = $this->unhashId($vessel, 'vessel');
        $debtId = $this->unhashId($debtId, 'debt');

        $debt = Debt::where('vessel_id', $vesselId)
            ->where('id', $debtId)
            ->firstOrFail();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $debt->remaining_amount,
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $this->debtService->addPayment($debt, $request->user(), $validated);

        return back()->with('success', 'Payment added successfully.');
    }

    public function destroy(Request $request, $vessel, $debtId)
    {
        $vesselId = $this->unhashId($vessel, 'vessel');
        $debtId = $this->unhashId($debtId, 'debt');

        $debt = Debt::where('vessel_id', $vesselId)
            ->where('id', $debtId)
            ->firstOrFail();

        $this->debtService->deleteDebt($debt, $request->user());

        return redirect()->route('panel.debts.index', ['vessel' => $vessel])
            ->with('success', 'Debt deleted successfully.');
    }
}

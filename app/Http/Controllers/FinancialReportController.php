<?php
namespace App\Http\Controllers;

use App\Models\Marea;
use App\Models\Movimentation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialReportController extends Controller
{
    /**
     * Display a listing of financial reports grouped by month and year.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        // Check reports.access permission from config (reports require specific access permission)
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['reports.access'] ?? false)) {
            abort(403, 'You do not have permission to view financial reports.');
        }

        // Get vessel settings for default currency
        $vesselSetting   = \App\Models\VesselSetting::getForVessel($vesselId);
        $vessel          = \App\Models\Vessel::find($vesselId);
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // Get all month/year combinations from transactions with summary data
        $monthYearCombinations = Movimentation::where('vessel_id', $vesselId)
            ->where('status', 'completed')
            ->selectRaw('
                DISTINCT transaction_month as month,
                transaction_year as year,
                COUNT(*) as count,
                SUM(CASE WHEN type = "income" THEN total_amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = "expense" THEN total_amount ELSE 0 END) as total_expenses
            ')
            ->whereNotNull('transaction_month')
            ->whereNotNull('transaction_year')
            ->groupBy('transaction_month', 'transaction_year')
            ->orderBy('transaction_year', 'desc')
            ->orderBy('transaction_month', 'desc')
            ->get()
            ->map(function ($item) {
                $totalIncome   = (int) $item->total_income;
                $totalExpenses = (int) $item->total_expenses;
                $netBalance    = $totalIncome - $totalExpenses;

                return [
                    'month'          => (int) $item->month,
                    'year'           => (int) $item->year,
                    'month_label'    => date('F', mktime(0, 0, 0, $item->month, 1)),
                    'count'          => (int) $item->count,
                    'total_income'   => $totalIncome,
                    'total_expenses' => $totalExpenses,
                    'net_balance'    => $netBalance,
                ];
            })
            ->values();

        return Inertia::render('FinancialReports/Index', [
            'monthYearCombinations' => $monthYearCombinations,
            'defaultCurrency'       => $defaultCurrency,
        ]);
    }

    /**
     * Display financial report for a specific month and year.
     */
    public function show(Request $request, $year, $month)
    {
        // Get parameters from route (ensures correct binding)
        $year  = (int) $request->route('year');
        $month = (int) $request->route('month');

        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view transactions using config permissions
        if (! $user || ! $user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        // Check reports.access permission from config
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (! ($permissions['reports.access'] ?? false)) {
            abort(403, 'You do not have permission to view financial reports.');
        }

        // Validate month and year
        if ($month < 1 || $month > 12) {
            abort(404, 'Invalid month.');
        }

        if ($year < 2000 || $year > 2100) {
            abort(404, 'Invalid year.');
        }

        // Get vessel settings for default currency
        $vesselSetting   = \App\Models\VesselSetting::getForVessel($vesselId);
        $vessel          = \App\Models\Vessel::find($vesselId);
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // Get all transactions for the month/year
        $transactions = Movimentation::where('vessel_id', $vesselId)
            ->where('transaction_month', $month)
            ->where('transaction_year', $year)
            ->where('status', 'completed') // Only completed transactions
            ->with(['category:id,name,type,color', 'marea:id,marea_number,name'])
            ->get();

        // Calculate summary statistics
        $totalIncome      = $transactions->where('type', 'income')->sum('total_amount');
        $totalExpenses    = $transactions->where('type', 'expense')->sum('total_amount');
        $netBalance       = $totalIncome - $totalExpenses;
        $transactionCount = $transactions->count();

        // Get category breakdown
        $categoryBreakdown = $transactions->groupBy('category_id')->map(function ($categoryTransactions, $categoryId) {
            $category = $categoryTransactions->first()->category;
            return [
                'category_id'    => $categoryId,
                'category_name'  => $category ? $category->translated_name : 'Uncategorized',
                'category_type'  => $category ? $category->type : null,
                'category_color' => $category ? $category->color : null,
                'income'         => $categoryTransactions->where('type', 'income')->sum('total_amount'),
                'expenses'       => $categoryTransactions->where('type', 'expense')->sum('total_amount'),
                'count'          => $categoryTransactions->count(),
            ];
        })->values()->sortByDesc('expenses')->values();

        // Get daily breakdown for chart
        $dailyBreakdown = $transactions->groupBy(function ($transaction) {
            $date = $transaction->transaction_date;
            if ($date instanceof \Carbon\Carbon) {
                return $date->format('Y-m-d');
            }
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        })->map(function ($dayTransactions, $date) {
            return [
                'date'           => $date,
                'formatted_date' => \Carbon\Carbon::parse($date)->format('M d'),
                'income'         => $dayTransactions->where('type', 'income')->sum('total_amount'),
                'expenses'       => $dayTransactions->where('type', 'expense')->sum('total_amount'),
                'net'            => $dayTransactions->where('type', 'income')->sum('total_amount') - $dayTransactions->where('type', 'expense')->sum('total_amount'),
                'count'          => $dayTransactions->count(),
            ];
        })->sortBy('date')->values();

        // Get marea information for the month
        $mareas = Marea::where('vessel_id', $vesselId)
            ->where(function ($query) use ($year, $month) {
                // Get mareas that were active during this month
                $query->where(function ($q) use ($year, $month) {
                    // Check if marea's dates overlap with the month
                    $startDate = sprintf('%04d-%02d-01', $year, $month);
                    $endDate   = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');

                    $q->where(function ($subQ) use ($startDate, $endDate) {
                        $subQ->whereNotNull('actual_departure_date')
                            ->whereNotNull('actual_return_date')
                            ->where('actual_departure_date', '<=', $endDate)
                            ->where('actual_return_date', '>=', $startDate);
                    })->orWhere(function ($subQ) use ($startDate, $endDate) {
                        $subQ->whereNotNull('estimated_departure_date')
                            ->whereNotNull('estimated_return_date')
                            ->where('estimated_departure_date', '<=', $endDate)
                            ->where('estimated_return_date', '>=', $startDate);
                    });
                });
            })
            ->with(['quantityReturns:id,marea_id,name,quantity'])
            ->get()
            ->map(function ($marea) use ($month, $year) {
                // Get transactions for this marea in this month
                $mareaTransactions = Movimentation::where('vessel_id', $marea->vessel_id)
                    ->where('marea_id', $marea->id)
                    ->where('transaction_month', $month)
                    ->where('transaction_year', $year)
                    ->where('status', 'completed')
                    ->get();

                $mareaIncome   = $mareaTransactions->where('type', 'income')->sum('total_amount');
                $mareaExpenses = $mareaTransactions->where('type', 'expense')->sum('total_amount');
                $mareaNet      = $mareaIncome - $mareaExpenses;

                return [
                    'id'                       => $marea->id,
                    'marea_number'             => $marea->marea_number,
                    'name'                     => $marea->name,
                    'status'                   => $marea->status,
                    'actual_departure_date'    => $marea->actual_departure_date ? $marea->actual_departure_date->format('Y-m-d') : null,
                    'actual_return_date'       => $marea->actual_return_date ? $marea->actual_return_date->format('Y-m-d') : null,
                    'estimated_departure_date' => $marea->estimated_departure_date ? $marea->estimated_departure_date->format('Y-m-d') : null,
                    'estimated_return_date'    => $marea->estimated_return_date ? $marea->estimated_return_date->format('Y-m-d') : null,
                    'total_income'             => $mareaIncome,
                    'total_expenses'           => $mareaExpenses,
                    'net_result'               => $mareaNet,
                    'transaction_count'        => $mareaTransactions->count(),
                    'quantity_returns'         => $marea->quantityReturns->map(function ($qr) {
                        return [
                            'name'     => $qr->name,
                            'quantity' => (float) $qr->quantity,
                        ];
                    }),
                ];
            });

        // Get month label
        $monthLabel = date('F', mktime(0, 0, 0, $month, 1));

        // Calculate percentage changes (compare with previous month if available)
        $previousMonth = $month - 1;
        $previousYear  = $year;
        if ($previousMonth < 1) {
            $previousMonth = 12;
            $previousYear  = $year - 1;
        }

        $previousMonthTransactions = Movimentation::where('vessel_id', $vesselId)
            ->where('transaction_month', $previousMonth)
            ->where('transaction_year', $previousYear)
            ->where('status', 'completed')
            ->get();

        $previousMonthIncome   = $previousMonthTransactions->where('type', 'income')->sum('total_amount');
        $previousMonthExpenses = $previousMonthTransactions->where('type', 'expense')->sum('total_amount');
        $previousMonthNet      = $previousMonthIncome - $previousMonthExpenses;

        $incomeChange = $previousMonthIncome > 0
            ? (($totalIncome - $previousMonthIncome) / $previousMonthIncome) * 100
            : 0;
        $expensesChange = $previousMonthExpenses > 0
            ? (($totalExpenses - $previousMonthExpenses) / $previousMonthExpenses) * 100
            : 0;
        $netChange = $previousMonthNet != 0
            ? (($netBalance - $previousMonthNet) / abs($previousMonthNet)) * 100
            : 0;

        return Inertia::render('FinancialReports/Show', [
            'month'             => $month,
            'year'              => $year,
            'monthLabel'        => $monthLabel,
            'defaultCurrency'   => $defaultCurrency,
            'summary'           => [
                'total_income'      => $totalIncome,
                'total_expenses'    => $totalExpenses,
                'net_balance'       => $netBalance,
                'transaction_count' => $transactionCount,
                'income_change'     => round($incomeChange, 2),
                'expenses_change'   => round($expensesChange, 2),
                'net_change'        => round($netChange, 2),
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'dailyBreakdown'    => $dailyBreakdown,
            'mareas'            => $mareas,
        ]);
    }
}

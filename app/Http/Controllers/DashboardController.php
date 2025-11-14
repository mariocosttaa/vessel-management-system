<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HashesIds;
use App\Models\Marea;
use App\Models\Movimentation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends BaseController
{
    use HashesIds;
    /**
     * Display the dashboard for the current vessel.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403, 'You must be logged in to view the dashboard.');
        }

        $vessel   = $this->getCurrentVessel($request);
        $vesselId = $this->getCurrentVesselId($request);

        // Get vessel settings for default currency
        $vesselSetting   = \App\Models\VesselSetting::getForVessel($vesselId);
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // Get current month and year
        $now          = Carbon::now();
        $currentMonth = $now->month;
        $currentYear  = $now->year;

        // Get current month financial statistics
        $currentMonthStats = Movimentation::where('vessel_id', $vesselId)
            ->where('transaction_year', $currentYear)
            ->where('transaction_month', $currentMonth)
            ->where('status', 'completed')
            ->selectRaw('
                COUNT(*) as transaction_count,
                SUM(CASE WHEN type = "income" THEN total_amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = "expense" THEN total_amount ELSE 0 END) as total_expenses
            ')
            ->first();

        $totalIncome      = (int) ($currentMonthStats->total_income ?? 0);
        $totalExpenses    = (int) ($currentMonthStats->total_expenses ?? 0);
        $netBalance       = $totalIncome - $totalExpenses;
        $transactionCount = (int) ($currentMonthStats->transaction_count ?? 0);

        // Get current month expenses by category for donut chart
        $currentMonthExpensesByCategory = Movimentation::where('transactions.vessel_id', $vesselId)
            ->where('transactions.transaction_year', $currentYear)
            ->where('transactions.transaction_month', $currentMonth)
            ->where('transactions.status', 'completed')
            ->where('transactions.type', 'expense')
            ->leftJoin('transaction_categories', 'transactions.category_id', '=', 'transaction_categories.id')
            ->select(
                'transactions.category_id',
                DB::raw('COALESCE(transaction_categories.name, "Uncategorized") as category_name'),
                DB::raw('transaction_categories.color as category_color'),
                DB::raw('SUM(transactions.total_amount) as total_expenses')
            )
            ->groupBy('transactions.category_id')
            ->groupBy(DB::raw('COALESCE(transaction_categories.name, "Uncategorized")'))
            ->groupBy('transaction_categories.color')
            ->havingRaw('SUM(transactions.total_amount) > 0')
            ->orderByDesc('total_expenses')
            ->get()
            ->map(function ($item) {
                return [
                    'category_id'    => $item->category_id,
                    'category_name'  => $item->category_name,
                    'category_color' => $item->category_color,
                    'total_expenses' => (int) $item->total_expenses,
                ];
            })
            ->values();

        // Get last 6 months data for chart
        $last6Months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = Carbon::now()->subMonths($i);
            $month = $date->month;
            $year  = $date->year;

            $monthStats = Movimentation::where('vessel_id', $vesselId)
                ->where('transaction_year', $year)
                ->where('transaction_month', $month)
                ->where('status', 'completed')
                ->selectRaw('
                    SUM(CASE WHEN type = "income" THEN total_amount ELSE 0 END) as income,
                    SUM(CASE WHEN type = "expense" THEN total_amount ELSE 0 END) as expenses
                ')
                ->first();

            $last6Months[] = [
                'month'       => $month,
                'year'        => $year,
                'month_label' => $date->format('M Y'),
                'income'      => (int) ($monthStats->income ?? 0),
                'expenses'    => (int) ($monthStats->expenses ?? 0),
                'net'         => (int) (($monthStats->income ?? 0) - ($monthStats->expenses ?? 0)),
            ];
        }

        // Check if vessel is at sea (has active marea with status 'at_sea')
        $vesselAtSea = Marea::where('vessel_id', $vesselId)
            ->where('status', 'at_sea')
            ->exists();

        // Get active marea info if at sea
        $activeMarea = null;
        if ($vesselAtSea) {
            $activeMarea = Marea::where('vessel_id', $vesselId)
                ->where('status', 'at_sea')
                ->with(['vessel'])
                ->first();

            if ($activeMarea) {
                $activeMarea = [
                    'id'                    => $this->hashId($activeMarea->id, 'marea-id'),
                    'marea_number'          => $activeMarea->marea_number,
                    'name'                  => $activeMarea->name,
                    'status'                => $activeMarea->status,
                    'actual_departure_date' => $activeMarea->actual_departure_date ? $activeMarea->actual_departure_date->format('Y-m-d') : null,
                    'estimated_return_date' => $activeMarea->estimated_return_date ? $activeMarea->estimated_return_date->format('Y-m-d') : null,
                ];
            }
        }

        // Get preparing mareas
        $preparingMareas = Marea::where('vessel_id', $vesselId)
            ->where('status', 'preparing')
            ->orderBy('estimated_departure_date', 'asc')
            ->get()
            ->map(function ($marea) {
                return [
                    'id'                       => $this->hashId($marea->id, 'marea-id'),
                    'marea_number'             => $marea->marea_number,
                    'name'                     => $marea->name,
                    'status'                   => $marea->status,
                    'estimated_departure_date' => $marea->estimated_departure_date ? $marea->estimated_departure_date->format('Y-m-d') : null,
                    'estimated_return_date'    => $marea->estimated_return_date ? $marea->estimated_return_date->format('Y-m-d') : null,
                ];
            });

        // Get recent transactions (last 5)
        $recentTransactions = Movimentation::where('vessel_id', $vesselId)
            ->where('status', 'completed')
            ->with(['category', 'supplier'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id'                         => $this->hashId($transaction->id, 'movimentation'),
                    'transaction_number'         => $transaction->transaction_number,
                    'type'                       => $transaction->type,
                    'type_label'                 => ucfirst($transaction->type),
                    'amount'                     => $transaction->total_amount,
                    'currency'                   => $transaction->currency,
                    'transaction_date'           => $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d') : null,
                    'formatted_transaction_date' => $transaction->transaction_date ? $transaction->transaction_date->format('M d, Y') : null,
                    'description'                => $transaction->description,
                    'category'                   => $transaction->category ? [
                        'id'    => $this->hashId($transaction->category->id, 'transactioncategory'),
                        'name'  => $transaction->category->translated_name,
                        'color' => $transaction->category->color,
                    ] : null,
                ];
            });

        // Get vessel statistics
        $vesselStats = [
            'total_crew'         => $vessel->crewMembers()->count(),
            'total_transactions' => $vessel->transactions()->where('status', 'completed')->count(),
            'total_mareas'       => $vessel->mareas()->count(),
            'active_mareas'      => $vessel->mareas()->whereIn('status', ['preparing', 'at_sea', 'returned'])->count(),
        ];

        // Get last 6 crew members
        $last6CrewMembers = \App\Models\User::where('vessel_id', $vesselId)
            ->whereNotNull('position_id')
            ->with('position')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($member) {
                return [
                    'id'                   => $this->hashId($member->id, 'user-id'),
                    'name'                 => $member->name,
                    'email'                => $member->email,
                    'position_name'        => $member->position ? $member->position->translated_name : null,
                    'status'               => $member->status,
                    'status_label'         => ucfirst($member->status ?? 'active'),
                    'created_at'           => $member->created_at ? $member->created_at->format('Y-m-d') : null,
                    'formatted_created_at' => $member->created_at ? $member->created_at->format('M d, Y') : null,
                ];
            });

        // Get user permissions for quick links
        $userRole    = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));

        return Inertia::render('Dashboard', [
            'vessel'                         => [
                'id'                  => $this->hashId($vessel->id, 'vessel'),
                'name'                => $vessel->name,
                'registration_number' => $vessel->registration_number,
                'status'              => $vessel->status,
            ],
            'currentMonth'                   => [
                'month'             => $currentMonth,
                'year'              => $currentYear,
                'month_label'       => $now->format('F Y'),
                'total_income'      => $totalIncome,
                'total_expenses'    => $totalExpenses,
                'net_balance'       => $netBalance,
                'transaction_count' => $transactionCount,
            ],
            'last6Months'                    => $last6Months,
            'vesselAtSea'                    => $vesselAtSea,
            'activeMarea'                    => $activeMarea,
            'preparingMareas'                => $preparingMareas,
            'recentTransactions'             => $recentTransactions,
            'vesselStats'                    => $vesselStats,
            'last6CrewMembers'               => $last6CrewMembers,
            'defaultCurrency'                => $defaultCurrency,
            'permissions'                    => $permissions,
            'currentMonthExpensesByCategory' => $currentMonthExpensesByCategory,
        ]);
    }
}

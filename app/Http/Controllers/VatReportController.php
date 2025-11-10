<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\VatProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VatReportController extends Controller
{
    /**
     * Display a listing of VAT reports grouped by month and year.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view transactions using config permissions
        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        // Check transactions.view permission from config (reports require view permission)
        $userRole = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (!($permissions['transactions.view'] ?? false)) {
            abort(403, 'You do not have permission to view VAT reports.');
        }

        // Get all month/year combinations from transactions with VAT (only income transactions have VAT)
        $monthYearCombinations = Transaction::where('vessel_id', $vesselId)
            ->where('type', 'income') // Only income transactions have VAT
            ->where('status', 'completed')
            ->where('vat_amount', '>', 0) // Only transactions with VAT
            ->selectRaw('DISTINCT transaction_month as month, transaction_year as year, COUNT(*) as count, SUM(vat_amount) as total_vat')
            ->whereNotNull('transaction_month')
            ->whereNotNull('transaction_year')
            ->groupBy('transaction_month', 'transaction_year')
            ->orderBy('transaction_year', 'desc')
            ->orderBy('transaction_month', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => (int) $item->month,
                    'year' => (int) $item->year,
                    'month_label' => date('F', mktime(0, 0, 0, $item->month, 1)),
                    'count' => (int) $item->count,
                    'total_vat' => (int) $item->total_vat,
                ];
            })
            ->values();

        return Inertia::render('VatReports/Index', [
            'monthYearCombinations' => $monthYearCombinations,
        ]);
    }

    /**
     * Display VAT report for a specific month and year.
     */
    public function show(Request $request, $year, $month)
    {
        // Get parameters from route (ensures correct binding)
        $year = (int) $request->route('year');
        $month = (int) $request->route('month');
        
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        // Get vessel_id from request attributes (set by EnsureVesselAccess middleware)
        /** @var int $vesselId */
        $vesselId = $request->attributes->get('vessel_id');

        // Check if user has permission to view transactions using config permissions
        if (!$user || !$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        // Check transactions.view permission from config
        $userRole = $user->getRoleForVessel($vesselId);
        $permissions = config('permissions.' . $userRole, config('permissions.default', []));
        if (!($permissions['transactions.view'] ?? false)) {
            abort(403, 'You do not have permission to view VAT reports.');
        }

        // Validate month and year
        if ($month < 1 || $month > 12) {
            abort(404, 'Invalid month.');
        }

        if ($year < 2000 || $year > 2100) {
            abort(404, 'Invalid year.');
        }

        // Get vessel settings for default currency
        $vesselSetting = \App\Models\VesselSetting::getForVessel($vesselId);
        $vessel = \App\Models\Vessel::find($vesselId);
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // Get all transactions with VAT for the month/year (only income transactions have VAT)
        $transactions = Transaction::where('vessel_id', $vesselId)
            ->where('transaction_month', $month)
            ->where('transaction_year', $year)
            ->where('type', 'income') // Only income transactions have VAT
            ->where('status', 'completed')
            ->where('vat_amount', '>', 0) // Only transactions with VAT
            ->with([
                'category:id,name,type,color',
                'vatProfile:id,name,percentage,code,country_id',
                'vatProfile.country:id,name,code',
                'marea:id,marea_number,name'
            ])
            ->get();

        // Calculate summary statistics
        $totalVat = $transactions->sum('vat_amount');
        $totalBaseAmount = $transactions->sum('amount');
        $totalAmountWithVat = $transactions->sum('total_amount');
        $transactionCount = $transactions->count();

        // Get VAT breakdown by VAT profile
        $vatProfileBreakdown = $transactions->groupBy('vat_profile_id')->map(function ($profileTransactions, $profileId) {
            $vatProfile = $profileTransactions->first()->vatProfile;
            $transactionsList = $profileTransactions->map(function ($transaction) {
                $date = $transaction->transaction_date;
                $formattedDate = null;
                
                if ($date) {
                    try {
                        if ($date instanceof \Carbon\Carbon || $date instanceof \DateTimeInterface) {
                            $formattedDate = \Carbon\Carbon::instance($date)->format('Y-m-d');
                        } else {
                            $formattedDate = \Carbon\Carbon::parse((string) $date)->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        $formattedDate = null;
                    }
                }
                
                return [
                    'id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'transaction_date' => $formattedDate,
                    'description' => $transaction->description,
                    'base_amount' => $transaction->amount,
                    'vat_amount' => $transaction->vat_amount,
                    'total_amount' => $transaction->total_amount,
                    'category' => $transaction->category ? [
                        'id' => $transaction->category->id,
                        'name' => $transaction->category->name,
                        'color' => $transaction->category->color,
                    ] : null,
                ];
            })->values();

            return [
                'vat_profile_id' => $profileId,
                'vat_profile_name' => $vatProfile ? $vatProfile->name : 'Unknown',
                'vat_profile_percentage' => $vatProfile ? (float) $vatProfile->percentage : 0,
                'vat_profile_code' => $vatProfile ? $vatProfile->code : null,
                'country' => $vatProfile && $vatProfile->country ? [
                    'id' => $vatProfile->country->id,
                    'name' => $vatProfile->country->name,
                    'code' => $vatProfile->country->code,
                ] : null,
                'total_base_amount' => $profileTransactions->sum('amount'),
                'total_vat_amount' => $profileTransactions->sum('vat_amount'),
                'total_amount_with_vat' => $profileTransactions->sum('total_amount'),
                'transaction_count' => $profileTransactions->count(),
                'transactions' => $transactionsList,
            ];
        })->values()->sortByDesc('total_vat_amount')->values();

        // Get VAT breakdown by category
        $categoryBreakdown = $transactions->groupBy('category_id')->map(function ($categoryTransactions, $categoryId) {
            $category = $categoryTransactions->first()->category;
            return [
                'category_id' => $categoryId,
                'category_name' => $category ? $category->name : 'Uncategorized',
                'category_color' => $category ? $category->color : null,
                'total_base_amount' => $categoryTransactions->sum('amount'),
                'total_vat_amount' => $categoryTransactions->sum('vat_amount'),
                'total_amount_with_vat' => $categoryTransactions->sum('total_amount'),
                'transaction_count' => $categoryTransactions->count(),
            ];
        })->values()->sortByDesc('total_vat_amount')->values();

        // Get daily breakdown for chart
        $dailyBreakdown = $transactions->groupBy(function ($transaction) {
            $date = $transaction->transaction_date;
            if ($date instanceof \Carbon\Carbon) {
                return $date->format('Y-m-d');
            }
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        })->map(function ($dayTransactions, $date) {
            return [
                'date' => $date,
                'formatted_date' => \Carbon\Carbon::parse($date)->format('M d'),
                'base_amount' => $dayTransactions->sum('amount'),
                'vat_amount' => $dayTransactions->sum('vat_amount'),
                'total_amount' => $dayTransactions->sum('total_amount'),
                'count' => $dayTransactions->count(),
            ];
        })->sortBy('date')->values();

        // Get VAT by marea
        $mareaBreakdown = $transactions->whereNotNull('marea_id')
            ->groupBy('marea_id')
            ->map(function ($mareaTransactions, $mareaId) {
                $marea = $mareaTransactions->first()->marea;
                return [
                    'marea_id' => $mareaId,
                    'marea_number' => $marea ? $marea->marea_number : 'Unknown',
                    'marea_name' => $marea ? $marea->name : null,
                    'total_base_amount' => $mareaTransactions->sum('amount'),
                    'total_vat_amount' => $mareaTransactions->sum('vat_amount'),
                    'total_amount_with_vat' => $mareaTransactions->sum('total_amount'),
                    'transaction_count' => $mareaTransactions->count(),
                ];
            })->values()->sortByDesc('total_vat_amount')->values();

        // Get month label
        $monthLabel = date('F', mktime(0, 0, 0, $month, 1));

        // Calculate percentage changes (compare with previous month if available)
        $previousMonth = $month - 1;
        $previousYear = $year;
        if ($previousMonth < 1) {
            $previousMonth = 12;
            $previousYear = $year - 1;
        }

        $previousMonthTransactions = Transaction::where('vessel_id', $vesselId)
            ->where('transaction_month', $previousMonth)
            ->where('transaction_year', $previousYear)
            ->where('type', 'income')
            ->where('status', 'completed')
            ->where('vat_amount', '>', 0)
            ->get();

        $previousMonthVat = $previousMonthTransactions->sum('vat_amount');
        $previousMonthBase = $previousMonthTransactions->sum('amount');

        $vatChange = $previousMonthVat > 0 
            ? (($totalVat - $previousMonthVat) / $previousMonthVat) * 100 
            : 0;
        $baseChange = $previousMonthBase > 0 
            ? (($totalBaseAmount - $previousMonthBase) / $previousMonthBase) * 100 
            : 0;

        // Get all transactions list for detailed view
        $transactionsList = $transactions->map(function ($transaction) {
            $date = $transaction->transaction_date;
            $formattedDate = null;
            $formattedDateLong = null;
            
            if ($date) {
                try {
                    if ($date instanceof \Carbon\Carbon || $date instanceof \DateTimeInterface) {
                        $carbonDate = \Carbon\Carbon::instance($date);
                        $formattedDate = $carbonDate->format('Y-m-d');
                        $formattedDateLong = $carbonDate->format('M d, Y');
                    } else {
                        $carbonDate = \Carbon\Carbon::parse((string) $date);
                        $formattedDate = $carbonDate->format('Y-m-d');
                        $formattedDateLong = $carbonDate->format('M d, Y');
                    }
                } catch (\Exception $e) {
                    $formattedDate = null;
                    $formattedDateLong = null;
                }
            }
            
            return [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'transaction_date' => $formattedDate,
                'formatted_transaction_date' => $formattedDateLong,
                'description' => $transaction->description,
                'reference' => $transaction->reference,
                'base_amount' => $transaction->amount,
                'vat_amount' => $transaction->vat_amount,
                'total_amount' => $transaction->total_amount,
                'currency' => $transaction->currency,
                'category' => $transaction->category ? [
                    'id' => $transaction->category->id,
                    'name' => $transaction->category->name,
                    'color' => $transaction->category->color,
                ] : null,
                'vat_profile' => $transaction->vatProfile ? [
                    'id' => $transaction->vatProfile->id,
                    'name' => $transaction->vatProfile->name,
                    'percentage' => (float) $transaction->vatProfile->percentage,
                    'code' => $transaction->vatProfile->code,
                ] : null,
                'marea' => $transaction->marea ? [
                    'id' => $transaction->marea->id,
                    'marea_number' => $transaction->marea->marea_number,
                    'name' => $transaction->marea->name,
                ] : null,
            ];
        })->sortByDesc('transaction_date')->values();

        return Inertia::render('VatReports/Show', [
            'month' => $month,
            'year' => $year,
            'monthLabel' => $monthLabel,
            'defaultCurrency' => $defaultCurrency,
            'summary' => [
                'total_vat' => $totalVat,
                'total_base_amount' => $totalBaseAmount,
                'total_amount_with_vat' => $totalAmountWithVat,
                'transaction_count' => $transactionCount,
                'vat_change' => round($vatChange, 2),
                'base_change' => round($baseChange, 2),
            ],
            'vatProfileBreakdown' => $vatProfileBreakdown,
            'categoryBreakdown' => $categoryBreakdown,
            'dailyBreakdown' => $dailyBreakdown,
            'mareaBreakdown' => $mareaBreakdown,
            'transactions' => $transactionsList,
        ]);
    }
}


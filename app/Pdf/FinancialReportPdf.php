<?php
namespace App\Pdf;

use App\Actions\PdfAction;
use App\Models\Marea;
use App\Models\Movimentation;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Support\Facades\App;

class FinancialReportPdf
{
    /**
     * Generate financial report PDF.
     *
     * @param Vessel $vessel
     * @param int $year
     * @param int $month
     * @param User|null $user
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generate(
        Vessel $vessel,
        int $year,
        int $month,
        ?User $user = null,
        bool $enableColors = false
    ) {
        // Get vessel settings for default currency
        $vesselSetting   = \App\Models\VesselSetting::getForVessel($vessel->id);
        $defaultCurrency = $vesselSetting->currency_code ?? $vessel->currency_code ?? 'EUR';

        // Get all transactions for the month/year
        $transactions = Movimentation::where('vessel_id', $vessel->id)
            ->where('transaction_month', $month)
            ->where('transaction_year', $year)
            ->where('status', 'completed')
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
                'category_name'  => $category ? $category->translated_name : trans('pdfs.Uncategorized'),
                'category_type'  => $category ? $category->type : null,
                'category_color' => $category ? $category->color : null,
                'income'         => $categoryTransactions->where('type', 'income')->sum('total_amount'),
                'expenses'       => $categoryTransactions->where('type', 'expense')->sum('total_amount'),
                'count'          => $categoryTransactions->count(),
            ];
        })->values()->sortByDesc('expenses')->values();

        // Get daily breakdown
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
        $mareas = Marea::where('vessel_id', $vessel->id)
            ->where(function ($query) use ($year, $month) {
                $startDate = sprintf('%04d-%02d-01', $year, $month);
                $endDate   = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');

                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->whereNotNull('actual_departure_date')
                        ->whereNotNull('actual_return_date')
                        ->where('actual_departure_date', '<=', $endDate)
                        ->where('actual_return_date', '>=', $startDate);
                })->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->whereNotNull('estimated_departure_date')
                        ->whereNotNull('estimated_return_date')
                        ->where('estimated_departure_date', '<=', $endDate)
                        ->where('estimated_return_date', '>=', $startDate);
                });
            })
            ->with(['quantityReturns:id,marea_id,name,quantity'])
            ->get()
            ->map(function ($marea) use ($month, $year, $vessel) {
                $mareaTransactions = Movimentation::where('vessel_id', $vessel->id)
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
                    'actual_departure_date'    => $marea->actual_departure_date ? (\Carbon\Carbon::parse($marea->actual_departure_date)->format('Y-m-d')) : null,
                    'actual_return_date'       => $marea->actual_return_date ? (\Carbon\Carbon::parse($marea->actual_return_date)->format('Y-m-d')) : null,
                    'estimated_departure_date' => $marea->estimated_departure_date ? (\Carbon\Carbon::parse($marea->estimated_departure_date)->format('Y-m-d')) : null,
                    'estimated_return_date'    => $marea->estimated_return_date ? (\Carbon\Carbon::parse($marea->estimated_return_date)->format('Y-m-d')) : null,
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

        $previousMonthTransactions = Movimentation::where('vessel_id', $vessel->id)
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

        // Prepare summary
        $summary = [
            'total_income'      => $totalIncome,
            'total_expenses'    => $totalExpenses,
            'net_balance'       => $netBalance,
            'transaction_count' => $transactionCount,
            'income_change'     => round($incomeChange, 2),
            'expenses_change'   => round($expensesChange, 2),
            'net_change'        => round($netChange, 2),
        ];

        // Translate title and subtitle if user is provided
        $title    = trans('pdfs.Financial Report');
        $subtitle = trans('pdfs.Comprehensive financial overview for') . " {$monthLabel} {$year}";

        return PdfAction::generate('pdf.reports.financial-report', [
            'vessel'            => $vessel,
            'year'              => $year,
            'month'             => $month,
            'monthLabel'        => $monthLabel,
            'defaultCurrency'   => $defaultCurrency,
            'summary'           => $summary,
            'categoryBreakdown' => $categoryBreakdown,
            'dailyBreakdown'    => $dailyBreakdown,
            'mareas'            => $mareas,
            'title'             => $title,
            'subtitle'          => $subtitle,
            'enableColors'      => $enableColors,
            'user'              => $user,
        ]);
    }

    /**
     * Download financial report PDF.
     *
     * @param Vessel $vessel
     * @param int $year
     * @param int $month
     * @param string|null $filename
     * @param User|null $user
     * @return \Illuminate\Http\Response
     */
    public static function download(
        Vessel $vessel,
        int $year,
        int $month,
        ?string $filename = null,
        ?User $user = null,
        bool $enableColors = false
    ) {
        if (! $filename) {
            $monthLabel = date('F', mktime(0, 0, 0, $month, 1));
            $filename   = "financial_report_{$monthLabel}_{$year}_" . date('Y-m-d') . '.pdf';
        }

        $pdf = self::generate($vessel, $year, $month, $user, $enableColors);
        return $pdf->download($filename);
    }

    /**
     * Stream financial report PDF (display in browser).
     *
     * @param Vessel $vessel
     * @param int $year
     * @param int $month
     * @param string|null $filename
     * @param User|null $user
     * @return \Illuminate\Http\Response
     */
    public static function stream(
        Vessel $vessel,
        int $year,
        int $month,
        ?string $filename = null,
        ?User $user = null,
        bool $enableColors = false
    ) {
        if (! $filename) {
            $monthLabel = date('F', mktime(0, 0, 0, $month, 1));
            $filename   = "financial_report_{$monthLabel}_{$year}_" . date('Y-m-d') . '.pdf';
        }

        $pdf = self::generate($vessel, $year, $month, $user, $enableColors);
        return $pdf->stream($filename);
    }
}

<?php

namespace App\Pdf;

use App\Models\Transaction;
use App\Models\Vessel;
use App\Actions\MoneyAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class TransactionPdf
{
    /**
     * Generate transaction report PDF.
     *
     * @param Vessel $vessel
     * @param Collection $transactions
     * @param array $summary
     * @param string|null $period
     * @param string|null $startDate Start date for period (Y-m-d format)
     * @param string|null $endDate End date for period (Y-m-d format)
     * @param string $title
     * @param string|null $subtitle
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generate(
        Vessel $vessel,
        Collection $transactions,
        array $summary,
        ?string $period = null,
        ?string $startDate = null,
        ?string $endDate = null,
        string $title = 'Transaction Report',
        ?string $subtitle = 'Movements and Transactions Overview',
        bool $enableColors = false
    ) {
        // Load relationships for transactions
        $transactions->load('category');

        return PdfService::generate('pdf.reports.transaction-report', [
            'vessel' => $vessel,
            'transactions' => $transactions,
            'summary' => $summary,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'title' => $title,
            'subtitle' => $subtitle,
            'enableColors' => $enableColors,
        ]);
    }

    /**
     * Generate transaction report PDF from request filters.
     *
     * @param Request $request
     * @param Vessel $vessel
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generateFromRequest(Request $request, Vessel $vessel)
    {
        // If 'all' parameter is set, get ALL transactions for testing pagination
        if ($request->get('all') === 'true') {
            $transactions = Transaction::query()
                ->where('vessel_id', $vessel->id)
                ->with(['category'])
                ->orderBy('transaction_date', 'desc')
                ->get();
            $period = 'All Transactions';
        } else {
            // Get filter parameters
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // Build query
            $query = Transaction::query()
                ->where('vessel_id', $vessel->id)
                ->with(['category'])
                ->orderBy('transaction_date', 'desc');

            // Filter by month/year or date range
            if ($startDate && $endDate) {
                $query->whereBetween('transaction_date', [$startDate, $endDate]);
                $period = date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate));
            } else {
                $query->where('transaction_year', $year)
                      ->where('transaction_month', $month);
                $period = date('F Y', mktime(0, 0, 0, $month, 1, $year));
            }

            $transactions = $query->get();

            // Calculate start and end dates for the month
            if (!$startDate || !$endDate) {
                $startDate = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
                $endDate = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year)); // Last day of month
            }
        }

        // Calculate summary
        $totalIncome = $transactions->where('type', 'income')->sum('total_amount');
        $totalExpenses = $transactions->where('type', 'expense')->sum('total_amount');
        $netBalance = $totalIncome - $totalExpenses;

        $summary = [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_balance' => $netBalance,
            'total_count' => $transactions->count(),
        ];

        // Calculate start and end dates for "All Transactions"
        if ($request->get('all') === 'true' && $transactions->count() > 0) {
            $startDate = $transactions->min('transaction_date')->format('Y-m-d');
            $endDate = $transactions->max('transaction_date')->format('Y-m-d');
        }

        return PdfService::generate('pdf.reports.transaction-report', [
            'vessel' => $vessel,
            'transactions' => $transactions,
            'summary' => $summary,
            'period' => $period,
            'startDate' => $startDate ?? null,
            'endDate' => $endDate ?? null,
            'title' => 'Transaction Report',
            'subtitle' => 'Movements and Transactions Overview',
        ]);
    }

    /**
     * Download transaction report PDF.
     *
     * @param Vessel $vessel
     * @param Collection $transactions
     * @param array $summary
     * @param string|null $period
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public static function download(
        Vessel $vessel,
        Collection $transactions,
        array $summary,
        ?string $period = null,
        ?string $filename = null
    ) {
        if (!$filename) {
            $periodSlug = $period ? str_replace(' ', '_', strtolower($period)) : 'report';
            $filename = "transaction_report_{$vessel->id}_{$periodSlug}.pdf";
        }

        $pdf = self::generate($vessel, $transactions, $summary, $period, null, null);
        return $pdf->download($filename);
    }

    /**
     * Stream transaction report PDF (display in browser).
     *
     * @param Vessel $vessel
     * @param Collection $transactions
     * @param array $summary
     * @param string|null $period
     * @param string|null $filename
     * @return \Illuminate\Http\Response
     */
    public static function stream(
        Vessel $vessel,
        Collection $transactions,
        array $summary,
        ?string $period = null,
        ?string $filename = null
    ) {
        if (!$filename) {
            $periodSlug = $period ? str_replace(' ', '_', strtolower($period)) : 'report';
            $filename = "transaction_report_{$vessel->id}_{$periodSlug}.pdf";
        }

        $pdf = self::generate($vessel, $transactions, $summary, $period, null, null);
        return $pdf->stream($filename);
    }
}


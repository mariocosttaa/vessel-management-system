<?php
namespace App\Pdf;

use App\Models\Movimentation;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class MovimentationPdf
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
        bool $enableColors = false,
        ?User $user = null
    ) {
        // Load relationships for transactions
        $transactions->load('category');

        // Translate title and subtitle if user is provided
        if ($user && $user->language) {
            $originalLocale = App::getLocale();
            App::setLocale($user->language);

            if ($title === 'Transaction Report') {
                $title = trans('pdfs.Transaction Report');
            }
            if ($subtitle === 'Movements and Transactions Overview') {
                $subtitle = trans('pdfs.Movements and Transactions Overview');
            }

            App::setLocale($originalLocale);
        }

        return PdfService::generate('pdf.reports.transaction-report', [
            'vessel'       => $vessel,
            'transactions' => $transactions,
            'summary'      => $summary,
            'period'       => $period,
            'startDate'    => $startDate,
            'endDate'      => $endDate,
            'title'        => $title,
            'subtitle'     => $subtitle,
            'enableColors' => $enableColors,
            'user'         => $user,
        ]);
    }

    /**
     * Generate transaction report PDF from request filters.
     *
     * @param Request $request
     * @param Vessel $vessel
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generateFromRequest(Request $request, Vessel $vessel, ?User $user = null)
    {
        // Get user from request if not provided
        if (! $user) {
            $user = $request->user();
        }

        // Translate period text if user is provided
        $allTransactionsText = 'All Transactions';
        if ($user && $user->language) {
            $originalLocale = App::getLocale();
            App::setLocale($user->language);
            $allTransactionsText = trans('pdfs.All Transactions');
            App::setLocale($originalLocale);
        }

        // If 'all' parameter is set, get ALL transactions for testing pagination
        if ($request->get('all') === 'true') {
            $transactions = Movimentation::query()
                ->where('vessel_id', $vessel->id)
                ->with(['category'])
                ->orderBy('transaction_date', 'desc')
                ->get();
            $period = $allTransactionsText;
        } else {
            // Get filter parameters
            $month     = $request->get('month', now()->month);
            $year      = $request->get('year', now()->year);
            $startDate = $request->get('start_date');
            $endDate   = $request->get('end_date');

            // Build query
            $query = Movimentation::query()
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
            if (! $startDate || ! $endDate) {
                $startDate = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
                $endDate   = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year)); // Last day of month
            }
        }

        // Calculate summary
        $totalIncome   = $transactions->where('type', 'income')->sum('total_amount');
        $totalExpenses = $transactions->where('type', 'expense')->sum('total_amount');
        $netBalance    = $totalIncome - $totalExpenses;

        $summary = [
            'total_income'   => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_balance'    => $netBalance,
            'total_count'    => $transactions->count(),
        ];

        // Calculate start and end dates for "All Transactions"
        if ($request->get('all') === 'true' && $transactions->count() > 0) {
            $startDate = $transactions->min('transaction_date')->format('Y-m-d');
            $endDate   = $transactions->max('transaction_date')->format('Y-m-d');
        }

        // Translate title and subtitle if user is provided
        $title    = 'Transaction Report';
        $subtitle = 'Movements and Transactions Overview';
        if ($user && $user->language) {
            $originalLocale = App::getLocale();
            App::setLocale($user->language);
            $title    = trans('pdfs.Transaction Report');
            $subtitle = trans('pdfs.Movements and Transactions Overview');
            App::setLocale($originalLocale);
        }

        return PdfService::generate('pdf.reports.transaction-report', [
            'vessel'       => $vessel,
            'transactions' => $transactions,
            'summary'      => $summary,
            'period'       => $period,
            'startDate'    => $startDate ?? null,
            'endDate'      => $endDate ?? null,
            'title'        => $title,
            'subtitle'     => $subtitle,
            'user'         => $user,
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
        ?string $filename = null,
        ?User $user = null
    ) {
        if (! $filename) {
            $periodSlug = $period ? str_replace(' ', '_', strtolower($period)) : 'report';
            $filename   = "transaction_report_{$vessel->id}_{$periodSlug}.pdf";
        }

        $pdf = self::generate($vessel, $transactions, $summary, $period, null, null, 'Transaction Report', 'Movements and Transactions Overview', false, $user);
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
        ?string $filename = null,
        ?User $user = null
    ) {
        if (! $filename) {
            $periodSlug = $period ? str_replace(' ', '_', strtolower($period)) : 'report';
            $filename   = "transaction_report_{$vessel->id}_{$periodSlug}.pdf";
        }

        $pdf = self::generate($vessel, $transactions, $summary, $period, null, null, 'Transaction Report', 'Movements and Transactions Overview', false, $user);
        return $pdf->stream($filename);
    }
}

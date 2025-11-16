<?php
namespace App\Pdf;

use App\Models\Maintenance;
use App\Models\User;
use App\Pdf\PdfService;
use Illuminate\Support\Facades\App;

class MaintenancePdf
{
    /**
     * Generate maintenance report PDF.
     *
     * @param Maintenance $maintenance
     * @param string $title
     * @param string|null $subtitle
     * @param bool $enableColors
     * @param User|null $user
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generate(
        Maintenance $maintenance,
        string $title = 'Maintenance Report',
        ?string $subtitle = 'Maintenance Overview',
        bool $enableColors = false,
        ?User $user = null
    ) {
        // Load all necessary relationships
        $maintenance->load([
            'vessel:id,name,currency_code,registration_number,vessel_type',
            'createdBy:id,name',
            'transactions' => function ($query) {
                $query->with(['category', 'supplier'])
                    ->orderBy('transaction_date', 'desc');
            },
        ]);

        // Calculate summary
        $transactions  = $maintenance->transactions;
        $totalExpenses = $transactions->where('type', 'expense')->sum('total_amount');
        $expenseCount  = $transactions->where('type', 'expense')->count();

        $summary = [
            'total_expenses' => $totalExpenses,
            'expense_count'  => $expenseCount,
        ];

        // Translate title and subtitle if user is provided
        if ($user && $user->language) {
            $originalLocale = App::getLocale();
            App::setLocale($user->language);

            if ($title === 'Maintenance Report') {
                $title = trans('pdfs.Maintenance Report');
            }
            if ($subtitle === 'Maintenance Overview') {
                $subtitle = trans('pdfs.Maintenance Overview');
            }

            App::setLocale($originalLocale);
        }

        return PdfService::generate('pdf.reports.maintenance-report', [
            'vessel'       => $maintenance->vessel,
            'maintenance'  => $maintenance,
            'transactions' => $transactions,
            'summary'      => $summary,
            'title'        => $title,
            'subtitle'     => $subtitle,
            'enableColors' => $enableColors,
            'user'         => $user,
        ]);
    }

    /**
     * Download maintenance report PDF.
     *
     * @param Maintenance $maintenance
     * @param string|null $filename
     * @param bool $enableColors
     * @param User|null $user
     * @return \Illuminate\Http\Response
     */
    public static function download(
        Maintenance $maintenance,
        ?string $filename = null,
        bool $enableColors = false,
        ?User $user = null
    ) {
        if (! $filename) {
            $filename = "maintenance_report_{$maintenance->maintenance_number}_" . date('Y-m-d') . '.pdf';
        }

        $pdf = self::generate($maintenance, 'Maintenance Report', 'Maintenance Overview', $enableColors, $user);
        return $pdf->download($filename);
    }

    /**
     * Stream maintenance report PDF (display in browser).
     *
     * @param Maintenance $maintenance
     * @param string|null $filename
     * @param bool $enableColors
     * @param User|null $user
     * @return \Illuminate\Http\Response
     */
    public static function stream(
        Maintenance $maintenance,
        ?string $filename = null,
        bool $enableColors = false,
        ?User $user = null
    ) {
        if (! $filename) {
            $filename = "maintenance_report_{$maintenance->maintenance_number}_" . date('Y-m-d') . '.pdf';
        }

        $pdf = self::generate($maintenance, 'Maintenance Report', 'Maintenance Overview', $enableColors, $user);
        return $pdf->stream($filename);
    }
}

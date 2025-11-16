<?php
namespace App\Pdf;

use App\Actions\PdfAction;
use App\Models\Movimentation;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Support\Facades\App;

class VatReportPdf
{
    /**
     * Generate VAT report PDF.
     *
     * @param Vessel $vessel
     * @param int $year
     * @param int $month
     * @param User|null $user
     * @param bool $enableColors
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

        // Get all transactions with VAT for the month/year (only income transactions have VAT)
        $transactions = Movimentation::where('vessel_id', $vessel->id)
            ->where('transaction_month', $month)
            ->where('transaction_year', $year)
            ->where('type', 'income')
            ->where('status', 'completed')
            ->where('vat_amount', '>', 0)
            ->with([
                'category:id,name,type,color',
                'vatProfile:id,name,percentage,code,country_id',
                'vatProfile.country:id,name,code',
                'marea:id,marea_number,name',
            ])
            ->get();

        // Calculate summary statistics
        $totalVat           = $transactions->sum('vat_amount');
        $totalBaseAmount    = $transactions->sum('amount');
        $totalAmountWithVat = $transactions->sum('total_amount');
        $transactionCount   = $transactions->count();

        // Get VAT breakdown by VAT profile
        $vatProfileBreakdown = $transactions->groupBy('vat_profile_id')->map(function ($profileTransactions, $profileId) {
            $vatProfile = $profileTransactions->first()->vatProfile;
            return [
                'vat_profile_id'         => $profileId,
                'vat_profile_name'       => $vatProfile ? $vatProfile->name : trans('pdfs.Unknown'),
                'vat_profile_percentage' => $vatProfile ? (float) $vatProfile->percentage : 0,
                'vat_profile_code'       => $vatProfile ? $vatProfile->code : null,
                'country'                => $vatProfile && $vatProfile->country ? [
                    'id'   => $vatProfile->country->id,
                    'name' => $vatProfile->country->name,
                    'code' => $vatProfile->country->code,
                ] : null,
                'total_base_amount'      => $profileTransactions->sum('amount'),
                'total_vat_amount'       => $profileTransactions->sum('vat_amount'),
                'total_amount_with_vat'  => $profileTransactions->sum('total_amount'),
                'transaction_count'      => $profileTransactions->count(),
            ];
        })->values()->sortByDesc('total_vat_amount')->values();

        // Get VAT breakdown by category
        $categoryBreakdown = $transactions->groupBy('category_id')->map(function ($categoryTransactions, $categoryId) {
            $category = $categoryTransactions->first()->category;
            return [
                'category_id'           => $categoryId,
                'category_name'         => $category ? $category->translated_name : trans('pdfs.Uncategorized'),
                'category_color'        => $category ? $category->color : null,
                'total_base_amount'     => $categoryTransactions->sum('amount'),
                'total_vat_amount'      => $categoryTransactions->sum('vat_amount'),
                'total_amount_with_vat' => $categoryTransactions->sum('total_amount'),
                'transaction_count'     => $categoryTransactions->count(),
            ];
        })->values()->sortByDesc('total_vat_amount')->values();

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
                'base_amount'    => $dayTransactions->sum('amount'),
                'vat_amount'     => $dayTransactions->sum('vat_amount'),
                'total_amount'   => $dayTransactions->sum('total_amount'),
                'count'          => $dayTransactions->count(),
            ];
        })->sortBy('date')->values();

        // Get VAT by marea
        $mareaBreakdown = $transactions->whereNotNull('marea_id')
            ->groupBy('marea_id')
            ->map(function ($mareaTransactions, $mareaId) {
                $marea = $mareaTransactions->first()->marea;
                return [
                    'marea_id'              => $mareaId,
                    'marea_number'          => $marea ? $marea->marea_number : trans('pdfs.Unknown'),
                    'marea_name'            => $marea ? $marea->name : null,
                    'total_base_amount'     => $mareaTransactions->sum('amount'),
                    'total_vat_amount'      => $mareaTransactions->sum('vat_amount'),
                    'total_amount_with_vat' => $mareaTransactions->sum('total_amount'),
                    'transaction_count'     => $mareaTransactions->count(),
                ];
            })->values()->sortByDesc('total_vat_amount')->values();

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
            ->where('type', 'income')
            ->where('status', 'completed')
            ->where('vat_amount', '>', 0)
            ->get();

        $previousMonthVat  = $previousMonthTransactions->sum('vat_amount');
        $previousMonthBase = $previousMonthTransactions->sum('amount');

        $vatChange = $previousMonthVat > 0
            ? (($totalVat - $previousMonthVat) / $previousMonthVat) * 100
            : 0;
        $baseChange = $previousMonthBase > 0
            ? (($totalBaseAmount - $previousMonthBase) / $previousMonthBase) * 100
            : 0;

        // Prepare summary
        $summary = [
            'total_vat'             => $totalVat,
            'total_base_amount'     => $totalBaseAmount,
            'total_amount_with_vat' => $totalAmountWithVat,
            'transaction_count'     => $transactionCount,
            'vat_change'            => round($vatChange, 2),
            'base_change'           => round($baseChange, 2),
        ];

        // Get transactions list for detailed view
        $transactionsList = $transactions->map(function ($transaction) {
            $date          = $transaction->transaction_date;
            $formattedDate = null;

            if ($date) {
                try {
                    if ($date instanceof \Carbon\Carbon  || $date instanceof \DateTimeInterface) {
                        $formattedDate = \Carbon\Carbon::instance($date)->format('Y-m-d');
                    } else {
                        $formattedDate = \Carbon\Carbon::parse((string) $date)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    $formattedDate = null;
                }
            }

            return [
                'id'                 => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'transaction_date'   => $formattedDate,
                'description'        => $transaction->description,
                'reference'          => $transaction->reference,
                'base_amount'        => $transaction->amount,
                'vat_amount'         => $transaction->vat_amount,
                'total_amount'       => $transaction->total_amount,
                'currency'           => $transaction->currency,
                'category'           => $transaction->category ? [
                    'id'    => $transaction->category->id,
                    'name'  => $transaction->category->translated_name,
                    'color' => $transaction->category->color,
                ] : null,
                'vat_profile'        => $transaction->vatProfile ? [
                    'id'         => $transaction->vatProfile->id,
                    'name'       => $transaction->vatProfile->name,
                    'percentage' => (float) $transaction->vatProfile->percentage,
                    'code'       => $transaction->vatProfile->code,
                ] : null,
                'marea'              => $transaction->marea ? [
                    'id'           => $transaction->marea->id,
                    'marea_number' => $transaction->marea->marea_number,
                    'name'         => $transaction->marea->name,
                ] : null,
            ];
        })->sortByDesc('transaction_date')->values();

        // Translate title and subtitle
        $title    = trans('pdfs.VAT Report');
        $subtitle = trans('pdfs.Comprehensive VAT overview for') . " {$monthLabel} {$year}";

        return PdfAction::generate('pdf.reports.vat-report', [
            'vessel'              => $vessel,
            'year'                => $year,
            'month'               => $month,
            'monthLabel'          => $monthLabel,
            'defaultCurrency'     => $defaultCurrency,
            'summary'             => $summary,
            'vatProfileBreakdown' => $vatProfileBreakdown,
            'categoryBreakdown'   => $categoryBreakdown,
            'dailyBreakdown'      => $dailyBreakdown,
            'mareaBreakdown'      => $mareaBreakdown,
            'transactions'        => $transactionsList,
            'title'               => $title,
            'subtitle'            => $subtitle,
            'enableColors'        => $enableColors,
            'user'                => $user,
        ]);
    }

    /**
     * Download VAT report PDF.
     *
     * @param Vessel $vessel
     * @param int $year
     * @param int $month
     * @param string|null $filename
     * @param User|null $user
     * @param bool $enableColors
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
            $filename   = "vat_report_{$monthLabel}_{$year}_" . date('Y-m-d') . '.pdf';
        }

        $pdf = self::generate($vessel, $year, $month, $user, $enableColors);
        return $pdf->download($filename);
    }

    /**
     * Stream VAT report PDF (display in browser).
     *
     * @param Vessel $vessel
     * @param int $year
     * @param int $month
     * @param string|null $filename
     * @param User|null $user
     * @param bool $enableColors
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
            $filename   = "vat_report_{$monthLabel}_{$year}_" . date('Y-m-d') . '.pdf';
        }

        $pdf = self::generate($vessel, $year, $month, $user, $enableColors);
        return $pdf->stream($filename);
    }
}

<?php
namespace App\Pdf;

use App\Models\Marea;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Support\Facades\App;

class MareaPdf
{
    /**
     * Generate marea report PDF.
     *
     * @param Marea $marea
     * @param string $title
     * @param string|null $subtitle
     * @param bool $enableColors
     * @param User|null $user
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generate(
        Marea $marea,
        string $title = 'Marea Report',
        ?string $subtitle = 'Fishing Trip Overview',
        bool $enableColors = false,
        ?User $user = null
    ) {
        // Load all necessary relationships
        $marea->load([
            'vessel:id,name,currency_code,registration_number,vessel_type',
            'distributionProfile:id,name',
            'createdBy:id,name',
            'crew'         => function ($query) {
                $query->with('user:id,name,email');
            },
            'quantityReturns',
            'transactions' => function ($query) {
                $query->with(['category', 'supplier', 'crewMember:id,name,email'])
                    ->orderBy('transaction_date', 'desc');
            },
        ]);

        // Also load crewMembers if it's a BelongsToMany relationship
        if (method_exists($marea, 'crewMembers')) {
            $marea->load('crewMembers:id,name,email');
        }

        // Calculate distribution
        $distribution = $marea->calculateDistribution();

        // Separate transactions by type
        $incomeTransactions  = $marea->transactions->where('type', 'income');
        $expenseTransactions = $marea->transactions->where('type', 'expense');

        // Calculate summary
        $summary = [
            'total_income'   => $marea->total_income,
            'total_expenses' => $marea->total_expenses,
            'net_result'     => $marea->net_result,
            'total_count'    => $marea->transactions->count(),
            'income_count'   => $incomeTransactions->count(),
            'expense_count'  => $expenseTransactions->count(),
        ];

        // Translate title and subtitle if user is provided
        if ($user && $user->language) {
            $originalLocale = App::getLocale();
            App::setLocale($user->language);

            if ($title === 'Marea Report') {
                $title = trans('pdfs.Marea Report');
            }
            if ($subtitle === 'Fishing Trip Overview') {
                $subtitle = trans('pdfs.Fishing Trip Overview');
            }

            App::setLocale($originalLocale);
        }

        return PdfService::generate('pdf.reports.marea-report', [
            'vessel'              => $marea->vessel,
            'marea'               => $marea,
            'summary'             => $summary,
            'distribution'        => $distribution,
            'incomeTransactions'  => $incomeTransactions,
            'expenseTransactions' => $expenseTransactions,
            'title'               => $title,
            'subtitle'            => $subtitle,
            'enableColors'        => $enableColors,
            'user'                => $user,
        ]);
    }

    /**
     * Download marea report PDF.
     *
     * @param Marea $marea
     * @param string|null $filename
     * @param User|null $user
     * @return \Illuminate\Http\Response
     */
    public static function download(
        Marea $marea,
        ?string $filename = null,
        ?User $user = null
    ) {
        if (! $filename) {
            $filename = "marea_report_{$marea->marea_number}_" . date('Y-m-d') . '.pdf';
        }

        $pdf = self::generate($marea, 'Marea Report', 'Fishing Trip Overview', false, $user);
        return $pdf->download($filename);
    }

    /**
     * Stream marea report PDF (display in browser).
     *
     * @param Marea $marea
     * @param string|null $filename
     * @param User|null $user
     * @return \Illuminate\Http\Response
     */
    public static function stream(
        Marea $marea,
        ?string $filename = null,
        ?User $user = null
    ) {
        if (! $filename) {
            $filename = "marea_report_{$marea->marea_number}_" . date('Y-m-d') . '.pdf';
        }

        $pdf = self::generate($marea, 'Marea Report', 'Fishing Trip Overview', false, $user);
        return $pdf->stream($filename);
    }

    /**
     * Generate partial marea report PDF with selected sections.
     *
     * @param Marea $marea
     * @param array $sections Sections to include: expenses, expensesWithSalary, incomes, crew, quantity
     * @param string $title
     * @param string|null $subtitle
     * @param bool $enableColors
     * @param User|null $user
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generatePartial(
        Marea $marea,
        array $sections,
        string $title = 'Marea Report',
        ?string $subtitle = 'Fishing Trip Overview',
        bool $enableColors = false,
        ?User $user = null
    ) {
        // Load all necessary relationships
        $marea->load([
            'vessel:id,name,currency_code,registration_number,vessel_type',
            'distributionProfile:id,name',
            'createdBy:id,name',
            'crew'         => function ($query) {
                $query->with('user:id,name,email');
            },
            'quantityReturns',
            'transactions' => function ($query) {
                $query->with(['category', 'supplier', 'crewMember:id,name,email'])
                    ->orderBy('transaction_date', 'desc');
            },
        ]);

        // Also load crewMembers if it's a BelongsToMany relationship
        if (method_exists($marea, 'crewMembers')) {
            $marea->load('crewMembers:id,name,email');
        }

        // Translate title and subtitle if user is provided
        if ($user && $user->language) {
            $originalLocale = App::getLocale();
            App::setLocale($user->language);

            if ($title === 'Marea Report') {
                $title = trans('pdfs.Marea Report');
            }
            if ($subtitle === 'Fishing Trip Overview') {
                $subtitle = trans('pdfs.Fishing Trip Overview');
            }

            App::setLocale($originalLocale);
        }

        return PdfService::generate('pdf.reports.marea-partial', [
            'vessel'       => $marea->vessel,
            'marea'        => $marea,
            'sections'     => $sections,
            'title'        => $title,
            'subtitle'     => $subtitle,
            'enableColors' => $enableColors,
            'user'         => $user,
        ]);
    }

    /**
     * Download partial marea report PDF.
     *
     * @param Marea $marea
     * @param array $sections
     * @param string|null $filename
     * @param User|null $user
     * @return \Illuminate\Http\Response
     */
    public static function downloadPartial(
        Marea $marea,
        array $sections,
        ?string $filename = null,
        ?User $user = null,
        bool $enableColors = false
    ) {
        if (! $filename) {
            $sectionNames = [];
            if ($sections['expensesWithSalary'] ?? false) {
                $sectionNames[] = 'expenses-with-salary';
            } elseif ($sections['expenses'] ?? false) {
                $sectionNames[] = 'expenses';
            }
            if ($sections['incomes'] ?? false) {
                $sectionNames[] = 'incomes';
            }
            if ($sections['crew'] ?? false) {
                $sectionNames[] = 'crew';
            }
            if ($sections['quantity'] ?? false) {
                $sectionNames[] = 'quantity';
            }
            if ($sections['salary'] ?? false) {
                $sectionNames[] = 'salary';
            }
            $sectionsStr = ! empty($sectionNames) ? '_' . implode('_', $sectionNames) : '';
            $filename    = "marea_report_{$marea->marea_number}{$sectionsStr}_" . date('Y-m-d') . '.pdf';
        }

        $pdf = self::generatePartial($marea, $sections, 'Marea Report', 'Fishing Trip Overview', $enableColors, $user);
        return $pdf->download($filename);
    }
}

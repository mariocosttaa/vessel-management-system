<?php
namespace App\Exports;

use App\Actions\MoneyAction;
use App\Models\Movimentation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected Collection $transactions;
    protected ?string $context; // 'marea', 'maintenance', or null (general)

    public function __construct(Collection $transactions, ?string $context = null)
    {
        $this->transactions = $transactions;
        $this->context      = $context; // 'marea', 'maintenance', or null
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->transactions;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $locale = App::getLocale();
        $yes    = $locale === 'pt' ? 'Sim' : ($locale === 'es' ? 'Sí' : ($locale === 'fr' ? 'Oui' : 'Yes'));
        $no     = $locale === 'pt' ? 'Não' : ($locale === 'es' ? 'No' : ($locale === 'fr' ? 'Non' : 'No'));

        $vinculatedMarea       = $locale === 'pt' ? 'Vinculado na Marea' : ($locale === 'es' ? 'Vinculado en Marea' : ($locale === 'fr' ? 'Lié à Marea' : 'Vinculated on Marea'));
        $vinculatedMaintenance = $locale === 'pt' ? 'Vinculado na Manutenção' : ($locale === 'es' ? 'Vinculado en Mantenimiento' : ($locale === 'fr' ? 'Lié à Maintenance' : 'Vinculated on Maintenance'));

        $amountIncludesVat = $locale === 'pt' ? 'Valor já inclui IVA' : ($locale === 'es' ? 'El valor ya incluye IVA' : ($locale === 'fr' ? 'La valeur inclut déjà la TVA' : 'Amount Includes VAT'));

        $headings = [
            'Transaction Number',
            'Type',
            'Category Name',
            'Amount (Cents)',
            'Amount (Formatted)',
            'VAT Amount (Cents)',
            'VAT Amount (Formatted)',
            'Total Amount (Cents)',
            'Total Amount (Formatted)',
            'Quantity',
            'Currency',
            $amountIncludesVat,
            'Description',
            'Notes',
        ];

        // Add Marea Number and Maintenance Number columns
        $headings[] = 'Marea Number';
        $headings[] = 'Maintenance Number';

        // Conditionally add boolean columns based on context
        // If exporting from marea, don't show "Vinculated on Marea" (all are linked)
        // If exporting from maintenance, don't show "Vinculated on Maintenance" (all are linked)
        if ($this->context !== 'marea') {
            $headings[] = $vinculatedMarea;
        }
        if ($this->context !== 'maintenance') {
            $headings[] = $vinculatedMaintenance;
        }

        return $headings;
    }

    /**
     * @param Movimentation $transaction
     * @return array
     */
    public function map($transaction): array
    {
        // Load relationships if not already loaded
        if (! $transaction->relationLoaded('category')) {
            $transaction->load('category');
        }
        if (! $transaction->relationLoaded('marea')) {
            $transaction->load('marea');
        }
        if (! $transaction->relationLoaded('maintenance')) {
            $transaction->load('maintenance');
        }

        // Get locale for Yes/No translations
        $locale = App::getLocale();
        $yes    = $locale === 'pt' ? 'Sim' : ($locale === 'es' ? 'Sí' : ($locale === 'fr' ? 'Oui' : 'Yes'));
        $no     = $locale === 'pt' ? 'Não' : ($locale === 'es' ? 'No' : ($locale === 'fr' ? 'Non' : 'No'));

        // For income transactions, determine if amount includes VAT
        // This is calculated: if type is income and vat_amount > 0, we check if the stored amount
        // represents the base (VAT excluded) or total (VAT included)
        // Since we store base amount when VAT is included, we can't perfectly determine this,
        // but we can export a reasonable default: if it's income with VAT, assume it was included
        // (this is a best guess - the actual value should be set during import)
        $amountIncludesVat = false;
        if ($transaction->type === 'income' && ($transaction->vat_amount ?? 0) > 0) {
            // For income with VAT, we'll export as "No" (VAT excluded) by default
            // The user can change this during import if needed
            // This is because we store the base amount when VAT is included
            $amountIncludesVat = false;
        }

        $data = [
            $transaction->transaction_number,
            $transaction->type, // income, expense, or transfer
            $transaction->category?->translated_name ?? $transaction->category?->name ?? null,
            $transaction->amount, // Amount in cents (integer)
            MoneyAction::format($transaction->amount, $transaction->house_of_zeros ?? 2, $transaction->currency, true),
            $transaction->vat_amount ?? 0, // VAT Amount in cents (integer)
            MoneyAction::format($transaction->vat_amount ?? 0, $transaction->house_of_zeros ?? 2, $transaction->currency, true),
            $transaction->total_amount, // Total Amount in cents (integer)
            MoneyAction::format($transaction->total_amount, $transaction->house_of_zeros ?? 2, $transaction->currency, true),
            $transaction->quantity,
            $transaction->currency,
            $amountIncludesVat ? $yes : $no, // Amount Includes VAT (only relevant for income)
            $transaction->description,
            $transaction->notes,
            $transaction->marea?->marea_number ?? null,
            $transaction->maintenance?->maintenance_number ?? null,
        ];

        // Conditionally add boolean columns based on context
        // If exporting from marea, don't show "Vinculated on Marea" (all are linked)
        // If exporting from maintenance, don't show "Vinculated on Maintenance" (all are linked)
        if ($this->context !== 'marea') {
            $data[] = $transaction->marea_id ? $yes : $no;
        }
        if ($this->context !== 'maintenance') {
            $data[] = $transaction->maintenance_id ? $yes : $no;
        }

        return $data;
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font'      => ['bold' => true],
                'fill'      => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        $widths = [
            'A' => 20, // Transaction Number
            'B' => 12, // Type
            'C' => 25, // Category Name
            'D' => 15, // Amount (Cents)
            'E' => 18, // Amount (Formatted)
            'F' => 15, // VAT Amount (Cents)
            'G' => 18, // VAT Amount (Formatted)
            'H' => 15, // Total Amount (Cents)
            'I' => 18, // Total Amount (Formatted)
            'J' => 12, // Quantity
            'K' => 10, // Currency
            'L' => 20, // Amount Includes VAT
            'M' => 30, // Description
            'N' => 30, // Notes
            'O' => 15, // Marea Number
            'P' => 20, // Maintenance Number
        ];

        // Conditionally add column widths for boolean columns based on context
        $col = 'Q';
        if ($this->context !== 'marea') {
            $widths[$col] = 20; // Vinculated on Marea
            $col          = chr(ord($col) + 1);
        }
        if ($this->context !== 'maintenance') {
            $widths[$col] = 25; // Vinculated on Maintenance
        }

        return $widths;
    }
}

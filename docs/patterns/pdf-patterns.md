# PDF Generation Patterns

This document describes the patterns and conventions for generating PDF documents in the Vessel Management System.

## 📁 Structure

```
app/
└── Pdf/
    ├── PdfService.php          # Base PDF service
    └── TransactionPdf.php     # Transaction-specific PDF generator

resources/views/
└── pdf/
    ├── layouts/
    │   └── default.blade.php  # Base PDF layout
    ├── partials/
    │   ├── header.blade.php    # PDF header
    │   └── footer.blade.php    # PDF footer
    ├── default.blade.php       # Default PDF template
    └── transactions.blade.php  # Transaction report template
```

## 🎯 PDF Service (`PdfService`)

The base service for generating PDFs from Blade views.

### Location
```
app/Pdf/PdfService.php
```

### Methods

#### `generate(string $view, array $data = [], array $options = [])`
Generate a PDF from a Blade view.

**Parameters:**
- `$view` (string): The view path (e.g., 'pdf.reports.transaction-report')
- `$data` (array): Data to pass to the view
- `$options` (array): PDF options
  - `paper`: Paper size (default: 'a4')
  - `orientation`: 'portrait' or 'landscape' (default: 'portrait')

**Returns:** `\Barryvdh\DomPDF\PDF`

**Example:**
```php
use App\Pdf\PdfService;

$pdf = PdfService::generate('pdf.reports.transaction-report', [
    'vessel' => $vessel,
    'transactions' => $transactions,
    'summary' => $summary,
]);
```

#### `download(string $view, array $data = [], string $filename = 'document.pdf', array $options = [])`
Generate PDF and return as download response.

**Example:**
```php
return PdfService::download('pdf.reports.transaction-report', $data, 'report.pdf');
```

#### `stream(string $view, array $data = [], string $filename = 'document.pdf', array $options = [])`
Generate PDF and return as inline response (display in browser).

**Example:**
```php
return PdfService::stream('pdf.reports.transaction-report', $data, 'report.pdf');
```

#### `save(string $view, array $data = [], string $path = 'pdfs/document.pdf', array $options = [])`
Generate PDF and save to storage.

**Example:**
```php
$path = PdfService::save('pdf.reports.transaction-report', $data, 'pdfs/report.pdf');
```

## 📄 PDF Generator Classes

Each PDF type should have its own generator class in `app/Pdf/`.

### Naming Convention
- Class name: `{Type}Pdf` (e.g., `TransactionPdf`, `InvoicePdf`)
- File name: `{Type}Pdf.php`

### Structure Pattern

```php
<?php

namespace App\Pdf;

use App\Models\Vessel;
use App\Pdf\PdfService;
use Illuminate\Http\Request;

class TransactionPdf
{
    /**
     * Generate PDF.
     *
     * @param Vessel $vessel
     * @param mixed $data
     * @param array $options
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generate(Vessel $vessel, $data, array $options = [])
    {
        return PdfService::generate('pdf.reports.transaction-report', [
            'vessel' => $vessel,
            'data' => $data,
            // ... other data
        ], $options);
    }

    /**
     * Generate from request.
     *
     * @param Request $request
     * @param Vessel $vessel
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generateFromRequest(Request $request, Vessel $vessel)
    {
        // Process request filters
        // Build query
        // Calculate summary
        // Return PDF
    }

    /**
     * Download PDF.
     *
     * @param Vessel $vessel
     * @param mixed $data
     * @param string|null $filename
     * @return \Illuminate\Http\Response
     */
    public static function download(Vessel $vessel, $data, ?string $filename = null)
    {
        $pdf = self::generate($vessel, $data);
        return $pdf->download($filename ?? self::generateFilename($vessel, $data));
    }

    /**
     * Stream PDF (display in browser).
     *
     * @param Vessel $vessel
     * @param mixed $data
     * @param string|null $filename
     * @return \Illuminate\Http\Response
     */
    public static function stream(Vessel $vessel, $data, ?string $filename = null)
    {
        $pdf = self::generate($vessel, $data);
        return $pdf->stream($filename ?? self::generateFilename($vessel, $data));
    }
}
```

## 🎨 PDF Templates

### Template Location
```
resources/views/pdf/{template-name}.blade.php
```

### Template Structure

All PDF templates should extend the default layout:

```blade
@extends('pdf.layouts.default')

@section('title', $title ?? 'Document')

@section('content')
    {{-- Your content here --}}
@endsection
```

### Available Variables

The default layout provides:
- `$vessel` - Current vessel model
- `$title` - Document title
- `$subtitle` - Document subtitle (optional)
- `$pdf` - PDF object (for page numbers, etc.)

### Header Partial

The header includes:
- System name (from config)
- Vessel information (name, registration, type)
- Generation timestamp and user info
- Document title and subtitle

### Footer Partial

The footer includes:
- System name and copyright
- Page numbers (when using PDF library)
- Generation timestamp

## 📋 Transaction PDF Example

### Complete Example

```php
<?php

namespace App\Http\Controllers;

use App\Pdf\TransactionPdf;
use App\Models\Vessel;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function downloadPdf(Request $request, Vessel $vessel)
    {
        return TransactionPdf::generateFromRequest($request, $vessel)
            ->download('transaction_report.pdf');
    }

    public function viewPdf(Request $request, Vessel $vessel)
    {
        return TransactionPdf::generateFromRequest($request, $vessel)
            ->stream('transaction_report.pdf');
    }
}
```

### Route Example

```php
Route::get('/transactions/pdf', [TransactionController::class, 'downloadPdf'])
    ->name('panel.transactions.pdf');
```

## 🎯 Best Practices

### 1. Always Load Relationships

When passing models to PDF views, eager load relationships:

```php
$transactions = Transaction::with(['category', 'supplier'])->get();
```

### 2. Calculate Summaries Before Passing to View

Calculate all summary data in the controller/PDF class, not in the view:

```php
$summary = [
    'total_income' => $transactions->where('type', 'income')->sum('total_amount'),
    'total_expenses' => $transactions->where('type', 'expense')->sum('total_amount'),
    'net_balance' => $totalIncome - $totalExpenses,
];
```

### 3. Use Money Formatting

Always format money values using `MoneyAction::format()`:

```php
use App\Actions\MoneyAction;

$amount = MoneyAction::format($transaction->total_amount, $houseOfZeros, $currency, true);
```

### 4. Handle Empty Data

Always check for empty data in templates:

```blade
@if(isset($transactions) && $transactions->count() > 0)
    {{-- Display transactions --}}
@else
    <p>No transactions found.</p>
@endif
```

### 5. Consistent Styling

- Use inline styles (required for PDF rendering)
- All text should be black except system name (blue)
- Use consistent padding (5mm for PDF, 8px for browser preview)
- Remove unnecessary borders and decorative elements

### 6. Page Margins

Respect page margins consistently:
- PDF: 5mm left/right padding
- Browser preview: 8px left/right padding
- All sections (header, content, footer) should align

## 🔧 PDF Options

### Paper Sizes
- `a4` (default)
- `letter`
- `legal`
- `a3`
- Custom sizes

### Orientation
- `portrait` (default)
- `landscape`

### Example with Options

```php
$pdf = PdfService::generate('pdf.reports.transaction-report', $data, [
    'paper' => 'a4',
    'orientation' => 'landscape',
]);
```

## 📝 Template Variables

### Required Variables
- `$vessel` - Vessel model (for header information)

### Common Variables
- `$title` - Document title
- `$subtitle` - Document subtitle
- `$period` - Period information (e.g., "November 2025")
- `$data` - Main data array
- `$summary` - Summary/statistics array

## 🚫 Common Mistakes to Avoid

❌ **Don't use external CSS files** - Use inline styles only
❌ **Don't use JavaScript** - PDFs don't execute JavaScript
❌ **Don't use complex CSS** - Stick to basic CSS properties
❌ **Don't forget to load relationships** - Always eager load
❌ **Don't calculate in views** - Do calculations in controllers
❌ **Don't use absolute URLs for images** - Use relative paths or base64
❌ **Don't forget page margins** - Always respect consistent padding

## ✅ Checklist for New PDF Templates

- [ ] Create PDF generator class in `app/Pdf/`
- [ ] Create Blade template in `resources/views/pdf/`
- [ ] Template extends `pdf.layouts.default`
- [ ] All text is black except system name (blue)
- [ ] Consistent padding (5mm PDF, 8px browser)
- [ ] Proper money formatting using `MoneyAction`
- [ ] Relationships are eager loaded
- [ ] Summary calculated before passing to view
- [ ] Empty data handling implemented
- [ ] Test both HTML preview and PDF generation

## 📚 Related Documentation

- [Controller Patterns](controller-patterns.md) - How to use PDFs in controllers
- [Money Handling](money-handling.md) - Money formatting for PDFs
- [Layout Patterns](layout-patterns.md) - Design system for PDFs


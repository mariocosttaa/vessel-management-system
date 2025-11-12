# PDF Template System - Quick Start Guide

## 📋 Overview

The PDF template system provides a professional, reusable structure for generating PDF reports with:
- ✅ Fixed headers on all pages
- ✅ Fixed footers on all pages  
- ✅ Automatic pagination
- ✅ Table header repetition
- ✅ White backgrounds throughout
- ✅ Consistent spacing and typography

## 🚀 Quick Usage

### Generate Transaction Report

```php
use App\Pdf\TransactionPdf;

// Generate PDF
$pdf = TransactionPdf::generate(
    vessel: $vessel,
    transactions: $transactions,
    summary: $summary,
    period: 'November 2025',
    startDate: '2025-11-01',
    endDate: '2025-11-30'
);

// Stream in browser
return $pdf->stream('transaction_report.pdf');

// Or download
return $pdf->download('transaction_report.pdf');
```

### Generate Custom Report

```php
use App\Pdf\PdfService;

$pdf = PdfService::generate('pdf.reports.my-report', [
    'vessel' => $vessel,
    'title' => 'My Report Title',
    'subtitle' => 'Report Subtitle',
    // ... your data
]);

return $pdf->stream('my-report.pdf');
```

## 📁 Template Structure

```
resources/views/pdf/
├── layouts/
│   └── base.blade.php              # Base layout (header/footer space)
├── reports/
│   └── transaction-report.blade.php # Transaction report template
└── partials/                        # Reusable partials
```

## 📖 Full Documentation

See **[docs/pdf-template-system.md](docs/pdf-template-system.md)** for complete documentation including:
- Header/Footer system explanation
- Pagination patterns
- Styling guidelines
- Best practices
- Troubleshooting

## 🎯 Key Features

### Header (Drawn on Every Page)
- System name (blue, bold)
- Vessel information (separate lines)
- Report title (black, bold)
- Subtitle
- Generation info (right side)

### Footer (Drawn on Every Page)
- Copyright notice (left)
- Page numbers and generation date (right)

### Body Content
- White background throughout
- Proper spacing
- Table pagination support
- Header repetition on new pages

## 💡 Example: Creating New Report

1. **Create template:**
```blade
{{-- resources/views/pdf/reports/my-report.blade.php --}}
@extends('pdf.layouts.base')

@section('title', $title ?? 'My Report')

@section('content')
    <div class="report-content">
        <h2>My Report Content</h2>
        <!-- Your content here -->
    </div>
@endsection
```

2. **Generate PDF:**
```php
$pdf = PdfService::generate('pdf.reports.my-report', [
    'vessel' => $vessel,
    'title' => 'My Report',
]);
```

That's it! The header and footer are automatically added by `PdfService`.


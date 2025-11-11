{{--
    Base PDF Layout Template

    This is the foundation template for all PDF reports in the system.
    It provides:
    - Fixed header on all pages (drawn via page_script)
    - Fixed footer on all pages (drawn via page_script)
    - Proper pagination support
    - White background throughout
    - Consistent spacing and typography

    The header and footer are drawn using DomPDF's page_script callback
    in PdfService, which ensures they appear on every page correctly.

    Page Structure:
    - @page CSS rules define margins for header/footer space
    - .content-spacer provides minimal gap on first page
    - .content contains the actual report content
    - Content can break across pages naturally

    Usage:
    @extends('pdf.layouts.base')
    @section('title', 'Your Report Title')
    @section('content')
        <!-- Your report content here -->
    @endsection
--}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="background: #fff;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            background: #fff !important;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #000;
            background: #fff !important;
            letter-spacing: 0.05em;
        }

        /* Page Margins - Reserve space for header and footer */
        @page {
            margin-top: 35mm !important; /* More space for header on ALL pages (increased for better spacing) */
            margin-bottom: 10mm !important; /* Footer space for subsequent pages */
            margin-left: 5mm;
            margin-right: 5mm;
            padding: 0; /* No padding on page */
            background: #fff;
        }

        /* First page has extra footer space to prevent content overlap */
        @page:first {
            margin-top: 30mm !important; /* Less margin-top on first page (header is smaller) */
            margin-bottom: 20mm !important; /* Extra footer space on first page */
        }

        body {
            margin: 0;
            padding: 0;
            position: relative;
            background: #fff;
        }

        /* Header and footer are drawn via page_script, so hide HTML versions */
        .header {
            display: none;
        }

        .footer {
            display: none;
        }

        .page-wrapper {
            position: relative;
            min-height: 100%;
            background: #fff;
        }

        /* Spacer between header and content */
        .content-spacer {
            height: 8mm; /* Gap between header and body content on first page */
            page-break-after: avoid;
        }

        /* More spacing needed on subsequent pages when content starts */
        .content > *:first-child {
            margin-top: 0;
        }

        .content {
            padding: 0 !important;
            margin: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 0 !important; /* Ensure no left padding */
            margin-left: 0 !important; /* Ensure no left margin */
            background: #fff;
        }

        /* Force all content to start at page margin */
        .content > * {
            margin-left: 0 !important;
            padding-left: 0 !important;
        }

        /* Ensure first element on each page has proper spacing */
        .content > *:first-child {
            margin-top: 0;
        }

        /* Table pagination support */
        table {
            margin-top: 0;
            page-break-inside: auto;
        }

        tr {
            page-break-inside: auto;
            page-break-after: auto;
        }

        thead {
            display: table-header-group;
            padding-top: 0;
        }

        /* Table header appears below header on new pages - prevent overlap */
        thead tr:first-child th {
            padding-top: 30px !important; /* Increased padding to prevent header overlap on subsequent pages */
        }

        tfoot {
            display: table-footer-group;
        }

        /* Prevent empty pages */
        .page-wrapper {
            page-break-after: avoid;
        }

        /* Browser preview styles */
        @media screen {
            .header {
                position: relative;
                top: 0;
                padding: 0;
                margin-bottom: 20px;
            }

            .footer {
                position: relative;
                bottom: 0;
                padding: 0;
                margin-top: 40px;
            }

            body {
                max-width: 210mm;
                margin: 0 auto;
                padding: 20px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                background: #fff;
            }

            .content {
                background: #fff;
                padding: 0 8px;
                min-height: auto;
            }
        }

        /* Base table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background-color: #fff;
        }

        table th,
        table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            background-color: #fff;
        }

        table th {
            background-color: #fff;
            font-weight: bold;
            color: #333;
        }

        /* Utility classes */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .mb-1 { margin-bottom: 8px; }
        .mb-2 { margin-bottom: 16px; }
        .mb-3 { margin-bottom: 24px; }
        .mt-1 { margin-top: 8px; }
        .mt-2 { margin-top: 16px; }
        .mt-3 { margin-top: 24px; }

        .page-break {
            page-break-after: always;
        }

        .no-break {
            page-break-inside: avoid;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="page-wrapper">
        <div class="content-spacer"></div>
        <div class="content">
            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>


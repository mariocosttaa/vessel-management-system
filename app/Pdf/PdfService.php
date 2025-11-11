<?php

namespace App\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PdfService
{
    /**
     * Generate PDF from a Blade view.
     *
     * @param string $view The view path (e.g., 'pdf.reports.transaction-report')
     * @param array $data Data to pass to the view
     * @param array $options PDF options (paper size, orientation, etc.)
     * @return \Barryvdh\DomPDF\PDF
     */
    public static function generate(string $view, array $data = [], array $options = [])
    {
        $defaultOptions = [
            'paper' => 'a4',
            'orientation' => 'portrait',
        ];

        $options = array_merge($defaultOptions, $options);

        $pdf = Pdf::loadView($view, $data)
            ->setPaper($options['paper'], $options['orientation']);

        // Set options for better rendering
        $pdf->setOption('enable-font-subsetting', true);
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        // Render the PDF first to get canvas access
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        // Get canvas
        $canvas = $dompdf->getCanvas();

        // Add header and footer to each page using page_script
        $systemName = config('app.name', 'Vessel Management System');
        $generatedAt = now()->format('d/m/Y H:i');

        // Extract header data
        $vessel = $data['vessel'] ?? null;
        $title = $data['title'] ?? 'Transaction Report';
        $subtitle = $data['subtitle'] ?? null;
        $user = Auth::user();

        // Get company logo path
        $companyLogoPath = public_path('bindamy-marea-logo-light.png');

        // Get vessel logo path if available
        $vesselLogoPath = null;
        if ($vessel && $vessel->logo) {
            // Check if logo is a full URL
            if (filter_var($vessel->logo, FILTER_VALIDATE_URL)) {
                // For external URLs, we'd need to download first - skip for now
                $vesselLogoPath = null;
            } else {
                // Logo is stored in storage, get full path
                $vesselLogoPath = storage_path('app/public/' . $vessel->logo);
                // If file doesn't exist, set to null
                if (!file_exists($vesselLogoPath)) {
                    $vesselLogoPath = null;
                }
            }
        }

        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($systemName, $generatedAt, $vessel, $title, $subtitle, $user, $companyLogoPath, $vesselLogoPath) {
            try {
                $font = $fontMetrics->get_font("DejaVu Sans", "normal");
                $fontBold = $fontMetrics->get_font("DejaVu Sans", "bold");
                $size = 8;
                $sizeSmall = 7;

                // Get page dimensions
                $pageHeight = $canvas->get_height();
                $pageWidth = $canvas->get_width();
                $leftX = 14; // 5mm = ~14pt (matches @page margin-left: 5mm)
                $rightX = $pageWidth - 14;

                // Header Y positions (starting from top of page, fixed position)
                // Total header height should be ~38mm to match @page margin-top
                // Header is drawn in the margin area, so start at top of page
                $headerStartY = 10;
                $currentY = $headerStartY;

                // Colors - CPDF expects numeric array [r, g, b] with values 0-1
                $blackColor = array_values([0.0, 0.0, 0.0]); // Black - ensure numeric indices

                // Line 1: Draw company logo image - ALWAYS on every page
                $companyLogoWidth = 140; // ~49mm
                $companyLogoHeight = ($companyLogoWidth / 238) * 44; // Maintain aspect ratio (~26pt)

                if (file_exists($companyLogoPath)) {
                    // Draw company logo
                    $canvas->image($companyLogoPath, $leftX, $currentY, $companyLogoWidth, $companyLogoHeight);
                } else {
                    // Fallback to text if logo not found
                    $systemNameSize = 14;
                    $blueColor = array_values([37/255, 99/255, 235/255]);
                    $canvas->text($leftX, $currentY, $systemName, $fontBold, $systemNameSize, $blueColor);
                }

                $currentY += $companyLogoHeight + 12; // Space after logo (12pt gap)

                // Line 2-4: Draw vessel info on separate lines if available
                if ($vessel) {
                    // Vessel name
                    $canvas->text($leftX, $currentY, "Vessel: " . $vessel->name, $font, $sizeSmall, $blackColor);
                    $currentY += 12; // Consistent spacing after vessel name

                    // Registration number
                    if ($vessel->registration_number) {
                        $canvas->text($leftX, $currentY, "Registration: " . $vessel->registration_number, $font, $sizeSmall, $blackColor);
                        $currentY += 12; // Consistent spacing after registration
                    }

                    // Vessel type
                    if ($vessel->vessel_type) {
                        $canvas->text($leftX, $currentY, "Type: " . $vessel->vessel_type, $font, $sizeSmall, $blackColor);
                        $currentY += 16; // More space after type before Transaction Report title
                    }
                }

                // Line 5: Draw "Transaction Report" title in BLACK (not blue)
                // Add a gap before the title for better organization
                if (!empty($title)) {
                    $canvas->text($leftX, $currentY, $title, $fontBold, 12, $blackColor);
                    $currentY += 16; // More space after title
                }

                // Line 6: Draw subtitle if available
                if (!empty($subtitle)) {
                    $canvas->text($leftX, $currentY, $subtitle, $font, $size, $blackColor);
                    $currentY += 14; // Consistent space after subtitle
                }

                // Calculate generation info position and text width for centering
                $genText = "Generated: " . $generatedAt;
                if ($user) {
                    $genText .= "\nGenerated by: " . $user->name . "\nEmail: " . $user->email;
                }
                $genLines = explode("\n", $genText);

                // Find the longest line to calculate center position
                $maxLineWidth = 0;
                foreach ($genLines as $line) {
                    $lineWidth = $fontMetrics->get_text_width($line, $font, $sizeSmall);
                    if ($lineWidth > $maxLineWidth) {
                        $maxLineWidth = $lineWidth;
                    }
                }

                // Position generation info - centered on right side
                $genX = $rightX - $maxLineWidth; // Align to right but not at edge
                $genY = $headerStartY;

                // Draw vessel logo centered above generation info if available
                if ($vesselLogoPath && file_exists($vesselLogoPath)) {
                    // Vessel logo size (smaller than company logo)
                    $vesselLogoWidth = 50; // ~18mm
                    $vesselLogoHeight = $vesselLogoWidth; // Square or maintain aspect ratio

                    // Center vessel logo above generation text
                    $vesselLogoX = $genX + ($maxLineWidth / 2) - ($vesselLogoWidth / 2); // Center above text
                    $vesselLogoY = $headerStartY; // At the top
                    $canvas->image($vesselLogoPath, $vesselLogoX, $vesselLogoY, $vesselLogoWidth, $vesselLogoHeight);

                    // Start generation info below vessel logo
                    $genY = $headerStartY + $vesselLogoHeight + 8; // 8pt gap below logo
                }

                // Draw generation info
                foreach ($genLines as $line) {
                    $canvas->text($genX, $genY, $line, $font, $sizeSmall, $blackColor);
                    $genY += 9; // Proper spacing between generation info lines
                }

                // Footer Y position (10mm from bottom = ~28pt)
                $footerY = $pageHeight - 28;

                // Left side footer text
                $leftText = $systemName . " © " . date('Y') . " All rights reserved";
                $canvas->text($leftX, $footerY, $leftText, $font, $size, $blackColor);

                // Right side footer text with page numbers
                $rightText = "Page {$pageNumber} of {$pageCount} | Generated on " . $generatedAt;
                $textWidth = $fontMetrics->get_text_width($rightText, $font, $size);
                $canvas->text($rightX - $textWidth, $footerY, $rightText, $font, $size, $blackColor);
            } catch (\Exception $e) {
                // Log error in development
                if (config('app.debug')) {
                    Log::error('PDF Header/Footer page_script error: ' . $e->getMessage());
                }
            }
        });

        return $pdf;
    }

    /**
     * Generate PDF and return as download response.
     *
     * @param string $view The view path
     * @param array $data Data to pass to the view
     * @param string $filename The filename for download
     * @param array $options PDF options
     * @return \Illuminate\Http\Response
     */
    public static function download(string $view, array $data = [], string $filename = 'document.pdf', array $options = [])
    {
        $pdf = self::generate($view, $data, $options);
        return $pdf->download($filename);
    }

    /**
     * Generate PDF and return as inline response (display in browser).
     *
     * @param string $view The view path
     * @param array $data Data to pass to the view
     * @param string $filename The filename
     * @param array $options PDF options
     * @return \Illuminate\Http\Response
     */
    public static function stream(string $view, array $data = [], string $filename = 'document.pdf', array $options = [])
    {
        $pdf = self::generate($view, $data, $options);
        return $pdf->stream($filename);
    }

    /**
     * Generate PDF and save to storage.
     *
     * @param string $view The view path
     * @param array $data Data to pass to the view
     * @param string $path Storage path
     * @param array $options PDF options
     * @return string The saved file path
     */
    public static function save(string $view, array $data = [], string $path = 'pdfs/document.pdf', array $options = [])
    {
        $pdf = self::generate($view, $data, $options);
        $fullPath = storage_path('app/' . $path);
        $pdf->save($fullPath);
        return $fullPath;
    }
}


<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register custom fonts with DomPDF
        $this->registerCustomFonts();
    }

    /**
     * Register custom fonts with DomPDF.
     */
    protected function registerCustomFonts(): void
    {
        $fontDir = resource_path('views/pdf/fonts');

        if (!file_exists($fontDir . '/DejaVuSans.ttf')) {
            return;
        }

        // DomPDF will automatically discover fonts in the configured font directory
        // We just need to ensure the font files are in the right place
        // The font will be available as 'DejaVu Sans' once DomPDF processes it
    }
}


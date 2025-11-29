<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TranslationRemoveCommand extends Command
{
    protected $signature = 'translation:remove
                            {key : The translation key to remove (supports wildcards with *)}
                            {--locale=* : Specific locales to remove from (en, pt, es, fr). Default: all}
                            {--file=* : Specific backend files to remove from (notifications, emails, pdfs, etc.). Default: all}
                            {--dry-run : Preview changes without making them}
                            {--frontend-only : Only remove from frontend JSON files}
                            {--backend-only : Only remove from backend PHP files}';

    protected $description = 'Remove translation keys from both frontend JSON files and backend PHP files';

    protected array $locales = ['en', 'pt', 'es', 'fr'];
    protected array $backendFiles = [];

    public function handle(): int
    {
        $key = $this->argument('key');
        $isDryRun = $this->option('dry-run');
        $frontendOnly = $this->option('frontend-only');
        $backendOnly = $this->option('backend-only');

        // Determine locales to process
        $localesToProcess = $this->option('locale');
        if (empty($localesToProcess)) {
            $localesToProcess = $this->locales;
        } else {
            // Validate locales
            $invalidLocales = array_diff($localesToProcess, $this->locales);
            if (!empty($invalidLocales)) {
                $this->error('Invalid locales: ' . implode(', ', $invalidLocales));
                $this->info('Valid locales: ' . implode(', ', $this->locales));
                return Command::FAILURE;
            }
        }

        // Get backend files to process
        $this->backendFiles = $this->getBackendFiles($localesToProcess[0]);
        $filesToProcess = $this->option('file');
        if (!empty($filesToProcess)) {
            // Validate files
            $invalidFiles = array_diff($filesToProcess, $this->backendFiles);
            if (!empty($invalidFiles)) {
                $this->error('Invalid backend files: ' . implode(', ', $invalidFiles));
                $this->info('Valid files: ' . implode(', ', $this->backendFiles));
                return Command::FAILURE;
            }
            $this->backendFiles = $filesToProcess;
        }

        $this->info('🔍 Removing Translation Key: ' . $key);
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }
        $this->newLine();

        $totalRemoved = 0;

        // Process frontend JSON files
        if (!$backendOnly) {
            $removed = $this->removeFromFrontend($key, $localesToProcess, $isDryRun);
            $totalRemoved += $removed;
        }

        // Process backend PHP files
        if (!$frontendOnly) {
            $removed = $this->removeFromBackend($key, $localesToProcess, $isDryRun);
            $totalRemoved += $removed;
        }

        $this->newLine();
        if ($isDryRun) {
            $this->info("📊 Preview: Would remove {$totalRemoved} translation(s)");
            $this->info('Run without --dry-run to apply changes');
        } else {
            $this->info("✅ Successfully removed {$totalRemoved} translation(s)");
        }

        return Command::SUCCESS;
    }

    /**
     * Remove translation key from frontend JSON files
     */
    protected function removeFromFrontend(string $key, array $locales, bool $isDryRun): int
    {
        $this->info('📄 Processing Frontend JSON Files:');
        $removed = 0;

        foreach ($locales as $locale) {
            $filePath = resource_path("js/i18n/locales/{$locale}.json");

            if (!File::exists($filePath)) {
                $this->warn("  ⚠️  File not found: {$filePath}");
                continue;
            }

            $content = File::get($filePath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("  ❌ Invalid JSON in {$filePath}: " . json_last_error_msg());
                continue;
            }

            $keysToRemove = $this->findMatchingKeys($key, array_keys($data));
            $removedCount = count($keysToRemove);

            if ($removedCount > 0) {
                foreach ($keysToRemove as $keyToRemove) {
                    unset($data[$keyToRemove]);
                    $this->line("  🗑️  [{$locale}] Removing: {$keyToRemove}");
                }

                if (!$isDryRun) {
                    // Preserve JSON formatting
                    $newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    // Add trailing newline
                    $newContent .= "\n";
                    File::put($filePath, $newContent);
                }

                $removed += $removedCount;
            } else {
                $this->line("  ℹ️  [{$locale}] No matching keys found");
            }
        }

        return $removed;
    }

    /**
     * Remove translation key from backend PHP files
     */
    protected function removeFromBackend(string $key, array $locales, bool $isDryRun): int
    {
        $this->info('📄 Processing Backend PHP Files:');
        $removed = 0;

        foreach ($locales as $locale) {
            foreach ($this->backendFiles as $file) {
                $filePath = lang_path("{$locale}/{$file}.php");

                if (!File::exists($filePath)) {
                    $this->warn("  ⚠️  File not found: {$filePath}");
                    continue;
                }

                $content = File::get($filePath);
                $data = include $filePath;

                if (!is_array($data)) {
                    $this->error("  ❌ Invalid PHP array in {$filePath}");
                    continue;
                }

                $keysToRemove = $this->findMatchingKeys($key, array_keys($data));
                $removedCount = count($keysToRemove);

                if ($removedCount > 0) {
                    foreach ($keysToRemove as $keyToRemove) {
                        unset($data[$keyToRemove]);
                        $this->line("  🗑️  [{$locale}/{$file}] Removing: {$keyToRemove}");
                    }

                    if (!$isDryRun) {
                        $newContent = $this->formatPhpArray($data, $file);
                        File::put($filePath, $newContent);
                    }

                    $removed += $removedCount;
                }
            }
        }

        return $removed;
    }

    /**
     * Find keys matching the pattern (supports wildcards)
     */
    protected function findMatchingKeys(string $pattern, array $keys): array
    {
        if (strpos($pattern, '*') === false) {
            // Exact match
            return in_array($pattern, $keys) ? [$pattern] : [];
        }

        // Wildcard pattern
        $regex = '/^' . str_replace(['*', '?'], ['.*', '.'], preg_quote($pattern, '/')) . '$/';
        return array_filter($keys, fn($key) => preg_match($regex, $key));
    }

    /**
     * Get list of backend PHP files for a locale
     */
    protected function getBackendFiles(string $locale): array
    {
        $langPath = lang_path($locale);
        if (!File::exists($langPath)) {
            return [];
        }

        $files = File::files($langPath);
        return array_map(function ($file) {
            return pathinfo($file->getFilename(), PATHINFO_FILENAME);
        }, $files);
    }

    /**
     * Format PHP array for writing to file
     */
    protected function formatPhpArray(array $data, string $fileName): string
    {
        $output = "<?php\n\nreturn [\n";

        // Sort keys for consistency
        ksort($data);

        // Find the longest key to align => operators
        $maxKeyLength = 0;
        foreach ($data as $key => $value) {
            $escapedKey = $this->escapePhpKey($key);
            $maxKeyLength = max($maxKeyLength, strlen($escapedKey));
        }

        // Add padding to align => operators (similar to existing files)
        $targetLength = max(120, $maxKeyLength + 20);

        foreach ($data as $key => $value) {
            $escapedKey = $this->escapePhpKey($key);
            $escapedValue = $this->escapePhpValue($value);

            // Pad key to align => operators
            $paddedKey = $escapedKey . str_repeat(' ', $targetLength - strlen($escapedKey));
            $output .= "    {$paddedKey} => {$escapedValue},\n";
        }

        $output .= "];\n";
        return $output;
    }

    /**
     * Escape PHP array key
     */
    protected function escapePhpKey(string $key): string
    {
        // If key contains special characters or spaces, use quotes
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
            return "'{$key}'";
        }
        return var_export($key, true);
    }

    /**
     * Escape PHP array value
     */
    protected function escapePhpValue($value): string
    {
        return var_export($value, true);
    }
}


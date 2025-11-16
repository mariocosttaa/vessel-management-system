<?php

namespace App\Actions\Tenant;

use App\Actions\General\EasyHashAction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TenantFileAction
{
    /**
     * Save a file for a vessel.
     *
     * @param int|string $vesselId
     * @param UploadedFile $file
     * @param bool $isPublic
     * @param ?string $path
     * @param ?string $fileName
     * @param ?string $extension
     * @return object {url: string, local_path: string, extension: string, size: int, type: string} File info object
     */
    public static function save($vesselId, UploadedFile $file, bool $isPublic = false, ?string $path = null, ?string $fileName = null, ?string $extension = null): object
    {
        $disk = $isPublic ? 'public' : 'local';
        $basePath = $isPublic ? "vessels/{$vesselId}" : "vessels/{$vesselId}";
        $fullPath = $basePath;
        if ($path) {
            $fullPath .= '/' . trim($path, '/');
        }

        // Ensure base storage directory exists before accessing disk
        self::ensureStorageDirectoryExists($disk);

        // Determine file name
        $ext = $extension ?: $file->getClientOriginalExtension();
        $name = $fileName ?: Str::random(21);
        $finalName = $name . '.' . $ext;

        // Ensure directory exists
        Storage::disk($disk)->makeDirectory($fullPath);

        // Store file
        $storedPath = $file->storeAs($fullPath, $finalName, $disk);
        $size = $file->getSize();
        $localPath = $fullPath . '/' . $finalName;

        // Return relative path instead of full URL
        $vesselIdHashed = EasyHashAction::encode($vesselId, 'vessel-id');
        $insidePath = $path ? trim($path, '/') . '/' . $finalName : $finalName;

        if ($isPublic) {
            $routeUrl = route('vessel-file-show-public', ['vesselIdHashed' => $vesselIdHashed, 'filePath' => $insidePath]);
        } else {
            $routeUrl = route('vessel-file-show-private', ['vesselIdHashed' => $vesselIdHashed, 'filePath' => $insidePath]);
        }

        //remove host from this url
        $routeUrl = self::stripHostFromUrl($routeUrl);

        // Detect file type
        $mime = $file->getMimeType();
        if (str_starts_with($mime, 'image/')) {
            $type = 'image';
        } elseif (str_starts_with($mime, 'video/')) {
            $type = 'video';
        } elseif (str_starts_with($mime, 'application/pdf') || str_starts_with($mime, 'application/msword') || str_starts_with($mime, 'application/vnd')) {
            $type = 'document';
        } else {
            $type = 'other';
        }

        return (object) [
            'url' => $routeUrl,
            'local_path' => $localPath,
            'extension' => $ext,
            'size' => $size,
            'type' => $type,
        ];
    }

    /**
     * Get file content.
     *
     * @param int|string $vesselId
     * @param string $filePath
     * @param bool $isPublic
     * @return string|null
     */
    public static function get($vesselId, string $filePath, bool $isPublic = false): ?string
    {
        $disk = $isPublic ? 'public' : 'local';

        // Ensure base storage directory exists before accessing disk
        self::ensureStorageDirectoryExists($disk);

        $basePath = $isPublic ? "vessels/{$vesselId}" : "vessels/{$vesselId}";
        $fullPath = $basePath . '/' . ltrim($filePath, '/');
        return Storage::disk($disk)->exists($fullPath) ? Storage::disk($disk)->get($fullPath) : null;
    }

    /**
     * Delete a file.
     *
     * @param int|string $vesselId
     * @param string|null $filePath
     * @param string|null $fileUrl
     * @param bool $isPublic
     * @return bool
     */
    public static function delete($vesselId, ?string $filePath = null, ?string $fileUrl = null, bool $isPublic = false): bool
    {
        $disk = $isPublic ? 'public' : 'local';

        // Ensure base storage directory exists before accessing disk
        self::ensureStorageDirectoryExists($disk);

        $basePath = "vessels/{$vesselId}";

        // Determine the file path to delete
        $pathToDelete = null;

        if ($filePath) {
            // If filePath is provided, use it
            if (str_starts_with($filePath, 'vessels/')) {
                // If it's a full storage path, extract the relative path
                // Handle both formats: "vessels/{vesselId}/logos/..." and "vessels/logos/..."
                $pathToDelete = preg_replace('/^vessels\/\d+\//', '', $filePath);
                // Also handle old format without vessel ID: "vessels/logos/..."
                if (str_starts_with($pathToDelete, 'vessels/logos/')) {
                    $pathToDelete = preg_replace('/^vessels\/logos\//', 'logos/', $pathToDelete);
                }
            } else {
                // Use the filePath as-is
                $pathToDelete = ltrim($filePath, '/');
            }
        } elseif ($fileUrl) {
            // Handle relative paths starting with /file/
            if (str_starts_with($fileUrl, '/file/')) {
                $pathSegments = explode('/', trim($fileUrl, '/'));
                if (isset($pathSegments[2])) {
                    $pathToDelete = implode('/', array_slice($pathSegments, 2));
                }
            } elseif (filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                // If fileUrl is provided, extract the path from the URL
                $pathInfo = parse_url($fileUrl);
                $pathSegments = explode('/', trim($pathInfo['path'], '/'));

                // Handle different route patterns
                if (in_array('file', $pathSegments)) {
                    // Pattern: /file/{vesselIdHashed}/{filePath}
                    $filePathIndex = array_search('file', $pathSegments);
                    if ($filePathIndex !== false && isset($pathSegments[$filePathIndex + 2])) {
                        $pathToDelete = implode('/', array_slice($pathSegments, $filePathIndex + 2));
                    }
                } else {
                    // Fallback: try to extract from the end
                    $pathToDelete = end($pathSegments);
                }
            } else {
                // If it's not a valid URL, treat it as a relative path
                $pathToDelete = ltrim($fileUrl, '/');
            }
        }

        if (!$pathToDelete) {
            Log::warning('Could not determine file path to delete', [
                'vessel_id' => $vesselId,
                'file_path' => $filePath,
                'file_url' => $fileUrl,
                'is_public' => $isPublic
            ]);
            return false;
        }

        $fullPath = $basePath . '/' . $pathToDelete;

        // Check if file exists before trying to delete
        if (!Storage::disk($disk)->exists($fullPath)) {
            Log::warning('File does not exist for deletion', [
                'vessel_id' => $vesselId,
                'full_path' => $fullPath,
                'disk' => $disk,
                'path_to_delete' => $pathToDelete
            ]);
            return false;
        }

        $deleted = Storage::disk($disk)->delete($fullPath);

        if ($deleted) {
            Log::info('File deleted successfully', [
                'vessel_id' => $vesselId,
                'full_path' => $fullPath,
                'disk' => $disk
            ]);
        } else {
            Log::error('Failed to delete file', [
                'vessel_id' => $vesselId,
                'full_path' => $fullPath,
                'disk' => $disk
            ]);
        }

        return $deleted;
    }

    /**
     * Get the URL to access the file via controller route.
     *
     * @param int|string $vesselId
     * @param string $filePath
     * @param bool $isPublic
     * @return string
     */
    public static function show($vesselId, string $filePath, bool $isPublic = false): string
    {
        $vesselIdHashed = EasyHashAction::encode($vesselId, 'vessel-id');

        if ($isPublic) {
            return route('vessel-file-show-public', ['vesselIdHashed' => $vesselIdHashed, 'filePath' => $filePath]);
        } else {
            return route('vessel-file-show-private', ['vesselIdHashed' => $vesselIdHashed, 'filePath' => $filePath]);
        }
    }

    /**
     * Ensure the storage directory exists for the given disk.
     * This is necessary when using volume mounts that might not have the directory structure.
     *
     * @param string $disk
     * @return void
     */
    private static function ensureStorageDirectoryExists(string $disk): void
    {
        try {
            $storagePath = storage_path('app');

            // Ensure base storage/app directory exists
            if (!is_dir($storagePath)) {
                if (!mkdir($storagePath, 0775, true) && !is_dir($storagePath)) {
                    throw new \RuntimeException("Failed to create storage directory: {$storagePath}. Check volume mount permissions.");
                }
                // Try to set ownership to www-data:www-data (matches Dockerfile)
                @chown($storagePath, 'www-data');
                @chgrp($storagePath, 'www-data');
            }

            // For public disk, ensure storage/app/public exists
            if ($disk === 'public') {
                $publicPath = storage_path('app/public');
                if (!is_dir($publicPath)) {
                    if (!mkdir($publicPath, 0775, true) && !is_dir($publicPath)) {
                        throw new \RuntimeException("Failed to create public storage directory: {$publicPath}. Check volume mount permissions.");
                    }
                }
                // Ensure permissions match Dockerfile (775) - fix even if directory already exists
                @chmod($publicPath, 0775);
                // Try to set ownership to www-data:www-data (matches Dockerfile)
                @chown($publicPath, 'www-data');
                @chgrp($publicPath, 'www-data');
            }

            // For local disk, ensure storage/app/private exists
            if ($disk === 'local') {
                $privatePath = storage_path('app/private');
                if (!is_dir($privatePath)) {
                    if (!mkdir($privatePath, 0775, true) && !is_dir($privatePath)) {
                        throw new \RuntimeException("Failed to create private storage directory: {$privatePath}. Check volume mount permissions.");
                    }
                }
                // Ensure permissions match Dockerfile (775) - fix even if directory already exists
                @chmod($privatePath, 0775);
                // Try to set ownership to www-data:www-data (matches Dockerfile)
                @chown($privatePath, 'www-data');
                @chgrp($privatePath, 'www-data');
            }
        } catch (\Exception $e) {
            Log::error('Failed to ensure storage directory exists', [
                'disk' => $disk,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Re-throw to provide clear error message
            throw new \RuntimeException("Storage directory initialization failed for disk '{$disk}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Remove o host (domínio, protocolo, porta, localhost, etc) de uma URL, retornando apenas o path relativo.
     * Usa APP_URL do .env para garantir remoção correta.
     * Exemplo: https://mario.com/asas/foi12 => asas/foi12
     * @param string $url
     * @return string
     */
    private static function stripHostFromUrl(string $url): string
    {
        // Remove protocolo e domínio
        $appUrl = config('app.url');
        if ($appUrl) {
            $url = preg_replace('#^' . preg_quote($appUrl, '#') . '#i', '', $url);
        }
        // Remove http(s)://dominio(:porta)?
        $url = preg_replace('#^https?://[^/]+#i', '', $url);
        // Remove http://dominio(:porta)?
        $url = preg_replace('#^http?://[^/]+#i', '', $url);
        // Remove localhost(:porta)?
        $url = preg_replace('#^localhost(:\d+)?#i', '', $url);
        // Remove barras iniciais
        $url = ltrim($url, '/');
        return $url;
    }

    /**
     * Debug method to show how a URL would be parsed for deletion.
     *
     * @param int|string $vesselId
     * @param string $fileUrl
     * @param bool $isPublic
     * @return array
     */
    public static function debugUrlParsing($vesselId, string $fileUrl, bool $isPublic = false): array
    {
        $disk = $isPublic ? 'public' : 'local';
        $basePath = "vessels/{$vesselId}";
        $pathToDelete = null;

        // Handle relative paths starting with /file/
        if (str_starts_with($fileUrl, '/file/')) {
            $pathSegments = explode('/', trim($fileUrl, '/'));
            if (isset($pathSegments[2])) {
                $pathToDelete = implode('/', array_slice($pathSegments, 2));
            }
        } elseif (filter_var($fileUrl, FILTER_VALIDATE_URL)) {
            $pathInfo = parse_url($fileUrl);
            $pathSegments = explode('/', trim($pathInfo['path'], '/'));

            if (in_array('file', $pathSegments)) {
                $filePathIndex = array_search('file', $pathSegments);
                if ($filePathIndex !== false && isset($pathSegments[$filePathIndex + 2])) {
                    $pathToDelete = implode('/', array_slice($pathSegments, $filePathIndex + 2));
                }
            } else {
                $pathToDelete = end($pathSegments);
            }
        } else {
            $pathToDelete = ltrim($fileUrl, '/');
        }

        $fullPath = $basePath . '/' . $pathToDelete;
        $exists = Storage::disk($disk)->exists($fullPath);

        return [
            'original_url' => $fileUrl,
            'path_to_delete' => $pathToDelete,
            'full_path' => $fullPath,
            'disk' => $disk,
            'exists' => $exists,
            'vessel_id' => $vesselId
        ];
    }
}


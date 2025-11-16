<?php

namespace App\Http\Controllers;

use App\Actions\General\EasyHashAction;
use App\Actions\Tenant\TenantFileAction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class VesselFileController
{
    /**
     * Serve a public file for a vessel.
     *
     * @param Request $request
     * @param string $vesselIdHashed
     * @param string $filePath
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function showPublic(Request $request, $vesselIdHashed, $filePath)
    {
        $vesselId = EasyHashAction::decode($vesselIdHashed, 'vessel-id');

        if (!$vesselId) {
            Log::warning('VesselFileController: Invalid vessel hash', [
                'vesselIdHashed' => $vesselIdHashed,
                'filePath' => $filePath,
            ]);
            abort(404, 'Vessel not found.');
        }

        // Ensure storage directory exists before accessing
        try {
            TenantFileAction::ensureStorageDirectoryExists('public');
        } catch (\Exception $e) {
            Log::error('VesselFileController: Failed to ensure storage directory', [
                'error' => $e->getMessage(),
                'vesselId' => $vesselId,
                'filePath' => $filePath,
            ]);
        }

        $disk = 'public';
        $path = "vessels/{$vesselId}/" . ltrim($filePath, '/');
        
        // Try to get full path from Storage
        $fullPath = null;
        try {
            $fullPath = Storage::disk($disk)->path($path);
        } catch (\Exception $e) {
            Log::warning('VesselFileController: Could not get path from Storage, trying direct filesystem path', [
                'error' => $e->getMessage(),
                'vesselId' => $vesselId,
                'filePath' => $filePath,
                'path' => $path,
            ]);
            // Fallback: construct path directly
            $fullPath = storage_path('app/public/' . $path);
        }
        
        // Check if file exists (try Storage first, then filesystem)
        $fileExists = false;
        try {
            $fileExists = Storage::disk($disk)->exists($path);
        } catch (\Exception $e) {
            Log::warning('VesselFileController: Storage::exists() failed, checking filesystem directly', [
                'error' => $e->getMessage(),
                'fullPath' => $fullPath,
            ]);
            $fileExists = file_exists($fullPath);
        }
        
        if (!$fileExists || !file_exists($fullPath)) {
            Log::warning('VesselFileController: File not found', [
                'vesselId' => $vesselId,
                'filePath' => $filePath,
                'storagePath' => $path,
                'fullPath' => $fullPath,
                'disk' => $disk,
                'storageBasePath' => storage_path('app/public'),
                'fileExists' => $fileExists,
                'fileExistsOnFs' => file_exists($fullPath ?? ''),
            ]);
            abort(404, 'File not found.');
        }

        try {
            $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
            
            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        } catch (\Exception $e) {
            Log::error('VesselFileController: Error serving file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'vesselId' => $vesselId,
                'filePath' => $filePath,
                'fullPath' => $fullPath ?? 'unknown',
            ]);
            abort(500, 'Error serving file.');
        }
    }

    /**
     * Serve a private file for a vessel (requires auth and vessel access).
     *
     * @param Request $request
     * @param string $vesselIdHashed
     * @param string $filePath
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function showPrivate(Request $request, $vesselIdHashed, $filePath)
    {
        $vesselId = EasyHashAction::decode($vesselIdHashed, 'vessel-id');

        if (!$vesselId) {
            abort(404, 'Vessel not found.');
        }

        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthorized.');
        }

        // Check if user has access to this vessel
        if (!$user->hasAccessToVessel($vesselId)) {
            abort(403, 'You do not have access to this vessel.');
        }

        $disk = 'local';
        $path = "vessels/{$vesselId}/" . ltrim($filePath, '/');
        if (!Storage::disk($disk)->exists($path)) {
            abort(404, 'File not found.');
        }
        $fullPath = Storage::disk($disk)->path($path);
        $mime = mime_content_type($fullPath);
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
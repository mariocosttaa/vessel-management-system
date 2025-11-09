<?php

namespace App\Http\Controllers;

use App\Actions\General\EasyHashAction;
use Illuminate\Support\Facades\Storage;
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
            abort(404, 'Vessel not found.');
        }

        $disk = 'public';
        $path = "vessels/{$vesselId}/" . ltrim($filePath, '/');
        if (!Storage::disk($disk)->exists($path)) {
            abort(404, 'File not found.');
        }
        $fullPath = Storage::disk($disk)->path($path);
        $mime = mime_content_type($fullPath);
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
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


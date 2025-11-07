<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AttachmentController extends Controller
{
    /**
     * Display a listing of attachments for a specific model.
     */
    public function index(Request $request): Response
    {
        $attachableType = $request->query('attachable_type');
        $attachableId = $request->query('attachable_id');

        $attachments = Attachment::query()
            ->when($attachableType && $attachableId, function ($query) use ($attachableType, $attachableId) {
                return $query->where('attachable_type', $attachableType)
                            ->where('attachable_id', $attachableId);
            })
            ->with('uploadedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Attachments/Index', [
            'attachments' => $attachments,
            'attachableType' => $attachableType ?? 'general',
            'attachableId' => $attachableId ?? 0,
        ]);
    }

    /**
     * Store a newly uploaded attachment.
     */
    public function store(StoreAttachmentRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $attachableType = $request->input('attachable_type');
        $attachableId = $request->input('attachable_id');
        $description = $request->input('description');

        // Generate unique filename
        $filename = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('attachments', $filename, 'public');

        // Create attachment record
        $attachment = Attachment::create([
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'description' => $description,
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', 'File uploaded successfully.');
    }

    /**
     * Display the specified attachment.
     */
    public function show(Attachment $attachment): Response
    {
        $attachment->load('uploadedBy', 'attachable');

        return Inertia::render('Attachments/Show', [
            'attachment' => $attachment,
        ]);
    }

    /**
     * Download the specified attachment.
     */
    public function download(Attachment $attachment)
    {
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File not found.');
        }

        return response()->download(
            Storage::disk('public')->path($attachment->file_path),
            $attachment->file_name
        );
    }

    /**
     * Remove the specified attachment.
     */
    public function destroy(Attachment $attachment): RedirectResponse
    {
        // Delete file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        // Delete database record
        $attachment->delete();

        return back()->with('success', 'File deleted successfully.');
    }
}

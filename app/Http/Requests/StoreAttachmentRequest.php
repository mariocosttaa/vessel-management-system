<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * @property string $attachable_type
 * @property int $attachable_id
 * @property string|null $description
 * @property \Illuminate\Http\UploadedFile $file
 * @method array all()
 * @method mixed input(string $key = null, mixed $default = null)
 * @method void merge(array $data)
 * @method mixed route(string $key = null)
 */
class StoreAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                'mimes:pdf,jpg,jpeg,png,gif,doc,docx,xls,xlsx,txt,csv',
            ],
            'attachable_type' => ['required', 'string', 'max:255'],
            'attachable_id' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => 'The file size must not exceed 10MB.',
            'file.mimes' => 'The file must be one of the following types: PDF, JPG, JPEG, PNG, GIF, DOC, DOCX, XLS, XLSX, TXT, CSV.',
            'attachable_type.required' => 'The attachable type is required.',
            'attachable_id.required' => 'The attachable ID is required.',
            'attachable_id.integer' => 'The attachable ID must be a valid number.',
            'attachable_id.min' => 'The attachable ID must be at least 1.',
            'description.max' => 'The description may not be greater than 1000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => $this->description ? trim($this->description) : null,
        ]);
    }
}

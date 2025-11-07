<?php

namespace App\Http\Requests;

use App\Models\BankAccount;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateBankAccountRequest validates updating an existing bank account.
 *
 * Route params:
 * @property BankAccount $bankAccount
 *
 * Input fields:
 * @property string $name
 * @property string $bank_name
 * @property string $status
 * @property string|null $notes
 *
 * Magic/inherited methods (MANDATORY):
 * @method bool hasFile(string $key)
 * @method \Illuminate\Http\UploadedFile|null file(string $key)
 * @method mixed route(string $key = null)
 * @method bool boolean(string $key)
 * @method array all()
 * @method void merge(array $data)
 * @method array input(string $key = null, mixed $default = null)
 * @method bool filled(string $key)
 * @method \Illuminate\Contracts\Auth\Authenticatable|null user()
 *
 * @mixin \Illuminate\Http\Request
 */
class UpdateBankAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get vessel ID from route parameter
        $vesselId = $this->route('vessel');

        // Check if user has permission to edit bank accounts for this vessel
        // Administrator, Supervisor, and Moderator can edit bank accounts
        return $this->user()?->hasAnyRoleForVessel($vesselId, ['Administrator', 'Supervisor', 'Moderator']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }


    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The account name is required.',
            'bank_name.required' => 'The bank name is required.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name),
            'bank_name' => trim($this->bank_name),
            'status' => $this->status ?? 'active',
        ]);
    }
}

<?php

namespace App\Http\Requests;

use App\Actions\General\EasyHashAction;
use App\Actions\MoneyAction;
use App\Models\CrewMember;
use App\Models\CrewPosition;
use App\Models\User;
use App\Models\Vessel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreCrewMemberRequest validates creating a new crew member.
 *
 * Input fields:
 * @property int $position_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $date_of_birth
 * @property string $hire_date
 * @property int $salary_amount
 * @property string $compensation_type
 * @property int|null $fixed_amount
 * @property float|null $percentage
 * @property string $currency
 * @property string $payment_frequency
 * @property string $status
 * @property string|null $notes
 * @property bool $login_permitted
 * @property string|null $password
 * @property string|null $password_confirmation
 *
 * Magic/inherited methods (MANDATORY):
 * @method bool hasFile(string $key)
 * @method \Illuminate\Http\UploadedFile|null file(string $key)
 * @method mixed route(string $key = null)
 * @method bool boolean(string $key)
 * @method array all()
 * @method void merge(array $data)
 * @method array input(string $key = null, mixed $default = null)
 *
 * @mixin \Illuminate\Http\Request
 */
class StoreCrewMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get vessel ID from route parameter
        $vesselId = $this->route('vessel');
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // Check if user can manage vessel users (administrator permission)
        return $user->canManageVesselUsers($vesselId);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vesselId = $this->route('vessel');
        $skipSalary = $this->boolean('skip_salary') ?? false;

        $rules = [
            'position_id' => ['required', 'integer', Rule::exists(CrewPosition::class, 'id')],
            'login_permitted' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8'],
            'password_confirmation' => ['nullable', 'same:password'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'hire_date' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive,on_leave'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'skip_salary' => ['boolean'],
        ];

        // Note: Password validation for existing users is handled in the controller
        // If email belongs to existing user, password is not required

        // Only require salary fields if salary is not skipped
        if (!$skipSalary) {
            $rules['compensation_type'] = ['required', 'string', 'in:fixed,percentage'];
            $rules['fixed_amount'] = ['required_if:compensation_type,fixed', 'nullable', 'numeric', 'min:0'];
            $rules['percentage'] = ['required_if:compensation_type,percentage', 'nullable', 'numeric', 'min:0', 'max:100'];
            $rules['currency'] = ['required', 'string', 'size:3'];
            $rules['payment_frequency'] = ['required', 'string', 'in:weekly,bi_weekly,monthly,quarterly,annually'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'position_id.required' => 'Please select a crew position.',
            'position_id.exists' => 'The selected crew position is invalid.',
            'name.required' => 'The crew member name is required.',
            'email.email' => 'Please enter a valid email address.',
            'date_of_birth.before' => 'The date of birth must be before today.',
            'hire_date.required' => 'The hire date is required.',
            'salary_amount.required' => 'The salary amount is required.',
            'salary_amount.min' => 'The salary amount must be at least 0.',
            'payment_frequency.required' => 'Please select a payment frequency.',
            'status.required' => 'Please select a status.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        // Unhash IDs from frontend
        if ($this->filled('position_id')) {
            $data['position_id'] = EasyHashAction::decode($this->position_id, 'crewposition-id');
        }

        $this->merge(array_merge($data, [
            'salary_amount' => $this->normalizeMoney($this->salary_amount),
            'salary_currency' => strtoupper($this->salary_currency ?? 'EUR'),
            'house_of_zeros' => $this->house_of_zeros ?? 2,
            'status' => $this->status ?? 'active',
            'name' => trim($this->name),
            'email' => $this->email ? strtolower(trim($this->email)) : null,
            'phone' => $this->phone ? preg_replace('/[^\d+]/', '', $this->phone) : null,
            'hire_date' => $this->normalizeDate($this->hire_date),
            'date_of_birth' => $this->normalizeDate($this->date_of_birth),
        ]));
    }

    private function normalizeMoney($value): int
    {
        if (is_string($value)) {
            return MoneyAction::sanitize($value);
        }

        return (int) round((float) $value * 100); // Convert to cents for numeric input
    }

    private function normalizeDate($date): ?string
    {
        if (empty($date)) {
            return $date;
        }

        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return $date; // Let validation handle invalid dates
        }
    }
}

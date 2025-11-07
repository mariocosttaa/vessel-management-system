# Show Modal Pattern

## Overview

The show modal pattern provides a clean separation between table data and detailed view data. Instead of loading all relationships for the table view, we load minimal data for the table and fetch detailed data via API when the modal opens.

### Key Benefits
- **Performance**: Table loads faster with minimal data
- **User Experience**: Smooth loading with blur effect and loading states
- **Data Freshness**: Always shows latest data when modal opens
- **Separation of Concerns**: Table data vs detailed data are handled separately

---

## Backend Implementation

### 1. Controller Pattern

#### Table Data (Index Method)
```php
public function index(Request $request)
{
    // Main data query - only essential data for table display
    $query = BankAccount::query();

    // Search, filtering, sorting...
    $bankAccounts = $query->paginate(15)->withQueryString();

    // Related data for filters/forms
    $countries = Country::orderBy('name')->get();
    $currencies = Currency::active()->orderBy('name')->get();

    return Inertia::render('BankAccounts/Index', [
        'bankAccounts' => BankAccountResource::collection($bankAccounts),
        'countries' => CountryResource::collection($countries)->resolve(),
        'currencies' => $currencies->map(function ($currency) {
            return [
                'id' => $currency->id,
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'formatted_display' => $currency->formatted_display,
            ];
        }),
        'statuses' => $statuses,
        'filters' => $filters,
    ]);
}
```

#### Detailed Data (API Endpoint)
```php
/**
 * Get bank account details for modal display (API endpoint)
 */
public function details(BankAccount $bankAccount)
{
    $bankAccount->load('country');

    return response()->json([
        'bankAccount' => new BankAccountResource($bankAccount),
    ]);
}
```

### 2. Route Definition

```php
// In routes/web.php
Route::get('api/bank-accounts/{bankAccount}/details', [BankAccountController::class, 'details'])
    ->name('api.bank-accounts.details');
```

### 3. Resource Pattern

```php
class BankAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'iban' => $this->iban,
            'initial_balance' => $this->initial_balance,
            'current_balance' => $this->current_balance,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Formatted values
            'formatted_initial_balance' => $this->formatted_initial_balance,
            'formatted_current_balance' => $this->formatted_current_balance,
            
            // Conditional relationship loading
            'country' => $this->whenLoaded('country', function () {
                return new CountryResource($this->country);
            }),
        ];
    }
}
```

---

## Frontend Implementation

### 1. Vue Component Structure

```vue
<script setup lang="ts">
import { ref, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

interface BankAccount {
    id: number;
    name: string;
    bank_name: string;
    account_number: string | null;
    iban: string | null;
    country: Country | null;
    formatted_initial_balance: string;
    formatted_current_balance: string;
    status: string;
    status_label: string;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    open: boolean;
    bankAccount: BankAccount;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
}>();

// Loading state
const loading = ref(false);
const error = ref<string | null>(null);
const detailedBankAccount = ref<BankAccount | null>(null);

// Fetch bank account details from API
const fetchBankAccountDetails = async () => {
    loading.value = true;
    error.value = null;

    const url = `/api/bank-accounts/${props.bankAccount.id}/details`;

    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Failed to fetch bank account details');
        }

        const data = await response.json();
        detailedBankAccount.value = data.bankAccount;
    } catch (err) {
        error.value = 'Failed to load bank account details';
        console.error('Error fetching bank account details:', err);
    } finally {
        loading.value = false;
    }
};

// Watch for modal open to fetch details
watch(() => props.open, async (isOpen) => {
    if (isOpen && props.bankAccount) {
        await fetchBankAccountDetails();
    } else {
        // Reset state when modal closes
        detailedBankAccount.value = null;
        error.value = null;
    }
}, { immediate: true });
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Bank Account Details</DialogTitle>
            </DialogHeader>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="flex items-center space-x-2">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                    <span class="text-muted-foreground">Loading bank account details...</span>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="flex items-center justify-center py-8">
                <div class="text-center">
                    <p class="text-red-600 mb-4">{{ error }}</p>
                    <Button @click="fetchBankAccountDetails" variant="outline">
                        Try Again
                    </Button>
                </div>
            </div>

            <!-- Content -->
            <div v-else-if="detailedBankAccount" class="space-y-6">
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Account Name</label>
                            <p class="text-lg font-semibold">{{ detailedBankAccount.name }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Bank Name</label>
                            <p class="text-lg">{{ detailedBankAccount.bank_name }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Account Number</label>
                            <p class="text-lg">{{ detailedBankAccount.account_number || 'Not provided' }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">IBAN</label>
                            <p class="text-lg">{{ detailedBankAccount.iban || 'Not provided' }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Country</label>
                            <p class="text-lg">{{ detailedBankAccount.country ? `${detailedBankAccount.country.name} (${detailedBankAccount.country.code})` : 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Initial Balance</label>
                            <p class="text-lg">{{ detailedBankAccount.formatted_initial_balance }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">Current Balance</label>
                            <p class="text-lg">{{ detailedBankAccount.formatted_current_balance }}</p>
                        </div>
                    </div>
                </div>

                <!-- Status and Notes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        <Badge :variant="getStatusVariant(detailedBankAccount.status)">
                            {{ detailedBankAccount.status_label }}
                        </Badge>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Notes</label>
                        <p class="text-lg">{{ detailedBankAccount.notes || 'No notes' }}</p>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Created</label>
                        <p class="text-lg">{{ new Date(detailedBankAccount.created_at).toLocaleDateString() }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Last Updated</label>
                        <p class="text-lg">{{ new Date(detailedBankAccount.updated_at).toLocaleDateString() }}</p>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="secondary" @click="emit('close')">
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
```

### 2. Parent Component Usage

```vue
<script setup lang="ts">
import BankAccountShowModal from '@/components/modals/BankAccount/show.vue';

const selectedBankAccount = ref<BankAccount | null>(null);
const showShowModal = ref(false);

const openShowModal = (bankAccount: BankAccount) => {
    selectedBankAccount.value = bankAccount;
    showShowModal.value = true;
};

const closeModals = () => {
    showShowModal.value = false;
    selectedBankAccount.value = null;
};
</script>

<template>
    <!-- Table with clickable rows -->
    <DataTable
        :data="bankAccounts.data"
        :columns="columns"
        clickable
        @row-click="openShowModal"
    />

    <!-- Show Modal -->
    <BankAccountShowModal
        v-if="selectedBankAccount"
        :open="showShowModal"
        :bank-account="selectedBankAccount"
        @close="closeModals"
    />
</template>
```

---

## Key Features

### 1. Loading States
- **Spinner**: Shows animated loading indicator
- **Blur Effect**: Optional blur effect during loading
- **Loading Message**: Clear indication of what's happening

### 2. Error Handling
- **Error State**: Shows error message if API fails
- **Retry Button**: Allows user to retry failed requests
- **Graceful Degradation**: Falls back to error state instead of crashing

### 3. Data Management
- **State Reset**: Clears data when modal closes
- **Fresh Data**: Always fetches latest data when opening
- **Conditional Loading**: Only loads relationships when needed

### 4. Performance Benefits
- **Lazy Loading**: Relationships only loaded when modal opens
- **Minimal Table Data**: Table loads faster with essential data only
- **Efficient API**: Dedicated endpoint for detailed data

---

## Best Practices

### ✅ DO

```php
// Separate table and detail data
public function index() {
    // Minimal data for table
    $bankAccounts = BankAccount::query()->paginate(15);
    return Inertia::render('Index', ['bankAccounts' => $bankAccounts]);
}

public function details(BankAccount $bankAccount) {
    // Load relationships for detailed view
    $bankAccount->load('country');
    return response()->json(['bankAccount' => new BankAccountResource($bankAccount)]);
}
```

```vue
// Use loading states
const loading = ref(false);
const error = ref<string | null>(null);

// Reset state when modal closes
watch(() => props.open, (isOpen) => {
    if (!isOpen) {
        detailedData.value = null;
        error.value = null;
    }
});
```

### ❌ DON'T

```php
// Don't load all relationships for table
public function index() {
    $bankAccounts = BankAccount::with(['country', 'transactions', 'monthlyBalances'])->paginate(15);
    // This loads unnecessary data for table view
}
```

```vue
// Don't forget error handling
const fetchDetails = async () => {
    const response = await fetch(url);
    // Missing error handling!
    const data = await response.json();
};
```

---

## Testing

### Backend Tests

```php
test('details endpoint returns bank account with country', function () {
    $bankAccount = BankAccount::factory()->create();
    $country = Country::factory()->create();
    $bankAccount->update(['country_id' => $country->id]);

    $response = $this->getJson("/api/bank-accounts/{$bankAccount->id}/details");

    $response->assertOk()
        ->assertJsonStructure([
            'bankAccount' => [
                'id',
                'name',
                'country' => [
                    'id',
                    'name',
                    'code'
                ]
            ]
        ]);
});
```

### Frontend Tests

```javascript
test('show modal fetches details on open', async () => {
    const mockBankAccount = { id: 1, name: 'Test Account' };
    
    render(BankAccountShowModal, {
        props: { open: true, bankAccount: mockBankAccount }
    });

    // Should show loading state initially
    expect(screen.getByText('Loading bank account details...')).toBeInTheDocument();

    // Wait for API call to complete
    await waitFor(() => {
        expect(screen.getByText('Test Account')).toBeInTheDocument();
    });
});
```

---

## Summary

The show modal pattern provides:

- **Performance**: Faster table loading with minimal data
- **User Experience**: Smooth loading states and error handling
- **Data Freshness**: Always shows latest data when modal opens
- **Maintainability**: Clear separation between table and detail data
- **Scalability**: Easy to add more relationships without affecting table performance

**Key Principle**: Load minimal data for tables, fetch detailed data on demand via dedicated API endpoints with proper loading states and error handling.

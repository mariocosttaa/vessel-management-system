<template>
  <div class="space-y-6 p-6">
    <div class="space-y-4">
      <h2 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">
        Money Input Components Examples
      </h2>

      <!-- Basic MoneyInput -->
      <div class="space-y-2">
        <h3 class="text-lg font-medium text-card-foreground dark:text-card-foreground">
          Basic MoneyInput
        </h3>
        <MoneyInput
          v-model="basicAmount"
          currency="EUR"
          placeholder="Enter amount"
          @value-change="handleValueChange"
        />
        <p class="text-sm text-muted-foreground dark:text-muted-foreground">
          Value: {{ basicAmount }} cents
        </p>
      </div>

      <!-- MoneyInputWithLabel -->
      <div class="space-y-2">
        <h3 class="text-lg font-medium text-card-foreground dark:text-card-foreground">
          MoneyInputWithLabel
        </h3>
        <MoneyInputWithLabel
          v-model="labeledAmount"
          label="Transaction Amount"
          currency="USD"
          placeholder="0.00"
          helper-text="Enter the transaction amount"
          required
          @value-change="handleLabeledValueChange"
        />
        <p class="text-sm text-muted-foreground dark:text-muted-foreground">
          Value: {{ labeledAmount }} cents
        </p>
      </div>

      <!-- MoneyDisplay Examples -->
      <div class="space-y-2">
        <h3 class="text-lg font-medium text-card-foreground dark:text-card-foreground">
          MoneyDisplay Examples
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <p class="text-sm font-medium">Positive Amount:</p>
            <MoneyDisplay
              :value="12345"
              currency="EUR"
              variant="positive"
            />
          </div>
          <div class="space-y-2">
            <p class="text-sm font-medium">Negative Amount:</p>
            <MoneyDisplay
              :value="-5678"
              currency="USD"
              variant="negative"
            />
          </div>
          <div class="space-y-2">
            <p class="text-sm font-medium">Without Symbol:</p>
            <MoneyDisplay
              :value="98765"
              currency="EUR"
              :show-symbol="false"
            />
          </div>
          <div class="space-y-2">
            <p class="text-sm font-medium">Large Amount:</p>
            <MoneyDisplay
              :value="1234567"
              currency="EUR"
              size="lg"
            />
          </div>
        </div>
      </div>

      <!-- Form Example -->
      <div class="space-y-4">
        <h3 class="text-lg font-medium text-card-foreground dark:text-card-foreground">
          Form Example
        </h3>
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <MoneyInputWithLabel
              v-model="form.amount"
              label="Amount"
              currency="EUR"
              placeholder="0,00"
              :error="errors.amount"
              required
            />
            <MoneyInputWithLabel
              v-model="form.vatAmount"
              label="VAT Amount"
              currency="EUR"
              placeholder="0,00"
              :error="errors.vatAmount"
            />
          </div>

          <div class="bg-muted/50 dark:bg-muted/50 p-4 rounded-lg">
            <div class="flex justify-between items-center">
              <span class="font-medium">Total Amount:</span>
              <MoneyDisplay
                :value="totalAmount"
                currency="EUR"
                size="lg"
                variant="positive"
              />
            </div>
          </div>

          <button
            type="submit"
            class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
          >
            Submit Form
          </button>
        </form>
      </div>

      <!-- Currency Examples -->
      <div class="space-y-2">
        <h3 class="text-lg font-medium text-card-foreground dark:text-card-foreground">
          Different Currencies
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <MoneyInputWithLabel
            v-model="currencyExamples.eur"
            label="EUR"
            currency="EUR"
            placeholder="0,00"
          />
          <MoneyInputWithLabel
            v-model="currencyExamples.usd"
            label="USD"
            currency="USD"
            placeholder="0.00"
          />
          <MoneyInputWithLabel
            v-model="currencyExamples.jpy"
            label="JPY"
            currency="JPY"
            :decimals="0"
            placeholder="0"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import MoneyInput from '@/components/Forms/MoneyInput.vue'
import MoneyInputWithLabel from '@/components/Forms/MoneyInputWithLabel.vue'
import MoneyDisplay from '@/components/Common/MoneyDisplay.vue'
import { useMoney } from '@/composables/useMoney'

// Reactive data
const basicAmount = ref<number | null>(null)
const labeledAmount = ref<number | null>(null)

const form = ref({
  amount: null as number | null,
  vatAmount: null as number | null
})

const errors = ref({
  amount: '',
  vatAmount: ''
})

const currencyExamples = ref({
  eur: null as number | null,
  usd: null as number | null,
  jpy: null as number | null
})

// Composables
const { calculateTotal } = useMoney()

// Computed
const totalAmount = computed(() => {
  const amount = form.value.amount || 0
  const vatAmount = form.value.vatAmount || 0
  return calculateTotal(amount, vatAmount)
})

// Methods
const handleValueChange = (rawValue: number | null, formattedValue: string, formValue: number | string | null, centsValue: number | null) => {
  console.log('Value changed:', { rawValue, formattedValue, formValue, centsValue })
}

const handleLabeledValueChange = (rawValue: number | null, formattedValue: string, formValue: number | string | null, centsValue: number | null) => {
  console.log('Labeled value changed:', { rawValue, formattedValue, formValue, centsValue })
}

const handleSubmit = () => {
  console.log('Form submitted:', {
    amount: form.value.amount,
    vatAmount: form.value.vatAmount,
    totalAmount: totalAmount.value
  })

  // Reset form
  form.value = {
    amount: null,
    vatAmount: null
  }
}
</script>

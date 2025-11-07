import { computed, ref } from 'vue'
import {
  formatCurrency,
  formatCurrencyWithoutSymbol,
  extractNumericValue,
  sanitizeMoneyInput,
  convertToFormValue,
  toInteger,
  toFloat,
  calculateVat,
  calculateTotal,
  type ReturnType,
  type CurrencyFormatterConfig,
  type UseCurrencyFormatterReturn
} from '@/helpers/moneyFormat'

/**
 * Money handling composable for Vue components
 *
 * This composable provides a centralized way to handle monetary values
 * in the vessel management system, following the established patterns
 * where all monetary values are stored as integers (cents).
 */
export function useMoney() {
  /**
   * Convert float value to integer (cents)
   */
  const toInteger = (value: number, decimals: number = 2): number => {
    return Math.round(value * Math.pow(10, decimals))
  }

  /**
   * Convert integer (cents) to float value
   */
  const toFloat = (value: number, decimals: number = 2): number => {
    return value / Math.pow(10, decimals)
  }

  /**
   * Format integer value as currency string
   */
  const format = (value: number, currency: string = 'EUR', decimals: number = 2): string => {
    return formatCurrency(value, currency, decimals)
  }

  /**
   * Format integer value as currency string without symbol
   */
  const formatWithoutSymbol = (value: number, decimals: number = 2): string => {
    return formatCurrencyWithoutSymbol(value, decimals)
  }

  /**
   * Sanitize user input string to integer (cents)
   */
  const sanitize = (value: string, decimals: number = 2): number => {
    return sanitizeMoneyInput(value, decimals)
  }

  /**
   * Calculate VAT amount
   */
  const calculateVatAmount = (amount: number, vatRate: number, decimals: number = 2): number => {
    return calculateVat(amount, vatRate, decimals)
  }

  /**
   * Calculate total amount including VAT
   */
  const calculateTotalAmount = (amount: number, vatAmount: number): number => {
    return calculateTotal(amount, vatAmount)
  }

  return {
    toInteger,
    toFloat,
    format,
    formatWithoutSymbol,
    sanitize,
    calculateVat: calculateVatAmount,
    calculateTotal: calculateTotalAmount
  }
}

/**
 * Currency formatter hook with React-like state management
 *
 * This hook provides a more React-like interface for handling currency
 * formatting with automatic state management.
 */
export function useCurrencyFormatter(config: CurrencyFormatterConfig = {}): UseCurrencyFormatterReturn {
  const {
    currency = 'EUR',
    decimals = 2,
    locale = 'pt-PT',
    initialValue = null,
    returnType = 'int'
  } = config

  const rawValue = ref<number | null>(initialValue)

  const formatValue = (value: number | null): string => {
    if (value === null) return ''
    return formatCurrencyWithoutSymbol(value, decimals, locale)
  }

  const handleChange = (event: Event): void => {
    const target = event.target as HTMLInputElement
    const inputValue = target.value
    const numericValue = extractNumericValue(inputValue, decimals)
    rawValue.value = numericValue
  }

  const setValue = (value: number | null): void => {
    rawValue.value = value
  }

  const formattedValue = computed(() => formatValue(rawValue.value))
  const formValue = computed(() => convertToFormValue(rawValue.value, returnType, decimals))

  return {
    value: formattedValue.value,
    rawValue: rawValue.value,
    formValue: formValue.value,
    onChange: handleChange,
    setValue
  }
}

/**
 * Money formatter utility class (alternative OOP approach)
 */
export class MoneyFormatter {
  private currency: string
  private decimals: number
  private locale: string
  private returnType: ReturnType

  constructor(
    currency: string = 'EUR',
    decimals: number = 2,
    locale: string = 'pt-PT',
    returnType: ReturnType = 'int'
  ) {
    this.currency = currency
    this.decimals = decimals
    this.locale = locale
    this.returnType = returnType
  }

  /**
   * Format a number as currency
   */
  format(value: number | null): string {
    return formatCurrency(value, this.currency, this.decimals, this.locale)
  }

  /**
   * Format a number as currency without symbol
   */
  formatWithoutSymbol(value: number | null): string {
    return formatCurrencyWithoutSymbol(value, this.decimals, this.locale)
  }

  /**
   * Parse currency string to number
   */
  parse(input: string): number | null {
    return extractNumericValue(input, this.decimals)
  }

  /**
   * Get value ready for form submission
   */
  getFormValue(value: number | null): number | string | null {
    return convertToFormValue(value, this.returnType, this.decimals)
  }

  /**
   * Update formatter configuration
   */
  configure(config: Partial<CurrencyFormatterConfig>): void {
    if (config.currency) this.currency = config.currency
    if (config.decimals !== undefined) this.decimals = config.decimals
    if (config.locale) this.locale = config.locale
    if (config.returnType) this.returnType = config.returnType
  }

  /**
   * Get current configuration
   */
  getConfig(): Required<Omit<CurrencyFormatterConfig, 'initialValue'>> {
    return {
      currency: this.currency,
      decimals: this.decimals,
      locale: this.locale,
      returnType: this.returnType
    }
  }
}

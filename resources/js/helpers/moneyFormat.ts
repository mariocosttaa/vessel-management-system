/**
 * Money Formatting Helper Functions
 *
 * This module provides utilities for formatting, parsing, and handling monetary values
 * in the vessel management system. All monetary values are stored as integers
 * representing the smallest currency unit (cents).
 */

// Type definitions
export type ReturnType = 'float' | 'int' | 'string';

export interface CurrencyFormatterConfig {
  currency?: string;
  decimals?: number;
  locale?: string;
  initialValue?: number | null;
  returnType?: ReturnType;
}

export interface UseCurrencyFormatterReturn {
  value: string;
  rawValue: number | null;
  formValue: number | string | null; // Value ready for form submission
  onChange: (event: Event) => void;
  setValue: (value: number | null) => void;
}

/**
 * Formats a numeric value as currency
 * @param value - Numeric value to be formatted (in cents)
 * @param currency - Currency code (e.g., USD, EUR, BRL)
 * @param decimals - Number of decimal places
 * @param locale - Locale for formatting (e.g., 'pt-PT', 'en-US')
 * @returns Formatted currency string
 */
export const formatCurrency = (
  value: number | null,
  currency: string = 'EUR',
  decimals: number = 2,
  locale: string = 'pt-PT'
): string => {
  if (value === null || value === undefined) return '';

  // Convert from cents to actual value
  const floatValue = value / Math.pow(10, decimals);

  const options: Intl.NumberFormatOptions = {
    style: 'currency',
    currency,
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  };

  return new Intl.NumberFormat(locale, options).format(floatValue);
};

/**
 * Formats currency without symbol (for input display)
 * @param value - Numeric value in cents
 * @param decimals - Number of decimal places
 * @param locale - Locale for formatting
 * @returns Formatted number string without currency symbol
 */
export const formatCurrencyWithoutSymbol = (
  value: number | null,
  decimals: number = 2,
  locale: string = 'pt-PT'
): string => {
  if (value === null || value === undefined) return '';

  // Convert from cents to actual value
  const floatValue = value / Math.pow(10, decimals);

  return new Intl.NumberFormat(locale, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  }).format(floatValue);
};

/**
 * Extracts numeric value from formatted currency string
 * @param input - Input string (can contain currency symbols and formatting)
 * @param decimals - Number of decimal places to consider
 * @returns Numeric value in cents or null if empty
 */
export const extractNumericValue = (input: string, decimals: number = 2): number | null => {
  // If input is empty or only whitespace, return null
  if (!input || input.trim() === '') return null;

  // Extract only numbers from input
  const numbersOnly = input.replace(/\D/g, '');

  // If no numbers found, return null
  if (!numbersOnly) return null;

  // Convert to actual decimal value
  const floatValue = parseFloat(numbersOnly) / Math.pow(10, decimals);

  // Convert to cents
  return Math.round(floatValue * Math.pow(10, decimals));
};

/**
 * Validates if a currency code is supported
 * @param currency - Currency code to validate
 * @returns Boolean indicating if currency is valid
 */
export const isValidCurrency = (currency: string): boolean => {
  try {
    new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency
    });
    return true;
  } catch {
    return false;
  }
};

/**
 * Converts a numeric value to the specified return type for form submission
 * @param value - Numeric value in cents to convert
 * @param returnType - Type to return ('float', 'int', 'string')
 * @param decimals - Number of decimal places (for string formatting)
 * @returns Converted value in the specified type
 */
export const convertToFormValue = (
  value: number | null,
  returnType: ReturnType = 'float',
  decimals: number = 2
): number | string | null => {
  if (value === null) return null;

  switch (returnType) {
    case 'int':
      return value; // Already in cents
    case 'string':
      return (value / Math.pow(10, decimals)).toFixed(decimals);
    case 'float':
    default:
      return value / Math.pow(10, decimals);
  }
};

/**
 * Converts float value to integer (cents)
 * @param value - Float value to convert
 * @param decimals - Number of decimal places
 * @returns Integer value in cents
 */
export const toInteger = (value: number, decimals: number = 2): number => {
  return Math.round(value * Math.pow(10, decimals));
};

/**
 * Converts integer (cents) to float value
 * @param value - Integer value in cents
 * @param decimals - Number of decimal places
 * @returns Float value
 */
export const toFloat = (value: number, decimals: number = 2): number => {
  return value / Math.pow(10, decimals);
};

/**
 * Sanitizes user input string to integer (cents)
 * @param input - User input string
 * @param decimals - Number of decimal places
 * @returns Integer value in cents
 */
export const sanitizeMoneyInput = (input: string, decimals: number = 2): number => {
  if (!input || input.trim() === '') return 0;

  // Remove all non-numeric characters except decimal point and comma
  const cleanValue = input.replace(/[^\d.,]/g, '');

  // Replace comma with dot for consistent decimal separator
  const normalizedValue = cleanValue.replace(',', '.');

  // Parse as float and convert to cents
  const floatValue = parseFloat(normalizedValue) || 0;
  return toInteger(floatValue, decimals);
};

/**
 * Calculates VAT amount
 * @param amount - Base amount in cents
 * @param vatRate - VAT rate as percentage (e.g., 23 for 23%)
 * @param decimals - Number of decimal places
 * @returns VAT amount in cents
 */
export const calculateVat = (amount: number, vatRate: number, decimals: number = 2): number => {
  return Math.round((amount * vatRate) / 100);
};

/**
 * Calculates total amount including VAT
 * @param amount - Base amount in cents
 * @param vatAmount - VAT amount in cents
 * @returns Total amount in cents
 */
export const calculateTotal = (amount: number, vatAmount: number): number => {
  return amount + vatAmount;
};

// Common currency configurations
export const CURRENCY_CONFIGS = {
  USD: { currency: 'USD', locale: 'en-US', decimals: 2 },
  EUR: { currency: 'EUR', locale: 'pt-PT', decimals: 2 },
  BRL: { currency: 'BRL', locale: 'pt-BR', decimals: 2 },
  JPY: { currency: 'JPY', locale: 'ja-JP', decimals: 0 },
  GBP: { currency: 'GBP', locale: 'en-GB', decimals: 2 },
  CAD: { currency: 'CAD', locale: 'en-CA', decimals: 2 },
  AOA: { currency: 'AOA', locale: 'pt-AO', decimals: 2 },
  AUD: { currency: 'AUD', locale: 'en-AU', decimals: 2 },
  CHF: { currency: 'CHF', locale: 'de-CH', decimals: 2 },
  CNY: { currency: 'CNY', locale: 'zh-CN', decimals: 2 },
  INR: { currency: 'INR', locale: 'hi-IN', decimals: 2 },
  MXN: { currency: 'MXN', locale: 'es-MX', decimals: 2 },
  ZAR: { currency: 'ZAR', locale: 'en-ZA', decimals: 2 },
  KRW: { currency: 'KRW', locale: 'ko-KR', decimals: 0 },
  SGD: { currency: 'SGD', locale: 'en-SG', decimals: 2 },
  NZD: { currency: 'NZD', locale: 'en-NZ', decimals: 2 },
  RUB: { currency: 'RUB', locale: 'ru-RU', decimals: 2 },
  SEK: { currency: 'SEK', locale: 'sv-SE', decimals: 2 },
  NOK: { currency: 'NOK', locale: 'nb-NO', decimals: 2 },
  DKK: { currency: 'DKK', locale: 'da-DK', decimals: 2 },
  TRY: { currency: 'TRY', locale: 'tr-TR', decimals: 2 },
  AED: { currency: 'AED', locale: 'ar-AE', decimals: 2 },
  SAR: { currency: 'SAR', locale: 'ar-SA', decimals: 2 },
  HKD: { currency: 'HKD', locale: 'zh-HK', decimals: 2 },
  ARS: { currency: 'ARS', locale: 'es-AR', decimals: 2 },
  CLP: { currency: 'CLP', locale: 'es-CL', decimals: 0 },
  COP: { currency: 'COP', locale: 'es-CO', decimals: 2 },
  EGP: { currency: 'EGP', locale: 'ar-EG', decimals: 2 },
  IDR: { currency: 'IDR', locale: 'id-ID', decimals: 2 },
  MYR: { currency: 'MYR', locale: 'ms-MY', decimals: 2 },
  PHP: { currency: 'PHP', locale: 'fil-PH', decimals: 2 },
  PLN: { currency: 'PLN', locale: 'pl-PL', decimals: 2 },
  THB: { currency: 'THB', locale: 'th-TH', decimals: 2 },
  VND: { currency: 'VND', locale: 'vi-VN', decimals: 0 },
  BGN: { currency: 'BGN', locale: 'bg-BG', decimals: 2 },
  CZK: { currency: 'CZK', locale: 'cs-CZ', decimals: 2 },
  HUF: { currency: 'HUF', locale: 'hu-HU', decimals: 2 },
  RON: { currency: 'RON', locale: 'ro-RO', decimals: 2 },
  ISK: { currency: 'ISK', locale: 'is-IS', decimals: 0 },
  UAH: { currency: 'UAH', locale: 'uk-UA', decimals: 2 },
  KES: { currency: 'KES', locale: 'sw-KE', decimals: 2 },
  NGN: { currency: 'NGN', locale: 'en-NG', decimals: 2 },
  PKR: { currency: 'PKR', locale: 'ur-PK', decimals: 2 },
  BDT: { currency: 'BDT', locale: 'bn-BD', decimals: 2 }
} as const;

export type CurrencyType = keyof typeof CURRENCY_CONFIGS;

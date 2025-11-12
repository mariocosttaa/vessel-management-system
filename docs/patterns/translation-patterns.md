# Translation Patterns

> **Important**: This document defines the translation system patterns for the Vessel Management System. Always follow these patterns when adding or using translations.

## 📋 Table of Contents

- [Overview](#overview)
- [Key Principles](#key-principles)
- [Translation File Structure](#translation-file-structure)
- [Using Translations in Components](#using-translations-in-components)
- [Key Verification](#key-verification)
- [Best Practices](#best-practices)
- [Adding New Translations](#adding-new-translations)
- [Common Patterns](#common-patterns)
- [Troubleshooting](#troubleshooting)

## Overview

The translation system uses **English text as keys** for all translations. This approach provides several benefits:

- ✅ **Self-documenting**: The English text serves as both the key and the default value
- ✅ **Type-safe**: Easy to see what text is being translated
- ✅ **No key management**: No need to maintain separate key constants
- ✅ **Fallback friendly**: If translation is missing, English text is shown

## Key Principles

### 1. English Text as Key

**Always use the English text as the translation key:**

```json
// ✅ CORRECT - English text as key
{
  "Dashboard": "Dashboard",
  "Create New Vessel": "Create New Vessel",
  "Save Changes": "Save Changes"
}
```

```json
// ❌ WRONG - Don't use abstract keys
{
  "nav.dashboard": "Dashboard",
  "vessel.create": "Create New Vessel",
  "actions.save": "Save Changes"
}
```

### 2. Consistent Capitalization

- **Navigation items**: Title Case (`"Dashboard"`, `"Crew Members"`)
- **Actions**: Title Case (`"Save"`, `"Cancel"`, `"Delete"`)
- **Labels**: Title Case (`"Income"`, `"Expenses"`, `"Net Balance"`)
- **Status**: Title Case (`"At Sea"`, `"In Port"`, `"Preparing"`)

### 3. Complete Phrases

Use complete phrases, not fragments:

```json
// ✅ CORRECT
{
  "View All": "View All",
  "No description": "No description",
  "Preparing Mareas": "Preparing Mareas"
}

// ❌ WRONG - Don't split phrases
{
  "View": "View",
  "All": "All",
  "No": "No",
  "description": "description"
}
```

## Translation File Structure

### File Locations

All translation files are located in `resources/js/i18n/locales/`:

- `en.json` - English (default/base)
- `pt.json` - Portuguese
- `es.json` - Spanish
- `fr.json` - French

### File Organization

Use comments to organize translations by section:

```json
{
  "_comment": "English translations - keys are the English text themselves",
  
  "_comment_navigation": "Navigation items",
  "Dashboard": "Dashboard",
  "Mareas": "Mareas",
  "Transactions": "Transactions",
  
  "_comment_dashboard": "Dashboard page",
  "At Sea": "At Sea",
  "In Port": "In Port",
  "Income": "Income",
  "Expenses": "Expenses"
}
```

**Note**: Comments use the `_comment` prefix and are ignored by the translation system.

## Using Translations in Components

### Basic Usage

Import and use the `useI18n` composable:

```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
</script>

<template>
  <div>
    <h1>{{ t('Dashboard') }}</h1>
    <button>{{ t('Save Changes') }}</button>
  </div>
</template>
```

### In Template

```vue
<template>
  <!-- Direct usage -->
  <h1>{{ t('Dashboard') }}</h1>
  
  <!-- With dynamic content -->
  <p>{{ t('Preparing Mareas') }} ({{ count }})</p>
  
  <!-- In attributes -->
  <button :title="t('Save Changes')">
    {{ t('Save') }}
  </button>
  
  <!-- Conditional -->
  <span>{{ vesselAtSea ? t('At Sea') : t('In Port') }}</span>
</template>
```

### In Script

```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

// In computed properties
const pageTitle = computed(() => t('Dashboard'));

// In functions
const showMessage = () => {
  alert(t('Changes saved successfully'));
};

// In reactive data
const statusLabel = ref(t('Active'));
</script>
```

## Key Verification

### Smart Translation Helper

The system automatically handles missing keys by falling back to the key itself (English text). However, you should verify keys exist during development.

### Development Verification

Create a helper function to check for missing keys in development:

```typescript
// In composables/useI18n.ts (add this for development)
export function useI18n() {
    const { t, locale } = useVueI18n();
    
    // Development helper to verify keys
    const tSafe = (key: string): string => {
        if (import.meta.env.DEV) {
            const messages = i18n.global.messages.value[locale.value];
            if (!messages || !messages[key]) {
                console.warn(`[i18n] Missing translation key: "${key}" for locale: ${locale.value}`);
            }
        }
        return t(key);
    };
    
    return {
        t: import.meta.env.DEV ? tSafe : t,
        // ... rest of the exports
    };
}
```

### Manual Verification

Before committing, verify all keys exist in all language files:

1. Check `en.json` has the key
2. Check `pt.json` has the translation
3. Check `es.json` has the translation
4. Check `fr.json` has the translation

### Automated Verification (Recommended)

Add a script to verify translations:

```json
// package.json
{
  "scripts": {
    "i18n:check": "node scripts/check-translations.js"
  }
}
```

## Best Practices

### 1. Always Use English as Key

```vue
<!-- ✅ CORRECT -->
<h1>{{ t('Dashboard') }}</h1>
<button>{{ t('Save Changes') }}</button>

<!-- ❌ WRONG -->
<h1>{{ t('nav.dashboard') }}</h1>
<button>{{ t('actions.save') }}</button>
```

### 2. Keep Keys Consistent

Use the same key across the application:

```vue
<!-- ✅ CORRECT - Same key everywhere -->
<button>{{ t('Save') }}</button>
<button>{{ t('Save') }}</button>
<button>{{ t('Save') }}</button>

<!-- ❌ WRONG - Different keys for same text -->
<button>{{ t('Save') }}</button>
<button>{{ t('Save Button') }}</button>
<button>{{ t('Save Changes') }}</button>
```

### 3. Use Complete Phrases

```vue
<!-- ✅ CORRECT -->
<p>{{ t('No description') }}</p>
<p>{{ t('View All') }}</p>

<!-- ❌ WRONG - Don't concatenate -->
<p>{{ t('No') }} {{ t('description') }}</p>
<p>{{ t('View') }} {{ t('All') }}</p>
```

### 4. Handle Dynamic Content

```vue
<!-- ✅ CORRECT - Keep static parts as keys -->
<p>{{ t('Preparing Mareas') }} ({{ count }})</p>
<p>{{ t('Departure') }}: {{ date }}</p>

<!-- ❌ WRONG - Don't put dynamic content in keys -->
<p>{{ t(`Preparing Mareas (${count})`) }}</p>
```

### 5. Pluralization

For pluralization, use separate keys:

```json
{
  "Transaction": "Transaction",
  "Transactions": "Transactions",
  "1 Transaction": "1 Transaction",
  "No Transactions": "No Transactions"
}
```

```vue
<template>
  <p v-if="count === 0">{{ t('No Transactions') }}</p>
  <p v-else-if="count === 1">{{ t('1 Transaction') }}</p>
  <p v-else>{{ count }} {{ t('Transactions') }}</p>
</template>
```

### 6. Context-Specific Translations

If the same English word needs different translations in different contexts, use descriptive keys:

```json
{
  "Back": "Back",
  "Back to Vessels": "Back to Vessels",
  "Go Back": "Go Back"
}
```

## Adding New Translations

### Step-by-Step Process

1. **Add to English file first** (`en.json`):
   ```json
   {
     "New Feature": "New Feature"
   }
   ```

2. **Add to all other language files**:
   ```json
   // pt.json
   {
     "New Feature": "Nova Funcionalidade"
   }
   
   // es.json
   {
     "New Feature": "Nueva Funcionalidad"
   }
   
   // fr.json
   {
     "New Feature": "Nouvelle Fonctionnalité"
   }
   ```

3. **Use in component**:
   ```vue
   <script setup lang="ts">
   import { useI18n } from '@/composables/useI18n';
   const { t } = useI18n();
   </script>
   
   <template>
     <h1>{{ t('New Feature') }}</h1>
   </template>
   ```

### Translation File Template

When adding new sections, use this template:

```json
{
  "_comment": "English translations - keys are the English text themselves",
  
  "_comment_section_name": "Description of this section",
  "Key 1": "Key 1",
  "Key 2": "Key 2",
  
  "_comment_another_section": "Another section",
  "Key 3": "Key 3"
}
```

## Common Patterns

### Navigation Items

```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const navItems = computed(() => [
  { title: t('Dashboard'), href: '/dashboard' },
  { title: t('Mareas'), href: '/mareas' },
  { title: t('Transactions'), href: '/transactions' },
]);
</script>
```

### Form Labels

```vue
<template>
  <form>
    <label>{{ t('Vessel Name') }}</label>
    <input type="text" />
    
    <label>{{ t('Registration Number') }}</label>
    <input type="text" />
    
    <button type="submit">{{ t('Save') }}</button>
    <button type="button">{{ t('Cancel') }}</button>
  </form>
</template>
```

### Status Badges

```vue
<template>
  <span :class="statusClass">
    {{ status === 'active' ? t('Active') : t('Inactive') }}
  </span>
</template>
```

### Error Messages

```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const showError = (message: string) => {
  // Use translation if available, otherwise use the message
  alert(t(message) || message);
};
</script>
```

### Breadcrumbs

```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const breadcrumbs = computed(() => [
  { title: t('Dashboard'), href: '/dashboard' },
  { title: t('Vessels'), href: '/vessels' },
  { title: vesselName.value },
]);
</script>
```

## Troubleshooting

### Missing Translation

**Problem**: Text shows as the key (English) in other languages.

**Solution**: 
1. Check if key exists in the target language file
2. Add the translation to the language file
3. Verify the key matches exactly (case-sensitive)

### Key Not Found

**Problem**: Console warning about missing key.

**Solution**:
1. Verify the key exists in `en.json`
2. Check for typos (case-sensitive)
3. Ensure the key is used correctly: `t('Key Name')`

### Translation Not Updating

**Problem**: Translation doesn't change when switching languages.

**Solution**:
1. Clear browser cache and localStorage
2. Verify the language file has the correct translation
3. Check that `setLocale()` is being called correctly
4. Ensure the page reloads after language change

### Inconsistent Translations

**Problem**: Same English text translated differently in different places.

**Solution**:
1. Use the same key consistently
2. If context matters, use descriptive keys:
   - `"Back"` for navigation
   - `"Back to Vessels"` for specific action
   - `"Go Back"` for form actions

## Quick Reference

### Import Pattern

```typescript
import { useI18n } from '@/composables/useI18n';
const { t } = useI18n();
```

### Usage Pattern

```vue
<!-- Template -->
{{ t('English Text as Key') }}

<!-- Script -->
const label = t('English Text as Key');
```

### Key Naming

- ✅ Use Title Case: `"Dashboard"`, `"Save Changes"`
- ✅ Use complete phrases: `"View All"`, `"No description"`
- ✅ Be descriptive: `"Back to Vessels"` not just `"Back"`
- ❌ Don't use abbreviations: `"Dashboard"` not `"Dash"`
- ❌ Don't use fragments: `"View All"` not `"View"` + `"All"`

## Examples

### Complete Component Example

```vue
<template>
  <VesselLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <!-- Header -->
      <div class="mb-4">
        <h1 class="text-2xl font-semibold">
          {{ t('Dashboard') }}
        </h1>
        <p class="text-muted-foreground">
          {{ t('Welcome to your vessel dashboard') }}
        </p>
      </div>
      
      <!-- Status Badge -->
      <div class="mb-4">
        <span :class="statusClass">
          {{ vesselAtSea ? t('At Sea') : t('In Port') }}
        </span>
      </div>
      
      <!-- Actions -->
      <div class="flex gap-2">
        <button @click="viewReports">
          {{ t('View Reports') }}
        </button>
        <button @click="cancel">
          {{ t('Cancel') }}
        </button>
      </div>
      
      <!-- Dynamic Content -->
      <div v-if="transactions.length === 0">
        <p>{{ t('No Transactions') }}</p>
      </div>
      <div v-else>
        <p>{{ transactions.length }} {{ t('Transactions') }}</p>
      </div>
    </div>
  </VesselLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import VesselLayout from '@/layouts/VesselLayout.vue';

const { t } = useI18n();

const breadcrumbs = computed(() => [
  { title: t('Dashboard'), href: '/dashboard' },
]);

const vesselAtSea = ref(false);
const transactions = ref([]);

const statusClass = computed(() => 
  vesselAtSea.value 
    ? 'bg-blue-100 text-blue-700' 
    : 'bg-green-100 text-green-700'
);
</script>
```

## Summary

- ✅ **Always use English text as the translation key**
- ✅ **Verify keys exist in all language files**
- ✅ **Use complete phrases, not fragments**
- ✅ **Keep keys consistent across the application**
- ✅ **Use Title Case for all keys**
- ✅ **Add comments to organize translation files**
- ✅ **Test translations in all supported languages**

Following these patterns ensures a maintainable, consistent, and user-friendly multilingual application.


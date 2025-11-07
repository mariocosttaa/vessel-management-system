# Theme Configuration Guide

This document explains how to manage themes and colors in the Vessel Management System using Tailwind CSS v4 with proper design system colors that work seamlessly in both light and dark modes.

## 🎨 Theme Management

### Current Setup
The project uses **Tailwind CSS v4** with the Vite plugin. Theme configuration is done directly in `resources/css/app.css` using the `@theme` directive with CSS custom properties that automatically adapt to light and dark modes.

### Design System Colors
All theme colors are defined using CSS custom properties that automatically switch between light and dark modes:

```css
@theme inline {
    /* Design System Colors - Auto-adapting */
    --color-background: var(--background);
    --color-foreground: var(--foreground);
    --color-card: var(--card);
    --color-card-foreground: var(--card-foreground);
    --color-muted: var(--muted);
    --color-muted-foreground: var(--muted-foreground);
    --color-border: var(--border);
    --color-input: var(--input);
    --color-primary: var(--primary);
    --color-primary-foreground: var(--primary-foreground);
    --color-secondary: var(--secondary);
    --color-secondary-foreground: var(--secondary-foreground);
    --color-destructive: var(--destructive);
    --color-destructive-foreground: var(--destructive-foreground);
    --color-sidebar: var(--sidebar-background);
    --color-sidebar-border: var(--sidebar-border);
}

:root {
    /* Light Mode Colors */
    --background: hsl(0 0% 100%);
    --foreground: hsl(0 0% 3.9%);
    --card: hsl(0 0% 100%);
    --card-foreground: hsl(0 0% 3.9%);
    --muted: hsl(0 0% 96.1%);
    --muted-foreground: hsl(0 0% 45.1%);
    --border: hsl(0 0% 92.8%);
    --input: hsl(0 0% 89.8%);
    --primary: hsl(0 0% 9%);
    --primary-foreground: hsl(0 0% 98%);
    --secondary: hsl(0 0% 92.1%);
    --secondary-foreground: hsl(0 0% 9%);
    --destructive: hsl(0 84.2% 60.2%);
    --destructive-foreground: hsl(0 0% 98%);
    --sidebar-background: hsl(0 0% 98%);
    --sidebar-border: hsl(0 0% 91%);
}

.dark {
    /* Dark Mode Colors */
    --background: hsl(0 0% 3.9%);
    --foreground: hsl(0 0% 98%);
    --card: hsl(0 0% 3.9%);
    --card-foreground: hsl(0 0% 98%);
    --muted: hsl(0 0% 16.08%);
    --muted-foreground: hsl(0 0% 63.9%);
    --border: hsl(0 0% 14.9%);
    --input: hsl(0 0% 14.9%);
    --primary: hsl(0 0% 98%);
    --primary-foreground: hsl(0 0% 9%);
    --secondary: hsl(0 0% 14.9%);
    --secondary-foreground: hsl(0 0% 98%);
    --destructive: hsl(0 84% 60%);
    --destructive-foreground: hsl(0 0% 98%);
    --sidebar-background: hsl(0 0% 7%);
    --sidebar-border: hsl(0 0% 15.9%);
}
```

## 🔧 How to Change Colors

### Method 1: Direct CSS Variable Modification
To change colors, modify the HSL values in `resources/css/app.css`:

```css
:root {
    /* Change primary color to blue */
    --primary: hsl(217 91% 60%);
    --primary-foreground: hsl(0 0% 98%);
}

.dark {
    /* Change primary color for dark mode */
    --primary: hsl(217 91% 70%);
    --primary-foreground: hsl(0 0% 9%);
}
```

### Method 2: Brand Color Presets
Create brand color presets by adding new CSS custom properties:

```css
:root {
    /* Brand Colors */
    --brand-primary: hsl(217 91% 60%);
    --brand-secondary: hsl(160 60% 45%);
    --brand-accent: hsl(30 80% 55%);
    
    /* Map to design system */
    --primary: var(--brand-primary);
    --secondary: var(--brand-secondary);
}

.dark {
    /* Dark mode brand colors */
    --brand-primary: hsl(217 91% 70%);
    --brand-secondary: hsl(160 60% 55%);
    --brand-accent: hsl(30 80% 65%);
    
    --primary: var(--brand-primary);
    --secondary: var(--brand-secondary);
}
```

### Method 3: Theme Variants
Create multiple theme variants:

```css
/* Default Theme */
:root {
    --primary: hsl(0 0% 9%);
    --secondary: hsl(0 0% 92.1%);
}

/* Blue Theme */
.theme-blue {
    --primary: hsl(217 91% 60%);
    --secondary: hsl(217 91% 95%);
}

/* Green Theme */
.theme-green {
    --primary: hsl(160 60% 45%);
    --secondary: hsl(160 60% 95%);
}

/* Purple Theme */
.theme-purple {
    --primary: hsl(280 65% 60%);
    --secondary: hsl(280 65% 95%);
}
```

## 🎯 Color Usage Guidelines

### Always Use Design System Colors
✅ **Correct - Use semantic color classes:**
```vue
<!-- Labels and secondary text -->
<label class="text-sm font-medium text-muted-foreground">Field Label</label>

<!-- Main content text -->
<p class="text-foreground">Main content text</p>

<!-- Card backgrounds -->
<div class="bg-card text-card-foreground">

<!-- Borders -->
<div class="border-border">

<!-- Buttons -->
<button class="bg-primary hover:bg-primary/90 text-primary-foreground">
```

❌ **Incorrect - Don't use hardcoded colors:**
```vue
<!-- These don't adapt to dark mode properly -->
<label class="text-sm font-medium text-gray-500">Field Label</label>
<p class="text-gray-900">Main content text</p>
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
<div class="border-gray-200 dark:border-gray-700">
<button class="bg-blue-600 hover:bg-blue-700 text-white">
```

### Key Design System Colors

| Purpose | Tailwind Class | Light Mode | Dark Mode | Usage |
|---------|----------------|------------|-----------|-------|
| **Primary Text** | `text-foreground` | `hsl(0 0% 3.9%)` | `hsl(0 0% 98%)` | Main content, headings |
| **Secondary Text** | `text-muted-foreground` | `hsl(0 0% 45.1%)` | `hsl(0 0% 63.9%)` | Labels, timestamps, metadata |
| **Page Background** | `bg-background` | `hsl(0 0% 100%)` | `hsl(0 0% 3.9%)` | Main page background |
| **Card Background** | `bg-card` | `hsl(0 0% 100%)` | `hsl(0 0% 3.9%)` | Modal, card backgrounds |
| **Card Text** | `text-card-foreground` | `hsl(0 0% 3.9%)` | `hsl(0 0% 98%)` | Text inside cards/modals |
| **Borders** | `border-border` | `hsl(0 0% 92.8%)` | `hsl(0 0% 14.9%)` | All borders |
| **Primary Button** | `bg-primary text-primary-foreground` | `hsl(0 0% 9%)` | `hsl(0 0% 98%)` | Main action buttons |
| **Secondary Button** | `bg-secondary text-secondary-foreground` | `hsl(0 0% 92.1%)` | `hsl(0 0% 14.9%)` | Secondary buttons |
| **Destructive** | `text-destructive` | `hsl(0 84.2% 60.2%)` | `hsl(0 84% 60%)` | Error states, delete actions |

## 🚨 Common Color Issues & Solutions

### Issue: Dark Text in Dark Mode
**Problem**: Using `text-gray-900` or similar dark colors that don't adapt to dark mode.

**Solution**: Use `text-foreground` instead:
```vue
<!-- ❌ Problem -->
<p class="text-gray-900">This text is hard to read in dark mode</p>

<!-- ✅ Solution -->
<p class="text-foreground">This text adapts to both light and dark modes</p>
```

### Issue: Poor Contrast Labels
**Problem**: Using `text-gray-500` for labels that don't have enough contrast in dark mode.

**Solution**: Use `text-muted-foreground`:
```vue
<!-- ❌ Problem -->
<label class="text-sm font-medium text-gray-500">Field Label</label>

<!-- ✅ Solution -->
<label class="text-sm font-medium text-muted-foreground">Field Label</label>
```

### Issue: Hardcoded Modal Colors
**Problem**: Modal content using hardcoded colors that don't work in dark mode.

**Solution**: Use semantic color classes:
```vue
<!-- ❌ Problem -->
<div class="bg-white text-gray-900 border-gray-200">
    <label class="text-gray-500">Created</label>
    <p class="text-gray-900">12/10/2025</p>
</div>

<!-- ✅ Solution -->
<div class="bg-card text-card-foreground border-border">
    <label class="text-muted-foreground">Created</label>
    <p class="text-foreground">12/10/2025</p>
</div>
```

### Issue: Inconsistent Button Colors
**Problem**: Using hardcoded button colors that don't match the theme.

**Solution**: Use design system button colors:
```vue
<!-- ❌ Problem -->
<button class="bg-blue-600 hover:bg-blue-700 text-white">

<!-- ✅ Solution -->
<button class="bg-primary hover:bg-primary/90 text-primary-foreground">
```

## 🚀 Adding New Colors

### Step 1: Add CSS Custom Property
```css
:root {
    --success: hsl(120 60% 50%);
    --success-foreground: hsl(0 0% 98%);
    --warning: hsl(45 90% 50%);
    --warning-foreground: hsl(0 0% 9%);
}

.dark {
    --success: hsl(120 60% 60%);
    --success-foreground: hsl(0 0% 9%);
    --warning: hsl(45 90% 60%);
    --warning-foreground: hsl(0 0% 9%);
}
```

### Step 2: Add to Tailwind Theme
```css
@theme inline {
    --color-success: var(--success);
    --color-success-foreground: var(--success-foreground);
    --color-warning: var(--warning);
    --color-warning-foreground: var(--warning-foreground);
}
```

### Step 3: Use in Components
```vue
<template>
    <div class="bg-success text-success-foreground">
        Success message
    </div>
    <div class="bg-warning text-warning-foreground">
        Warning message
    </div>
</template>
```

## 🔄 Theme Switching

### Automatic Theme Detection
The system automatically detects user's system preference and applies the appropriate theme.

### Manual Theme Control
Users can manually switch themes via the Settings → Appearance page:

```vue
<template>
    <div class="flex space-x-2">
        <button @click="setTheme('light')" :class="{ 'bg-primary': theme === 'light' }">
            Light
        </button>
        <button @click="setTheme('dark')" :class="{ 'bg-primary': theme === 'dark' }">
            Dark
        </button>
        <button @click="setTheme('system')" :class="{ 'bg-primary': theme === 'system' }">
            System
        </button>
    </div>
</template>
```

## 📱 Responsive Design

### Breakpoints
Use Tailwind's default breakpoints:
- `sm`: 640px
- `md`: 768px
- `lg`: 1024px
- `xl`: 1280px
- `2xl`: 1536px

### Example Usage
```vue
<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <!-- Responsive grid -->
    </div>
</template>
```

## ✅ Best Practices

1. **Consistency**: Always use design system colors (`text-foreground`, `text-muted-foreground`, `bg-card`, etc.)
2. **Accessibility**: Ensure proper contrast ratios (4.5:1 minimum) - design system colors are tested for this
3. **Performance**: Use `transition-colors` for smooth theme changes
4. **Maintainability**: Keep colors centralized in CSS custom properties
5. **Testing**: Test both light and dark modes with real content
6. **Documentation**: Update this file when adding new colors
7. **Modal Content**: Always use `text-foreground` for main content and `text-muted-foreground` for labels
8. **Never Hardcode**: Avoid `text-gray-900`, `text-gray-500`, `bg-white`, etc. - use semantic classes instead
9. **Timestamps & Metadata**: Use `text-muted-foreground` for dates, labels, and secondary information
10. **Form Elements**: Use `text-foreground` for input values and `text-muted-foreground` for labels

## 🛠️ Development Workflow

### Making Color Changes
1. Edit `resources/css/app.css`
2. Run `npm run dev` to rebuild assets
3. Test both light and dark modes
4. Update component documentation if needed

### Adding New Theme Variants
1. Add CSS custom properties to `:root` and `.dark`
2. Add corresponding Tailwind classes in `@theme inline`
3. Update this documentation
4. Test all components with new colors

---

## 📋 Quick Reference

### Essential Color Classes
```css
/* Text Colors */
text-foreground          /* Main content text */
text-muted-foreground    /* Labels, timestamps, metadata */
text-card-foreground    /* Text inside cards/modals */

/* Background Colors */
bg-background           /* Main page background */
bg-card                 /* Card/modal backgrounds */
bg-primary              /* Primary buttons */
bg-secondary            /* Secondary buttons */

/* Border Colors */
border-border           /* All borders */
border-input            /* Input borders */

/* State Colors */
text-destructive        /* Error states */
text-primary            /* Primary accent text */
```

### Common Patterns
```vue
<!-- Modal Content -->
<div class="bg-card text-card-foreground border-border">
    <label class="text-muted-foreground">Label</label>
    <p class="text-foreground">Content</p>
</div>

<!-- Form Elements -->
<label class="text-muted-foreground">Field Label</label>
<input class="bg-background border-input text-foreground" />

<!-- Buttons -->
<button class="bg-primary text-primary-foreground">Primary</button>
<button class="bg-secondary text-secondary-foreground">Secondary</button>
```

This theme system provides a flexible, maintainable way to manage colors across the entire application while ensuring consistency, accessibility, and seamless dark mode support.

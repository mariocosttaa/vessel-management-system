# Layout Patterns & Design System

This document defines the standard layout patterns, design system colors, and component structure for the Vessel Management System.

## 🎨 Design System Colors

### CSS Custom Properties
All colors are defined as CSS custom properties in `resources/css/app.css`:

```css
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

### Tailwind Color Classes
Use these Tailwind classes for consistent styling:

| Purpose | Light Mode | Dark Mode | Usage |
|---------|------------|-----------|-------|
| **Background** | `bg-background` | `dark:bg-background` | Main page background |
| **Card Background** | `bg-card` | `dark:bg-card` | Card containers |
| **Text Primary** | `text-card-foreground` | `dark:text-card-foreground` | Main text content |
| **Text Secondary** | `text-muted-foreground` | `dark:text-muted-foreground` | Secondary text |
| **Borders** | `border-border` | `dark:border-border` | General borders |
| **Sidebar Borders** | `border-sidebar-border/70` | `dark:border-sidebar-border` | Card borders |
| **Input Background** | `bg-background` | `dark:bg-background` | Form inputs |
| **Input Borders** | `border-input` | `dark:border-input` | Input borders |
| **Hover States** | `hover:bg-muted/50` | `dark:hover:bg-muted/50` | Interactive elements |
| **Primary Button** | `bg-primary hover:bg-primary/90` | `text-primary-foreground` | Main actions |
| **Secondary Button** | `bg-secondary hover:bg-secondary/80` | `text-secondary-foreground` | Secondary actions |
| **Destructive** | `text-destructive hover:text-destructive/80` | `dark:text-destructive` | Delete/danger actions |

## 📐 Layout Structure

### Page Container Pattern
Every page should follow this container structure:

```vue
<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header Card -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <!-- Header content -->
            </div>

            <!-- Content Cards -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
                <!-- Main content -->
            </div>

            <!-- Additional Cards as needed -->
        </div>
    </AppLayout>
</template>
```

### Card Components

#### Header Card
```vue
<div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-card-foreground dark:text-card-foreground">Page Title</h1>
            <p class="text-muted-foreground dark:text-muted-foreground mt-1">Page description</p>
        </div>
        <Link
            :href="createUrl"
            class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors"
        >
            <Icon name="plus" class="w-4 h-4 mr-2" />
            Add Item
        </Link>
    </div>
</div>
```

#### Filters Card
```vue
<div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card p-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search Input -->
        <div>
            <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">Search</label>
            <input
                v-model="search"
                type="text"
                placeholder="Search items..."
                class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            />
        </div>
        
        <!-- Select Dropdown -->
        <div>
            <label class="block text-sm font-medium text-card-foreground dark:text-card-foreground mb-2">Filter</label>
            <select
                v-model="filter"
                class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            >
                <option value="">All Items</option>
                <option v-for="item in options" :key="item.value" :value="item.value">
                    {{ item.label }}
                </option>
            </select>
        </div>
        
        <!-- Clear Button -->
        <div class="flex items-end">
            <button
                @click="clearFilters"
                class="w-full px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors"
            >
                Clear Filters
            </button>
        </div>
    </div>
</div>
```

#### Data Table Card
```vue
<div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-border dark:divide-border">
            <thead class="bg-muted/50 dark:bg-muted/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider">
                        Column Header
                    </th>
                </tr>
            </thead>
            <tbody class="bg-card dark:bg-card divide-y divide-border dark:divide-border">
                <tr class="hover:bg-muted/50 dark:hover:bg-muted/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-card-foreground dark:text-card-foreground">
                        Cell Content
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div v-if="pagination?.links?.length > 3" class="bg-card dark:bg-card px-4 py-3 border-t border-border dark:border-border sm:px-6">
        <!-- Pagination content -->
    </div>
</div>
```

## 🎯 Status Badges

### Status Badge Pattern
```vue
<span :class="getStatusBadgeClass(status)">
    {{ statusLabel }}
</span>
```

### Status Badge Function
```javascript
const getStatusBadgeClass = (status: string) => {
    const baseClass = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium';

    switch (status) {
        case 'active':
            return `${baseClass} bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400`;
        case 'maintenance':
            return `${baseClass} bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400`;
        case 'inactive':
            return `${baseClass} bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400`;
        default:
            return `${baseClass} bg-muted text-muted-foreground dark:bg-muted dark:text-muted-foreground`;
    }
};
```

## 🔧 Form Elements

### Input Fields
```vue
<input
    v-model="value"
    type="text"
    placeholder="Placeholder text"
    class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
/>
```

### Select Dropdowns
```vue
<select
    v-model="value"
    class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
>
    <option value="">Select option</option>
    <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
    </option>
</select>
```

### Buttons

#### Primary Button
```vue
<button class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors">
    <Icon name="icon-name" class="w-4 h-4 mr-2" />
    Button Text
</button>
```

#### Secondary Button
```vue
<button class="px-4 py-2 border border-border dark:border-border rounded-lg bg-secondary hover:bg-secondary/80 text-secondary-foreground dark:text-secondary-foreground font-medium transition-colors">
    Button Text
</button>
```

#### Destructive Button
```vue
<button class="text-destructive hover:text-destructive/80 dark:text-destructive dark:hover:text-destructive/80 transition-colors">
    Delete
</button>
```

## 📱 Responsive Design

### Grid Layouts
- **Mobile**: `grid-cols-1`
- **Tablet**: `md:grid-cols-2` or `md:grid-cols-3`
- **Desktop**: `lg:grid-cols-4` or `lg:grid-cols-5`

### Spacing
- **Card Gap**: `gap-4` between cards
- **Card Padding**: `p-6` for card content
- **Table Padding**: `px-6 py-4` for table cells

## 🎨 Theme Management

### Tailwind Configuration
The theme colors are managed through Tailwind's CSS custom properties system. To change colors:

1. **Update CSS Variables**: Modify `resources/css/app.css`
2. **Rebuild Assets**: Run `npm run dev` or `npm run build`
3. **Test Both Modes**: Verify light and dark mode appearance

### Adding New Colors
To add new colors to the design system:

1. Add CSS custom property to `:root` and `.dark`
2. Add corresponding Tailwind class in `@theme inline`
3. Update this documentation
4. Use consistently across components

## ✅ Checklist for New Pages

When creating new pages, ensure:

- [ ] Uses `AppLayout` with proper breadcrumbs
- [ ] Implements card-based container structure
- [ ] Uses design system colors (no hardcoded colors)
- [ ] Includes proper dark mode support
- [ ] Has responsive design
- [ ] Uses consistent spacing and typography
- [ ] Includes proper hover states and transitions
- [ ] Follows form element patterns
- [ ] Implements proper status badges if needed
- [ ] Includes pagination for data tables

## 🚀 Best Practices

1. **Consistency**: Always use the design system colors
2. **Accessibility**: Ensure proper contrast ratios
3. **Performance**: Use `transition-colors` for smooth theme changes
4. **Maintainability**: Keep colors centralized in CSS custom properties
5. **Testing**: Test both light and dark modes
6. **Documentation**: Update this file when adding new patterns

---

This layout pattern ensures consistency, maintainability, and a professional appearance across the entire application.

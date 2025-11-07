# Quick Reference Guide

## 🚀 Getting Started

### Essential Files
- **Layout Patterns**: `docs/layout-patterns.md` - Complete layout and design system guide
- **Frontend Patterns**: `docs/patterns/frontend-patterns.md` - Vue.js component patterns
- **Theme Configuration**: `docs/theme-configuration.md` - Color and theme management
- **Database Schema**: `docs/database-schema.md` - Complete database structure
- **Implementation Guide**: `docs/implementation-guide.md` - Phase-by-phase roadmap

## 🎨 Design System Quick Reference

### Container Structure
```vue
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
    </div>
</AppLayout>
```

### Essential Color Classes
| Purpose | Class |
|---------|-------|
| **Text Primary** | `text-card-foreground dark:text-card-foreground` |
| **Text Secondary** | `text-muted-foreground dark:text-muted-foreground` |
| **Card Background** | `bg-card dark:bg-card` |
| **Borders** | `border-border dark:border-border` |
| **Input Background** | `bg-background dark:bg-background` |
| **Input Borders** | `border-input dark:border-input` |
| **Primary Button** | `bg-primary hover:bg-primary/90 text-primary-foreground` |
| **Secondary Button** | `bg-secondary hover:bg-secondary/80 text-secondary-foreground` |
| **Destructive** | `text-destructive hover:text-destructive/80` |

### Form Elements
```vue
<!-- Input Field -->
<input class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground placeholder:text-muted-foreground dark:placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent" />

<!-- Select Dropdown -->
<select class="w-full px-3 py-2 border border-input dark:border-input rounded-lg bg-background dark:bg-background text-foreground dark:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent">
    <!-- options -->
</select>

<!-- Button -->
<button class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors">
    Button Text
</button>
```

### Data Table
```vue
<div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card dark:bg-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-border dark:divide-border">
            <thead class="bg-muted/50 dark:bg-muted/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground dark:text-muted-foreground uppercase tracking-wider">
                        Header
                    </th>
                </tr>
            </thead>
            <tbody class="bg-card dark:bg-card divide-y divide-border dark:divide-border">
                <tr class="hover:bg-muted/50 dark:hover:bg-muted/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-card-foreground dark:text-card-foreground">
                        Content
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

## 🔧 Development Workflow

### Creating New Pages
1. Follow the container structure pattern
2. Use design system colors only
3. Implement proper dark mode support
4. Add responsive design
5. Include proper hover states and transitions

### Adding New Colors
1. Add CSS custom property to `resources/css/app.css`
2. Add corresponding Tailwind class in `@theme inline`
3. Update `docs/theme-configuration.md`
4. Test both light and dark modes

### Component Checklist
- [ ] Uses design system colors
- [ ] Implements dark mode support
- [ ] Has proper hover states
- [ ] Includes transitions
- [ ] Is responsive
- [ ] Follows layout patterns
- [ ] Uses proper semantic HTML

## 📁 File Structure

```
docs/
├── layout-patterns.md          # Complete layout guide
├── theme-configuration.md      # Color and theme management
├── database-schema.md         # Database structure
├── implementation-guide.md    # Development roadmap
└── patterns/
    ├── frontend-patterns.md   # Vue.js patterns
    ├── controller-patterns.md # Laravel controllers
    ├── model-patterns.md      # Eloquent models
    ├── request-patterns.md    # Form requests
    └── resource-patterns.md   # API resources
```

## 🔐 Permission System Quick Reference

### User Types
- **`paid_system`**: Can create vessels, becomes vessel owner/administrator
- **`employee_of_vessel`**: Can only access assigned vessels

### Vessel Roles
- **`normal`**: View-only access
- **`moderator`**: View + edit basic data
- **`supervisor`**: View + edit basic + advanced data
- **`administrator`**: Full control (owner-level)

### Permission Checks
```php
// Backend - User model methods
$user->canCreateVessels()                    // Check if user can create vessels
$user->canEditVessel($vesselId)             // Check edit permission for specific vessel
$user->canDeleteVessel($vesselId)           // Check delete permission for specific vessel
$user->canManageVesselUsers($vesselId)      // Check user management permission
$user->getVesselRoleAccess($vesselId)       // Get role access for vessel
```

### Frontend Permission Usage
```vue
<!-- Show/hide based on vessel permissions -->
<button v-if="vessel.permissions.can_edit" @click="editVessel">
    Edit Vessel
</button>

<button v-if="vessel.permissions.can_delete" @click="deleteVessel">
    Delete Vessel
</button>

<!-- Show create button only for paid_system users -->
<button v-if="permissions.can_create_vessels" @click="createVessel">
    Create New Vessel
</button>
```

## 🎯 Key Principles

1. **Consistency**: Always use design system colors
2. **Accessibility**: Ensure proper contrast ratios
3. **Performance**: Use `transition-colors` for smooth changes
4. **Maintainability**: Keep colors centralized
5. **Testing**: Test both light and dark modes
6. **Documentation**: Update docs when adding new patterns
7. **Security**: Always check vessel-specific permissions
8. **User Experience**: Show appropriate actions based on user role

## 🚨 Common Mistakes to Avoid

❌ **Don't use hardcoded colors:**
```vue
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
```

✅ **Use design system colors:**
```vue
<div class="bg-card dark:bg-card text-card-foreground dark:text-card-foreground">
```

❌ **Don't forget dark mode:**
```vue
<button class="bg-blue-600 text-white">
```

✅ **Always include dark mode:**
```vue
<button class="bg-primary hover:bg-primary/90 text-primary-foreground">
```

---

**Remember**: Always refer to the complete documentation files for detailed patterns and examples!

# Documentation Guide for Vessel Management System

This directory contains comprehensive documentation for the Vessel Management Financial System. This guide explains each file and provides instructions for AI assistants on how to effectively use the documentation.

## 📚 Documentation Overview

The documentation is organized into **core guides** and **pattern references** to help developers and AI assistants understand the system architecture, implementation patterns, and best practices.

## 🎯 Quick Start for AI Assistants

**When working on this project, always start by reading these files in order:**

1. **Start Here**: `quick-reference.md` - Essential patterns and quick lookup
2. **Architecture**: `implementation-guide.md` - Complete system roadmap
3. **Database**: `database-schema.md` - Complete data structure
4. **RBAC System**: `vessel-rbac-overview.md` - Vessel-specific permission system
5. **Patterns**: Read relevant pattern files based on your task

## 📁 Core Documentation Files

### 🚀 Essential Guides

#### `quick-reference.md`
**Purpose**: Quick lookup for common patterns and essential information
**When to use**: 
- Need quick access to design system colors
- Looking up form patterns or component structures
- Checking common mistakes to avoid
- Getting started with development

**Key Content**:
- Design system quick reference
- Essential color classes
- Form elements patterns
- Data table structure
- Development workflow checklist

#### `implementation-guide.md`
**Purpose**: Complete phase-by-phase implementation roadmap
**When to use**:
- Understanding the overall system architecture
- Planning development phases
- Understanding dependencies between features
- Getting context on the complete system

**Key Content**:
- 8-phase implementation roadmap
- Dependencies and critical paths
- Testing strategy
- Code quality standards
- Success metrics and risk mitigation

#### `database-schema.md`
**Purpose**: Complete database structure and relationships
**When to use**:
- Understanding data models and relationships
- Planning migrations or schema changes
- Understanding business logic constraints
- Reference for model development

**Key Content**:
- Complete SQL schema for all tables
- Relationship mappings
- Index strategies
- Data types and constraints
- Migration order

#### `vessel-rbac-overview.md`
**Purpose**: Comprehensive overview of the vessel-specific RBAC system
**When to use**:
- Understanding the permission system architecture
- Planning user roles and permissions
- Implementing vessel-specific access control
- Understanding security model

**Key Content**:
- User types and vessel roles
- Permission matrix and capabilities
- Security benefits and implementation
- Use cases and scenarios
- Technical implementation details

### 🎨 Design and Layout

#### `layout-patterns.md`
**Purpose**: Complete layout system and design patterns
**When to use**:
- Creating new pages or components
- Understanding the design system
- Implementing consistent UI patterns
- Working with cards, forms, and tables

**Key Content**:
- Design system colors (CSS custom properties)
- Page container patterns
- Card component structures
- Form element patterns
- Status badges and responsive design
- Complete component examples

#### `theme-configuration.md`
**Purpose**: Theme and color management system
**When to use**:
- Changing colors or themes
- Adding new color variants
- Understanding the CSS custom property system
- Implementing dark mode support

**Key Content**:
- CSS custom property definitions
- Color system location
- Methods for changing colors
- Theme switching implementation
- Responsive design breakpoints
- Best practices for theme management

## 📁 Pattern Documentation (`patterns/`)

### Backend Patterns

#### `model-patterns.md`
**Purpose**: Eloquent model patterns and conventions
**When to use**:
- Creating or modifying models
- Understanding relationships
- Implementing scopes and accessors
- Working with money handling
- Understanding boot methods

**Key Content**:
- Model structure and naming conventions
- Relationship definitions (BelongsTo, HasMany, MorphMany)
- Query scopes for common filters
- Money handling trait integration
- Accessors and mutators
- Boot methods for auto-calculations
- Complete model examples

#### `controller-patterns.md`
**Purpose**: Laravel controller patterns and conventions
**When to use**:
- Creating new controllers
- Implementing CRUD operations
- Understanding Inertia responses
- Working with authorization
- Error handling patterns

**Key Content**:
- Controller structure and naming
- Action naming conventions
- Inertia response patterns
- Authorization implementation
- Error handling strategies
- Resource loading optimization

#### `request-patterns.md`
**Purpose**: Form request validation patterns
**When to use**:
- Creating validation requests
- Implementing data normalization
- Working with multilanguage fields
- Understanding authorization in requests
- Implementing conditional validation

**Key Content**:
- Request structure and naming conventions
- PHPDoc property annotations (MANDATORY)
- Hashed ID decoding patterns
- Database validation with Rule::exists/unique
- Multilanguage field validation
- Money and date normalization
- Conditional validation logic
- Complete request examples

#### `resource-patterns.md`
**Purpose**: Laravel API resource patterns
**When to use**:
- Creating API resources
- Understanding data transformation
- Working with relationships in resources
- Implementing conditional field inclusion
- Money formatting for frontend

**Key Content**:
- Resource organization (General, Detailed, List, Api)
- Basic resource structure
- Conditional field inclusion patterns
- Nested resource handling
- Money formatting patterns
- Relationship loading optimization
- Complete resource examples

### Frontend Patterns

#### `frontend-patterns.md`
**Purpose**: Vue.js component patterns and conventions
**When to use**:
- Creating Vue.js components
- Understanding component structure
- Working with TypeScript
- Implementing forms and modals
- Understanding state management

**Key Content**:
- Vue.js 3 Composition API patterns
- Component structure and naming
- TypeScript integration
- Form handling patterns
- Modal implementation
- State management with Pinia
- Complete component examples

#### `modal-patterns.md`
**Purpose**: Modal implementation patterns and conventions
**When to use**:
- Creating modal components
- Understanding modal structure
- Implementing form modals
- Working with modal state management

**Key Content**:
- Modal component structure
- Form modal patterns
- State management
- Event handling
- Accessibility features

#### `base-modal-pattern.md`
**Purpose**: Unified BaseModal component with API request support
**When to use**:
- Creating any type of modal (show, create, edit, confirm)
- Implementing loading states and error handling
- Making API requests from modals
- Ensuring consistent modal behavior

**Key Content**:
- BaseModal component API
- API request integration
- Loading states and error handling
- Slot-based content system
- Migration from custom modals

#### `show-modal-pattern.md`
**Purpose**: Show modal pattern with separate API requests
**When to use**:
- Implementing show modals for detailed views
- Optimizing data loading for tables
- Creating separate API endpoints for detailed data
- Implementing loading states and error handling

**Key Content**:
- Show modal architecture
- Separate API endpoint pattern
- Conditional resource loading
- Loading states and error handling
- Performance optimization

### Specialized Patterns

#### `notification-patterns.md`
**Purpose**: Complete notification system implementation patterns
**When to use**:
- Implementing user feedback for CRUD operations
- Creating confirmation dialogs for destructive actions
- Working with flash messages from backend
- Understanding notification components and composables
- Implementing accessibility-compliant notifications
- Setting up middleware for flash message sharing

**Key Content**:
- Backend flash message patterns with try-catch blocks
- Frontend notification components (NotificationContainer, NotificationItem)
- Confirmation dialog implementation for destructive operations
- useNotifications TypeScript composable
- HandleInertiaRequests middleware configuration
- Notification types and best practices
- Complete notification examples with error handling

### Development Workflow

#### `git-commit-pattners.md`
**Purpose**: Git commit guidelines and best practices
**When to use**:
- Making commits
- Understanding commit grouping rules
- Following commit message format
- Maintaining clean git history

**Key Content**:
- Maximum 5 files per commit rule
- Commit message format
- Grouping related files
- Commit types and examples
- Workflow best practices

## 🤖 Instructions for AI Assistants

### How to Use This Documentation

1. **Start with Context**: Always read `quick-reference.md` first to understand the system
2. **Understand Architecture**: Read `implementation-guide.md` for complete system context
3. **Check Database**: Reference `database-schema.md` for data structure understanding
4. **Follow Patterns**: Use specific pattern files based on your task:
   - **Models**: Use `model-patterns.md`
   - **Controllers**: Use `controller-patterns.md`
   - **Requests**: Use `request-patterns.md`
   - **Resources**: Use `resource-patterns.md`
   - **Frontend**: Use `frontend-patterns.md`
   - **Modals**: Use `modal-patterns.md`
   - **BaseModal**: Use `base-modal-pattern.md`
   - **Show Modals**: Use `show-modal-pattern.md`
   - **Money**: Use `money-handling.md`
   - **Layout**: Use `layout-patterns.md`
   - **Theme**: Use `theme-configuration.md`
   - **Permissions**: Use `permissions-patterns.md`
   - **Notifications**: Use `notification-patterns.md`
   - **Multi-Tenant**: Use `multi-tenant-patterns.md` ⭐ **ESSENTIAL FOR VESSEL-BASED SYSTEM**

### Task-Specific Guidance

#### When Creating New Features:
1. Read `implementation-guide.md` to understand the phase
2. Check `database-schema.md` for required tables
3. Follow `multi-tenant-patterns.md` for vessel-based architecture ⭐ **ESSENTIAL**
4. Follow `model-patterns.md` for model creation
5. Use `controller-patterns.md` for controller implementation
6. Apply `request-patterns.md` for validation
7. Use `resource-patterns.md` for API responses
8. Follow `frontend-patterns.md` for Vue.js components
9. Apply `permissions-patterns.md` for authorization
10. Apply `notification-patterns.md` for user feedback
11. Apply `layout-patterns.md` for UI consistency

#### When Working with Money:
1. Always read `money-handling.md` first
2. Use MoneyAction for all money operations (formatting, sanitization, currency detection)
3. Follow money formatting patterns from `resource-patterns.md`
4. Implement proper VAT calculations
5. Never use EUR as fallback - always detect currency from country/IBAN

#### When Creating UI Components:
1. Start with `layout-patterns.md` for design system
2. Use `theme-configuration.md` for colors
3. Follow `frontend-patterns.md` for Vue.js patterns
4. Use `base-modal-pattern.md` for all modal components
5. Use `modal-patterns.md` for custom modal implementations
6. Use `show-modal-pattern.md` for show modals with separate API requests
7. Ensure responsive design and dark mode support

#### When Implementing CRUD Operations:
1. Follow the complete workflow in `implementation-guide.md`
2. Use patterns from all relevant files:
   - Multi-Tenant: `multi-tenant-patterns.md` ⭐ **ESSENTIAL**
   - Model: `model-patterns.md`
   - Controller: `controller-patterns.md`
   - Request: `request-patterns.md`
   - Resource: `resource-patterns.md`
   - Frontend: `frontend-patterns.md`
   - Permissions: `permissions-patterns.md`
   - Notifications: `notification-patterns.md`

### Critical Rules to Follow

1. **Always use design system colors** - Never hardcode colors
2. **Follow money handling patterns** - Always store as integers in cents
3. **Implement proper validation** - Use Rule::exists/unique for database validation
4. **Include PHPDoc annotations** - Required for all request classes
5. **Follow commit guidelines** - Maximum 5 files per commit
6. **Use proper relationship loading** - Avoid N+1 queries
7. **Implement dark mode support** - All components must support both themes
8. **Follow naming conventions** - Consistent across all layers
9. **Implement proper authorization** - Always check permissions before actions
10. **Use TypeScript for composables** - Better type safety and development experience
11. **Provide user feedback** - Always show notifications for user actions
12. **Use confirmation dialogs** - Always confirm destructive operations
13. **Handle errors gracefully** - Use try-catch blocks and proper error messages
14. **Never use EUR as fallback** - Always detect currency from country/IBAN
15. **Always filter by vessel** - Every query must include vessel_id filtering ⭐ **ESSENTIAL**
16. **Use vessel-specific authorization** - Always check vessel-based roles ⭐ **ESSENTIAL**
17. **Inject vessel_id automatically** - Always inject vessel_id in store methods ⭐ **ESSENTIAL**
18. **Use VesselLayout for vessel pages** - Never use AppLayout for vessel-specific pages ⭐ **ESSENTIAL**

### Common Mistakes to Avoid

❌ **Don't hardcode colors**: Use design system colors only
❌ **Don't store money as floats**: Always use integers in cents
❌ **Don't skip validation**: Always implement proper request validation
❌ **Don't forget dark mode**: All components must support both themes
❌ **Don't ignore relationships**: Always eager load relationships
❌ **Don't mix unrelated changes**: Follow commit grouping rules
❌ **Don't skip authorization**: Always check permissions before actions
❌ **Don't use JavaScript for composables**: Use TypeScript for better type safety
❌ **Don't skip user feedback**: Always provide notifications for user actions
❌ **Don't skip confirmation dialogs**: Always confirm destructive operations
❌ **Don't ignore error handling**: Always use try-catch blocks and proper error messages
❌ **Don't use EUR as fallback**: Always detect currency from country/IBAN
❌ **Don't forget vessel filtering**: Always filter queries by vessel_id ⭐ **CRITICAL**
❌ **Don't use global permissions**: Always use vessel-specific role checks ⭐ **CRITICAL**
❌ **Don't skip vessel injection**: Always inject vessel_id in store methods ⭐ **CRITICAL**
❌ **Don't use AppLayout**: Use VesselLayout for vessel-specific pages ⭐ **CRITICAL**
❌ **Don't forget vessel parameter**: Always include vessel in route generation ⭐ **CRITICAL**

## 📋 Quick Reference Checklist

Before implementing any feature, ensure you have:

- [ ] Read relevant pattern documentation
- [ ] Read `multi-tenant-patterns.md` for vessel-based architecture ⭐ **ESSENTIAL**
- [ ] Understood the database schema
- [ ] Followed naming conventions
- [ ] Implemented proper validation
- [ ] Used design system colors
- [ ] Added dark mode support
- [ ] Followed money handling patterns
- [ ] Implemented proper error handling
- [ ] Added proper TypeScript types
- [ ] Followed commit guidelines
- [ ] Implemented proper authorization
- [ ] Used TypeScript for composables
- [ ] Provided user feedback notifications
- [ ] Added confirmation dialogs for destructive operations
- [ ] Implemented proper error handling with try-catch blocks
- [ ] Used currency detection instead of EUR fallback
- [ ] Added vessel_id filtering to all queries ⭐ **ESSENTIAL**
- [ ] Implemented vessel-specific authorization ⭐ **ESSENTIAL**
- [ ] Used VesselLayout for vessel pages ⭐ **ESSENTIAL**
- [ ] Included vessel parameter in all routes ⭐ **ESSENTIAL**

## 🔗 File Relationships

```
docs/
├── README.md (this file)
├── quick-reference.md (start here)
├── implementation-guide.md (architecture)
├── database-schema.md (data structure)
├── vessel-rbac-overview.md (permission system overview)
├── layout-patterns.md (UI patterns)
├── theme-configuration.md (colors/themes)
└── patterns/
    ├── model-patterns.md (Eloquent models)
    ├── controller-patterns.md (Laravel controllers)
    ├── request-patterns.md (validation)
    ├── resource-patterns.md (API resources)
    ├── frontend-patterns.md (Vue.js)
    ├── modal-patterns.md (modal components)
    ├── base-modal-pattern.md (unified BaseModal)
    ├── show-modal-pattern.md (show modals with API)
    ├── permissions-patterns.md (vessel-specific RBAC system)
    ├── notification-patterns.md (user feedback)
    ├── money-handling.md (monetary values)
    ├── multi-tenant-patterns.md (vessel-based architecture) ⭐ **ESSENTIAL**
    └── git-commit-pattners.md (commit rules)
```

## 🎯 Success Metrics

When following this documentation, you should achieve:

- ✅ Consistent code patterns across the application
- ✅ Proper money handling without precision issues
- ✅ Responsive design with dark mode support
- ✅ Clean git history with focused commits
- ✅ Proper validation and error handling
- ✅ Optimized database queries
- ✅ Maintainable and scalable code structure
- ✅ Sophisticated vessel-specific RBAC system
- ✅ Secure permission-based access control
- ✅ Granular user and vessel role management
- ✅ Complete multi-tenant vessel-based architecture ⭐ **ACHIEVED**
- ✅ Perfect data isolation between vessels ⭐ **ACHIEVED**
- ✅ Vessel-specific role and permission management ⭐ **ACHIEVED**
- ✅ Automatic vessel context injection ⭐ **ACHIEVED**
- ✅ Tenant-based authorization throughout the system ⭐ **ACHIEVED**

---

**Remember**: This documentation is designed to ensure consistency, maintainability, and best practices across the entire Vessel Management System. Always reference the appropriate files for your specific task and follow the established patterns.

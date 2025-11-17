# 🚢 Bindamy Mareas - Vessel Management Financial System

> A comprehensive, production-ready financial management system designed specifically for vessel operations. Built with modern technologies and best practices, showcasing enterprise-level full-stack development capabilities.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=flat-square&logo=vue.js)](https://vuejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.2-3178C6?style=flat-square&logo=typescript)](https://www.typescriptlang.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-2.1-9553E9?style=flat-square)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.1-38B2AC?style=flat-square&logo=tailwind-css)](https://tailwindcss.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql)](https://www.mysql.com)

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Technology Stack](#-technology-stack)
- [Key Features](#-key-features)
- [Architecture & Design Patterns](#-architecture--design-patterns)
- [Core Modules](#-core-modules)
- [Security & Authentication](#-security--authentication)
- [Performance & Scalability](#-performance--scalability)
- [Development Workflow](#-development-workflow)
- [Installation & Setup](#-installation--setup)
- [Deployment](#-deployment)
- [Testing](#-testing)
- [Documentation](#-documentation)
- [Project Highlights](#-project-highlights)

---

## 🎯 Overview

**Bindamy Mareas** is a sophisticated vessel management system that provides complete financial control and operational efficiency for fishing fleet operations. This enterprise-grade application demonstrates expertise in:

- **Full-Stack Development**: Laravel backend with Vue.js frontend
- **Modern Architecture**: Server-Side Rendering (SSR), Inertia.js SPA patterns
- **Financial Systems**: Precise money handling, VAT calculations, multi-currency support
- **Complex Business Logic**: Mareas (fishing trips), crew distribution, salary calculations
- **Enterprise Features**: Role-based access control, audit trails, multi-tenancy patterns
- **DevOps**: Docker containerization, queue workers, scheduled tasks

### Business Domain

The system manages the complete lifecycle of fishing vessel operations:
- **Mareas**: Complete fishing trip management from departure to return
- **Financial Transactions**: Income, expenses, transfers with automatic VAT
- **Crew Management**: Positions, salaries, and distribution calculations
- **Maintenance Tracking**: Scheduled and completed maintenance records
- **Reporting**: Financial reports, VAT compliance, cash flow analysis

---

## 🛠️ Technology Stack

### Backend
- **Framework**: Laravel 12.x (Latest)
- **PHP**: 8.3 with modern features (Enums, Attributes, Readonly Properties)
- **Database**: MySQL 8.0+ with optimized indexes and relationships
- **Queue System**: Laravel Queues with Redis support
- **Caching**: Redis for session and cache management
- **File Storage**: Laravel Filesystem (local/S3 compatible)

### Frontend
- **Framework**: Vue.js 3.5 with Composition API
- **TypeScript**: Full type safety across frontend
- **UI Framework**: shadcn-vue components (Radix UI primitives)
- **Styling**: Tailwind CSS 4.1 with custom design system
- **State Management**: Pinia (Vuex alternative)
- **Form Handling**: VueUse + Zod validation
- **Charts**: Recharts & Unovis for data visualization
- **Internationalization**: Vue I18n for multi-language support

### Architecture
- **SPA Framework**: Inertia.js 2.1 (Server-Side Rendering enabled)
- **Build Tool**: Vite 7.x with HMR and optimized builds
- **SSR**: Server-Side Rendering for improved SEO and performance
- **Routing**: Laravel Wayfinder for type-safe route generation

### DevOps & Infrastructure
- **Containerization**: Docker with multi-stage builds
- **Web Server**: Nginx with optimized Laravel configuration
- **Process Management**: Supervisor for queue workers, scheduler, SSR server
- **Package Management**: Composer (PHP) + npm (Node.js)

### Third-Party Integrations
- **PDF Generation**: DomPDF for financial reports
- **Excel Export**: Laravel Excel (Maatwebsite)
- **OAuth**: Laravel Socialite (Google, Microsoft)
- **2FA**: Laravel Fortify with TOTP
- **ID Hashing**: Hashids for secure public IDs

### Development Tools
- **Testing**: Pest PHP (modern PHP testing framework)
- **Code Quality**: Laravel Pint (PHP CS Fixer), ESLint, Prettier
- **Type Checking**: PHPStan, vue-tsc
- **Debugging**: Laravel Debugbar, Laravel Pail

---

## ✨ Key Features

### 🚢 Vessel Management
- **Multi-Vessel Support**: Manage entire fleets from a single platform
- **Vessel Registry**: Complete specifications, ownership, and status tracking
- **Vessel-Scoped Operations**: All features are vessel-aware with proper isolation
- **File Management**: Public and private file storage per vessel
- **Settings Management**: Per-vessel configuration (currency, location, etc.)

### 💰 Financial Management
- **Transaction System**: Complete CRUD for income, expenses, and transfers
- **Multi-Currency Support**: Handle multiple currencies with proper conversion
- **Money Precision**: Integer-based storage (cents) to avoid floating-point errors
- **Automatic VAT**: Configurable VAT rates with automatic calculations
- **Bank Account Tracking**: Multiple accounts with real-time balance calculations
- **Transaction Categories**: Flexible categorization system
- **Recurring Transactions**: Automated recurring payments (salaries, insurance, etc.)
- **Transaction History**: Complete audit trail with filtering and search

### 🐟 Mareas (Fishing Trips) Management
- **Trip Lifecycle**: Complete management from departure to return
- **Crew Assignment**: Assign crew members to specific trips
- **Catch Tracking**: Record quantity returns and catch details
- **Distribution Profiles**: Customizable payment distribution rules
- **Automatic Calculations**: Crew salary calculations based on catch and positions
- **Status Management**: Track trip status (planned, at sea, returned, closed, cancelled)
- **PDF Reports**: Generate detailed trip reports with all information

### 👥 Crew Management
- **Crew Registry**: Complete crew member database with positions
- **Position Management**: Define crew positions with salary structures
- **Invitation System**: Email-based crew invitations with secure tokens
- **Salary Tracking**: Automated salary calculations and payments
- **Compensation System**: Handle salary compensations and adjustments
- **Status Tracking**: Active, inactive, on-leave status management
- **Unified User System**: Crew members can be system users with login access

### 🔧 Maintenance Management
- **Maintenance Scheduling**: Plan and track vessel maintenance
- **Transaction Linking**: Link maintenance costs to financial transactions
- **Status Tracking**: Track maintenance from planned to completed
- **PDF Reports**: Generate maintenance reports
- **Finalization**: Complete maintenance records with all associated costs

### 📊 Reporting & Analytics
- **Dashboard**: Real-time financial metrics with interactive charts
- **Financial Reports**: Income statements, cash flow, profitability analysis
- **VAT Reports**: Complete VAT calculations and compliance reporting
- **Category Analysis**: Expense breakdown by category with visualizations
- **Monthly Summaries**: Month-over-month financial comparisons
- **Export Options**: PDF, Excel, and CSV export capabilities
- **Date Range Filtering**: Flexible date range selection for reports

### 🔄 Automation & Background Jobs
- **Recurring Transactions**: Automatic generation of recurring payments
- **Queue Workers**: Background processing for emails and heavy operations
- **Scheduled Tasks**: Laravel scheduler for daily/weekly/monthly tasks
- **Email Notifications**: Automated email notifications for important events
- **Balance Updates**: Automatic balance recalculation on transaction changes

### 🔐 Security & Access Control
- **Multi-Factor Authentication**: TOTP-based 2FA support
- **OAuth Integration**: Google and Microsoft OAuth login
- **Role-Based Access Control**: Granular permissions per vessel
- **Vessel-Scoped Permissions**: Users have different roles per vessel
- **Audit Logging**: Complete activity log for all user actions
- **Soft Deletes**: Recycle bin system for data recovery
- **CSRF Protection**: Built-in Laravel CSRF protection
- **SQL Injection Prevention**: Eloquent ORM with parameterized queries
- **XSS Protection**: Automatic output escaping in Vue templates

### 📁 Document Management
- **File Uploads**: Secure file uploads with validation
- **Attachment System**: Link files to transactions, mareas, and other entities
- **Public/Private Files**: Vessel-specific file access control
- **File Downloads**: Secure file download with proper authorization

### 🌐 User Experience
- **Responsive Design**: Mobile-first design with Tailwind CSS
- **Dark Mode**: Complete dark mode support throughout the application
- **Internationalization**: Multi-language support with Vue I18n
- **Loading States**: Proper loading indicators and skeleton screens
- **Error Handling**: Comprehensive error handling with user-friendly messages
- **Form Validation**: Real-time validation with clear error messages
- **Search & Filtering**: Advanced search and filtering across all modules

---

## 🏗️ Architecture & Design Patterns

### Backend Architecture

#### MVC Pattern with Service Layer
```
Controllers → Services → Models → Database
     ↓
  Requests (Validation)
     ↓
  Resources (Transformation)
```

- **Controllers**: Handle HTTP requests, return Inertia responses
- **Form Requests**: Validation and data normalization
- **Services**: Business logic abstraction (MoneyService, VatCalculationService)
- **Models**: Eloquent models with relationships, scopes, and accessors
- **Resources**: API resource transformation for consistent data formatting
- **Jobs**: Background job processing for async operations

#### Key Design Patterns
- **Repository Pattern**: Model abstraction for complex queries
- **Service Pattern**: Business logic encapsulation
- **Observer Pattern**: Model events for automatic calculations
- **Factory Pattern**: Model factories for testing
- **Strategy Pattern**: Money handling strategies per currency
- **Middleware Pattern**: Request/response filtering

### Frontend Architecture

#### Component-Based Architecture
```
Pages (Inertia)
  ↓
Layouts
  ↓
Components (Reusable)
  ↓
Composables (Shared Logic)
  ↓
Utils (Helpers)
```

- **Pages**: Inertia.js page components for routes
- **Layouts**: Consistent layout structure (AppLayout, IndexDefaultLayout)
- **Components**: Reusable Vue components (shadcn-vue based)
- **Composables**: Shared Vue composition functions
- **Types**: TypeScript interfaces for type safety
- **Utils**: Helper functions and utilities

#### State Management
- **Inertia.js**: Server-driven state (no API layer needed)
- **Pinia**: Client-side state for UI-only data
- **Composables**: Shared reactive state logic

### Database Design

#### Normalized Schema
- **28+ Tables**: Properly normalized with foreign key relationships
- **Indexes**: Optimized indexes for performance-critical queries
- **Soft Deletes**: Recycle bin functionality for data recovery
- **Polymorphic Relations**: Flexible attachment system
- **Pivot Tables**: Many-to-many relationships (vessel_users, marea_crew)

#### Performance Optimizations
- **Monthly Balance Tables**: Pre-calculated balances for fast reporting
- **Eager Loading**: N+1 query prevention
- **Query Scopes**: Reusable query filters
- **Database Indexes**: Strategic indexes on frequently queried columns

---

## 📦 Core Modules

### 1. Authentication & Authorization
- **Laravel Breeze**: Authentication scaffolding
- **Email Verification**: Required email verification
- **Password Reset**: Secure password reset flow
- **OAuth Providers**: Google and Microsoft integration
- **2FA Support**: TOTP-based two-factor authentication
- **Invitation System**: Secure crew invitation flow

### 2. Vessel Management
- **Vessel CRUD**: Complete vessel management
- **Vessel Selector**: Multi-vessel switching interface
- **Vessel Settings**: Per-vessel configuration
- **File Management**: Vessel-specific file storage

### 3. Financial Transactions
- **Transaction CRUD**: Full transaction management
- **Money Handling**: Integer-based precision system
- **VAT Calculations**: Automatic VAT with configurable rates
- **Category Management**: Transaction categorization
- **Supplier Management**: Supplier database
- **Recurring Transactions**: Automated recurring payments
- **History View**: Monthly transaction history

### 4. Mareas System
- **Marea CRUD**: Complete fishing trip management
- **Crew Assignment**: Assign crew to trips
- **Catch Tracking**: Quantity return recording
- **Distribution Profiles**: Custom payment distribution rules
- **Salary Calculations**: Automatic crew payment calculations
- **Status Workflow**: Trip status management

### 5. Crew Management
- **Crew Registry**: Complete crew member database
- **Position Management**: Crew position definitions
- **Invitation System**: Email-based crew invitations
- **Salary Tracking**: Crew salary management
- **Compensation System**: Salary adjustments

### 6. Maintenance System
- **Maintenance CRUD**: Maintenance record management
- **Transaction Linking**: Link costs to transactions
- **Status Tracking**: Maintenance workflow
- **PDF Reports**: Maintenance documentation

### 7. Reporting System
- **Dashboard**: Real-time financial metrics
- **Financial Reports**: Income statements, cash flow
- **VAT Reports**: Tax compliance reporting
- **Export System**: PDF, Excel, CSV exports

### 8. Audit & Monitoring
- **Activity Logs**: Complete user action tracking
- **Audit Trail**: Change history for all entities
- **Recycle Bin**: Soft delete recovery system

---

## 🔐 Security & Authentication

### Authentication Methods
- **Email/Password**: Traditional authentication with Laravel Breeze
- **OAuth 2.0**: Google and Microsoft OAuth integration
- **Two-Factor Authentication**: TOTP-based 2FA with recovery codes
- **Email Verification**: Required email verification for new accounts

### Authorization System
- **Role-Based Access Control (RBAC)**: Granular permission system
- **Vessel-Scoped Permissions**: Different roles per vessel
- **Permission Checks**: Middleware and policy-based authorization
- **Role Hierarchy**: Admin, Manager, Viewer roles with different capabilities

### Security Features
- **CSRF Protection**: Laravel's built-in CSRF token validation
- **SQL Injection Prevention**: Eloquent ORM with parameterized queries
- **XSS Protection**: Automatic output escaping in Vue templates
- **File Upload Validation**: Type and size validation for uploads
- **Secure File Access**: Authorization checks for file downloads
- **Password Hashing**: Bcrypt password hashing
- **Secure Tokens**: Hashids for public-facing IDs

---

## ⚡ Performance & Scalability

### Backend Optimizations
- **Query Optimization**: Eager loading, query scopes, database indexes
- **Caching Strategy**: Redis caching for frequently accessed data
- **Queue Processing**: Background job processing for heavy operations
- **Database Indexing**: Strategic indexes on foreign keys and frequently queried columns
- **Monthly Balance Tables**: Pre-calculated balances for fast reporting
- **Pagination**: Efficient pagination for large datasets

### Frontend Optimizations
- **Code Splitting**: Vite-based code splitting
- **Lazy Loading**: Route-based lazy loading
- **Asset Optimization**: Minified and compressed assets
- **SSR Support**: Server-side rendering for improved initial load
- **Image Optimization**: Optimized image handling

### Infrastructure
- **Docker Containerization**: Multi-stage builds for optimized images
- **Supervisor Process Management**: Automatic process restart
- **Nginx Configuration**: Optimized web server configuration
- **Queue Workers**: Dedicated queue processing
- **Scheduled Tasks**: Laravel scheduler for cron jobs

---

## 💻 Development Workflow

### Code Quality
- **Laravel Pint**: PHP code formatting (PHP CS Fixer)
- **ESLint**: JavaScript/TypeScript linting
- **Prettier**: Code formatting for frontend
- **TypeScript**: Full type safety on frontend
- **PHPStan**: Static analysis for PHP

### Git Workflow
- **Conventional Commits**: Structured commit messages
- **Branch Strategy**: Feature branches with clear naming
- **Commit Guidelines**: Maximum 5 files per commit for clarity

### Development Tools
- **Laravel Pail**: Real-time log viewing
- **Laravel Debugbar**: Development debugging
- **Vite HMR**: Hot module replacement for fast development
- **Concurrently**: Run multiple dev processes simultaneously

### Testing
- **Pest PHP**: Modern PHP testing framework
- **Feature Tests**: Complete workflow testing
- **Unit Tests**: Model and service testing
- **Money Calculation Tests**: Extensive financial calculation testing

---

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.3 or higher
- MySQL 8.0 or higher
- Node.js 20.x and npm
- Composer 2.x
- Redis (for queues and caching)

### Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/mariocosttaa/vessel-management-system.git
   cd vessel-management-system
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   Update `.env` with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=vessel_management
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   # Or for development:
   npm run dev
   ```

8. **Start development server**
   ```bash
   # Using Laravel's built-in server
   php artisan serve
   
   # Or using the dev script (includes queue, logs, vite)
   composer run dev
   ```

### Docker Setup

1. **Build and run containers**
   ```bash
   docker build -t vessel-management .
   docker run -p 80:80 vessel-management
   ```

The Docker setup includes:
- Nginx web server
- PHP-FPM
- Queue worker
- Laravel scheduler
- SSR server
- All managed by Supervisor

---

## 🐳 Deployment

### Production Requirements
- PHP 8.3+ with required extensions (pdo_mysql, mbstring, exif, pcntl, bcmath, gd, zip)
- MySQL 8.0+ or PostgreSQL 13+
- Redis for queues and caching
- Nginx or Apache web server
- SSL certificate (Let's Encrypt recommended)
- Node.js 20.x for building assets

### Deployment Steps

1. **Server Setup**
   - Install PHP, MySQL, Redis, Nginx
   - Configure PHP-FPM
   - Set up SSL certificate

2. **Application Deployment**
   ```bash
   git clone <repository>
   cd vessel-management-system
   composer install --optimize-autoloader --no-dev
   npm ci && npm run build
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Queue Workers**
   ```bash
   php artisan queue:work --daemon
   # Or use Supervisor (recommended)
   ```

4. **Scheduled Tasks**
   Add to crontab:
   ```bash
   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
   ```

5. **File Permissions**
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

### Docker Deployment
The included Dockerfile provides a production-ready container with:
- Multi-stage build for optimized image size
- Nginx configuration
- Supervisor for process management
- Queue workers and scheduler
- SSR server

---

## 🧪 Testing

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# With coverage
php artisan test --coverage
```

### Test Structure
- **Feature Tests**: Complete workflow testing (controllers, routes)
- **Unit Tests**: Model and service testing
- **Money Tests**: Financial calculation validation
- **Integration Tests**: End-to-end workflow testing

---

## 📚 Documentation

Comprehensive documentation is available in the `/docs` directory:

### Core Documentation
- **[Quick Reference](docs/quick-reference.md)** - Essential patterns and quick lookup
- **[Implementation Guide](docs/implementation-guide.md)** - Complete system roadmap
- **[Database Schema](docs/database-schema.md)** - Complete database structure

### Pattern Documentation
- **[Model Patterns](docs/patterns/model-patterns.md)** - Eloquent model conventions
- **[Controller Patterns](docs/patterns/controller-patterns.md)** - Controller structure
- **[Request Patterns](docs/patterns/request-patterns.md)** - Validation patterns
- **[Resource Patterns](docs/patterns/resource-patterns.md)** - API resource formatting
- **[Frontend Patterns](docs/patterns/frontend-patterns.md)** - Vue.js component patterns
- **[Money Handling](docs/patterns/money-handling.md)** - Financial system documentation

### Design Documentation
- **[Layout Patterns](docs/layout-patterns.md)** - UI/UX patterns
- **[Theme Configuration](docs/theme-configuration.md)** - Design system

---

## 🌟 Project Highlights

### Technical Excellence
✅ **Modern Tech Stack**: Latest versions of Laravel, Vue.js, TypeScript  
✅ **Type Safety**: Full TypeScript on frontend, type hints in PHP  
✅ **Server-Side Rendering**: Inertia.js SSR for improved performance  
✅ **Financial Precision**: Integer-based money handling (no floating-point errors)  
✅ **Complex Business Logic**: Mareas system with distribution calculations  
✅ **Enterprise Features**: RBAC, audit trails, multi-tenancy patterns  
✅ **Production Ready**: Docker, queue workers, scheduled tasks, monitoring  

### Code Quality
✅ **Comprehensive Documentation**: 10+ documentation files with examples  
✅ **Testing**: Feature and unit tests with Pest PHP  
✅ **Code Standards**: Laravel Pint, ESLint, Prettier  
✅ **Design Patterns**: Service layer, repository pattern, observers  
✅ **Clean Architecture**: Separation of concerns, SOLID principles  

### User Experience
✅ **Responsive Design**: Mobile-first with Tailwind CSS  
✅ **Dark Mode**: Complete dark mode support  
✅ **Internationalization**: Multi-language support  
✅ **Accessibility**: Semantic HTML, ARIA labels  
✅ **Performance**: Optimized queries, caching, code splitting  

### DevOps & Infrastructure
✅ **Docker Support**: Production-ready containerization  
✅ **CI/CD Ready**: Structured for continuous integration  
✅ **Process Management**: Supervisor for reliability  
✅ **Monitoring**: Logging and error tracking setup  

---

## 📊 Project Statistics

- **28+ Database Models**: Comprehensive data structure
- **30+ Controllers**: Full CRUD operations across modules
- **50+ Vue Components**: Reusable component library
- **100+ Routes**: Complete application routing
- **10+ Documentation Files**: Comprehensive developer documentation
- **Multi-language Support**: Internationalization ready
- **Production Deployed**: Live system in use

---

## 🎓 Skills Demonstrated

This project showcases expertise in:

### Backend Development
- Laravel framework (latest version)
- PHP 8.3 modern features
- Eloquent ORM and database design
- RESTful API design patterns
- Queue and job processing
- Scheduled tasks and cron jobs
- File storage and management
- Email notifications

### Frontend Development
- Vue.js 3 Composition API
- TypeScript for type safety
- Inertia.js SPA framework
- Component-based architecture
- State management (Pinia)
- Form handling and validation
- Data visualization (charts)
- Responsive design

### Full-Stack Integration
- Server-Side Rendering (SSR)
- Real-time data synchronization
- File upload and management
- Authentication and authorization
- Role-based access control
- Audit logging

### DevOps & Infrastructure
- Docker containerization
- Nginx configuration
- Process management (Supervisor)
- Queue workers
- Scheduled tasks
- Production deployment

### Software Engineering
- Design patterns (Service, Repository, Observer)
- SOLID principles
- Clean architecture
- Code quality tools
- Testing strategies
- Documentation practices

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📧 Contact & Support

For questions, support, or collaboration opportunities:
- Create an issue in the GitHub repository
- Check the documentation in `/docs`
- Review the implementation guide for development questions

---

**Built with ❤️ for modern vessel management operations**

*Showcasing enterprise-level full-stack development capabilities with modern technologies and best practices.*

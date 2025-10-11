# Vessel Management Financial System

A comprehensive financial management system designed specifically for vessel operations, built with Laravel 12, Vue.js 3, and Inertia.js.

## 🚢 Overview

This system provides complete financial control for vessel management operations, including:

- **Vessel Management**: Track multiple vessels with their specifications and status
- **Crew Management**: Manage crew members, positions, and salary payments
- **Financial Transactions**: Handle income, expenses, and transfers with automatic VAT calculations
- **Recurring Transactions**: Automate recurring payments like salaries, insurance, and maintenance
- **Bank Account Management**: Track multiple bank accounts with real-time balance calculations
- **Comprehensive Reporting**: Generate financial reports, cash flow statements, and VAT reports
- **Document Management**: Attach receipts, invoices, and other financial documents

## 🛠️ Technology Stack

- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: Vue.js 3 with Composition API
- **UI Framework**: shadcn-vue components
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Breeze with Inertia.js
- **File Storage**: Laravel's file system with support for local/cloud storage

## 📋 Key Features

### Core Financial Features
- ✅ Multi-currency support with proper money handling (stored as integers)
- ✅ Automatic VAT calculations with configurable rates
- ✅ Real-time balance tracking across multiple bank accounts
- ✅ Automatic transaction numbering
- ✅ Monthly balance calculations for performance optimization

### Vessel Operations
- ✅ Complete vessel registry with specifications
- ✅ Crew member management with position tracking
- ✅ Salary management with different payment frequencies
- ✅ Vessel-specific financial tracking

### Automation
- ✅ Recurring transaction generation (daily, weekly, monthly, etc.)
- ✅ Automatic VAT calculations
- ✅ Scheduled balance updates
- ✅ Email notifications for due payments

### Reporting & Analytics
- ✅ Dashboard with key financial metrics
- ✅ Income statement reports
- ✅ Cash flow analysis
- ✅ VAT reporting for tax compliance
- ✅ Category-based expense analysis
- ✅ Vessel-specific profitability reports
- ✅ PDF and Excel export capabilities

### User Management
- ✅ Role-based access control (Admin, Manager, Viewer)
- ✅ User activity logging and audit trails
- ✅ Secure file uploads with validation

## 🚀 Quick Start

### Prerequisites
- PHP 8.2 or higher
- MySQL 8.0 or higher
- Node.js 18+ and npm
- Composer

### Installation

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

5. **Database configuration**
   Update your `.env` file with database credentials:
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
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

Visit `http://localhost:8000` to access the application.

## 📚 Documentation

Comprehensive documentation is available in the `/docs` directory:

- **[Controller Patterns](docs/patterns/controller-patterns.md)** - Backend controller structure and conventions
- **[Request Patterns](docs/patterns/request-patterns.md)** - Form request validation and normalization
- **[Model Patterns](docs/patterns/model-patterns.md)** - Eloquent model relationships and patterns
- **[Resource Patterns](docs/patterns/resource-patterns.md)** - API resource formatting and transformation
- **[Frontend Patterns](docs/patterns/frontend-patterns.md)** - Vue.js component structure and patterns
- **[Money Handling](docs/patterns/money-handling.md)** - Complete money system documentation
- **[Database Schema](docs/database-schema.md)** - Complete database structure reference
- **[Implementation Guide](docs/implementation-guide.md)** - Phase-by-phase development roadmap

## 🏗️ Architecture

### Backend Architecture
- **Controllers**: Handle HTTP requests and return Inertia responses
- **Models**: Eloquent models with relationships and business logic
- **Services**: Business logic services for complex operations
- **Requests**: Form request classes for validation and normalization
- **Resources**: API resources for data transformation
- **Jobs**: Background jobs for recurring transactions and calculations

### Frontend Architecture
- **Pages**: Inertia.js pages for different routes
- **Components**: Reusable Vue.js components
- **Composables**: Vue.js composables for shared logic
- **Types**: TypeScript interfaces for type safety
- **Utils**: Utility functions for common operations

### Database Design
- **Normalized Structure**: Properly normalized database with foreign key relationships
- **Money Storage**: All monetary values stored as integers (cents) for precision
- **Audit Trail**: Complete activity logging for all user actions
- **Performance Optimization**: Monthly balance tables for fast reporting

## 💰 Money Handling System

The system uses a robust money handling approach:

- **Storage**: All amounts stored as integers (e.g., €123.45 stored as 12345)
- **Currency**: 3-letter ISO currency codes (EUR, USD, etc.)
- **Precision**: Configurable decimal places per currency
- **Calculations**: All calculations performed on integers to avoid floating-point errors
- **Display**: Automatic formatting for user interface

## 🔐 Security Features

- **Authentication**: Laravel Breeze with secure password hashing
- **Authorization**: Role-based access control with granular permissions
- **CSRF Protection**: Built-in CSRF protection for all forms
- **File Upload Security**: Validated file uploads with type checking
- **SQL Injection Prevention**: Eloquent ORM with parameterized queries
- **XSS Protection**: Automatic output escaping in Vue.js templates

## 📊 Reporting System

### Available Reports
- **Income Statement**: Revenue vs expenses analysis
- **Cash Flow**: Money in vs money out tracking
- **VAT Report**: Tax calculations and reporting
- **Category Analysis**: Expense breakdown by category
- **Vessel Profitability**: Per-vessel financial performance
- **Monthly Summaries**: Month-over-month comparisons

### Export Options
- **PDF Export**: Professional PDF reports using DomPDF
- **Excel Export**: Spreadsheet exports using Laravel Excel
- **CSV Export**: Data exports for external analysis

## 🔄 Automation Features

### Recurring Transactions
- **Flexible Scheduling**: Daily, weekly, monthly, quarterly, semi-annual, annual
- **Automatic Generation**: Background jobs create transactions automatically
- **Manual Override**: Ability to generate transactions manually
- **End Dates**: Support for recurring transactions with end dates

### Background Jobs
- **Transaction Generation**: Daily job to create recurring transactions
- **Balance Updates**: Automatic balance recalculation
- **Notification Sending**: Email notifications for due payments
- **Report Generation**: Scheduled report creation

## 🧪 Testing

The system includes comprehensive testing:

- **Unit Tests**: Model and service testing
- **Feature Tests**: Controller and API endpoint testing
- **Integration Tests**: Complete workflow testing
- **Money Calculation Tests**: Extensive testing of financial calculations

Run tests with:
```bash
php artisan test
```

## 🚀 Deployment

### Production Requirements
- PHP 8.2+ with required extensions
- MySQL 8.0+ or PostgreSQL 13+
- Redis (for queues and caching)
- Web server (Nginx/Apache)
- SSL certificate

### Deployment Steps
1. Set up production environment
2. Configure environment variables
3. Run migrations and seeders
4. Build frontend assets
5. Set up queue workers
6. Configure scheduled tasks
7. Set up monitoring and logging

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue in the GitHub repository
- Check the documentation in `/docs`
- Review the implementation guide for development questions

## 🔮 Roadmap

### Planned Features
- **Mobile Application**: React Native app for field operations
- **Banking Integration**: Direct bank account integration
- **Advanced Analytics**: Machine learning for financial insights
- **Multi-tenant Support**: Support for multiple companies
- **API Development**: RESTful API for third-party integrations

### Version History
- **v1.0.0**: Initial release with core financial features
- **v1.1.0**: Enhanced reporting and automation
- **v1.2.0**: Mobile app and advanced features (planned)

---

Built with ❤️ for vessel management operations.
# Vessel Management System

A Laravel-based vessel management system built with Inertia.js and Vue.js.

## Features

- User authentication and authorization
- Dashboard interface
- Settings management
- Modern UI with Vue.js components

## Tech Stack

- **Backend**: Laravel 11
- **Frontend**: Vue.js 3 with Inertia.js
- **Database**: SQLite (development)
- **Testing**: Pest PHP

## Installation

1. Clone the repository
2. Install PHP dependencies: `composer install`
3. Install Node.js dependencies: `npm install`
4. Copy environment file: `cp .env.example .env`
5. Generate application key: `php artisan key:generate`
6. Run migrations: `php artisan migrate`
7. Build assets: `npm run build`

## Development

- Start the development server: `php artisan serve`
- Watch for asset changes: `npm run dev`

## Testing

Run tests with Pest: `php artisan test`

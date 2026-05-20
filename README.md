# Lead Generation & Outreach SaaS Platform

A comprehensive SaaS platform for lead generation, hyper-personalized email outreach, and LinkedIn messaging support. Built with Laravel.

## Core Modules

### 1. Lead Generation Engine
- Search & extract leads by geographic filters (country, city) and professional filters (job title, industry)
- Personalized dashboard with metrics (total leads, session leads, remaining quota)
- Lead management with dynamic categorization by search query

### 2. Hyper-Personalized Email Outreach
- Secure email integration via Maton API
- Send immediately or save as drafts with bulk action support
- Email open tracking via tracking pixels
- Unsubscribe functionality for compliance

### 3. LinkedIn Outreach Support
- Draft management for personalized LinkedIn messages
- One-click copy for seamless pasting into LinkedIn

## Advanced Features
- CRM integration for lead synchronization
- Open rate and send tracking analytics
- Granular access controls and quota management

## Tech Stack
- **Backend:** Laravel (PHP)
- **Frontend:** Tailwind CSS, Vite
- **Database:** MySQL
- **Email:** Maton API integration

## Getting Started

```bash
# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Start development server
php artisan serve
npm run dev
```

## License

MIT License

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Rate limiting for API endpoints (100 requests/minute)
- Swagger UI for interactive API documentation
- GitHub Actions CI/CD pipeline
- Docker Compose with PHP-FPM, Nginx, MySQL, Redis
- Export comptable CSV/Excel
- Multi-language support (FR/EN)
- Automatic invoice reminders
- Dashboard trend charts

### Changed
- Improved README with architecture diagrams
- Enhanced Docker configuration with profiles

## [1.0.0] - 2024-01-15

### Added
- Complete billing system for freelancers and small businesses
- User authentication with secure sessions
- Password reset with expiring tokens
- Client management (B2B) with company details
  - SIRET and VAT number support
  - Contact information
  - Invoice history and statistics
- Quote management
  - Auto-generated numbering (DEV-YYYY-NNNN)
  - Status tracking: draft, sent, accepted, declined, expired
  - One-click conversion to invoice
  - PDF export with company branding
- Invoice management
  - Auto-generated numbering (FAC-YYYY-NNNN)
  - Multiple VAT rate support
  - Status tracking: draft, pending, paid, overdue, cancelled
  - Automatic overdue detection
  - PDF export with legal mentions
- Stripe payment integration
  - Checkout sessions
  - Webhook handling
  - Refund support
  - SEPA and card payments
- REST API (v1)
  - Bearer token authentication
  - Complete CRUD for clients, invoices, quotes
  - Statistics endpoints
  - Pagination and filtering
- Dashboard with analytics
  - Monthly/yearly revenue charts
  - KPIs: invoice counts, conversion rates
  - Quick actions
- Settings management
  - Company branding
  - Bank details (IBAN, BIC)
  - Default VAT rate
  - Payment terms
- Email notifications via PHPMailer
- PHPUnit test suite

### Security
- SQL injection prevention (100% prepared statements)
- XSS protection (htmlspecialchars escaping)
- CSRF tokens on all forms
- Secure password hashing (bcrypt cost 12)
- API token hashing (SHA-256)
- Security headers (X-Frame-Options, X-Content-Type-Options, etc.)
- Multi-tenant data isolation

## [0.1.0] - 2024-01-01

### Added
- Initial project structure
- MVC architecture setup
- Database schema design
- Basic routing system

---

## Version History Summary

| Version | Date | Highlights |
|---------|------|------------|
| 1.0.0 | 2024-01-15 | Full billing system, Stripe, API |
| 0.1.0 | 2024-01-01 | Initial project setup |

## Upgrade Guide

### From 0.x to 1.0.0

1. Run database migrations:
   ```bash
   mysql -u root -p invoiceflow < database/migrations/002_api_tokens.sql
   mysql -u root -p invoiceflow < database/migrations/003_stripe_payments.sql
   ```

2. Update environment variables:
   ```env
   STRIPE_PUBLISHABLE_KEY=pk_...
   STRIPE_SECRET_KEY=sk_...
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

3. Configure Stripe webhook endpoint:
   - URL: `https://yourdomain.com/webhook/stripe`
   - Events: `checkout.session.completed`, `payment_intent.succeeded`, `payment_intent.payment_failed`

## Contributing

When contributing, please update this changelog with your changes under the `[Unreleased]` section.

### Change Types

- `Added` for new features
- `Changed` for changes in existing functionality
- `Deprecated` for soon-to-be removed features
- `Removed` for now removed features
- `Fixed` for any bug fixes
- `Security` for vulnerability fixes

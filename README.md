# Daya - Digital Distribution Service

[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19-blue.svg)](https://reactjs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.0-blue.svg)](https://www.typescriptlang.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.0-38B2AC.svg)](https://tailwindcss.com)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-2.0-9553E9.svg)](https://inertiajs.com)

A cloud-native platform connecting Clients, Digital Content Distributors (DCDs), and Digital Ambassadors (DAs) under a community-led distribution model. Daya enables targeted digital content distribution through unique QR codes and integrates with the Digital Wallet Service (DWS) for seamless payouts.

## 📋 Table of Contents

- [Overview](#overview)
- [Core Features](#core-features)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [User Roles](#user-roles)
- [Installation & Setup](#installation--setup)
- [API Documentation](#api-documentation)
- [Database Schema](#database-schema)
- [Email System](#email-system)
- [Security Features](#security-features)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Overview

Daya is a comprehensive digital distribution platform that creates a sustainable growth loop where:

- **Clients** pay for campaigns with upfront budget distribution
- **Digital Ambassadors (DAs)** recruit and onboard new DCDs, earning 10% commissions
- **Digital Content Distributors (DCDs)** distribute QR codes to earn 60% of campaign budgets
- **Platform (DAYA)** receives 30% for operations and infrastructure
- **Venture Shares** provide long-term incentive alignment for early participants

### Core Business Model

```
Client → Pays for Campaign → DCD Scans QR → DA Earns Commission → More Recruitment
```

## 🚀 Core Features

### 🎯 Campaign Management
- **Multi-tier Pricing**: Light-touch (KSh 1), Moderate-touch (KSh 5), High-touch (KSh 10)
- **Budget Tracking**: Real-time spending monitoring with auto-completion
- **Cost Per Click**: Precise CPC management with decimal precision
- **Duplicate Prevention**: Device fingerprinting prevents multiple scans
- **Geographic Targeting**: County-level campaign targeting
- **Status Workflow**: Draft → Submitted → Under Review → Approved → Paid → Live → Completed

### 👥 User Management
- **Three User Types**: Clients, DAs, and DCDs
- **Referral System**: Multi-level referral tracking with venture share rewards
- **Wallet Integration**: DWS wallet creation and management
- **KYC Ready**: National ID and phone verification structure
- **Role-based Access**: Secure authentication with Laravel Fortify

### 💰 Earnings & Rewards
- **Upfront Distribution**: 60% DCD, 10% DA/Admin, 30% Platform upon campaign approval
- **Campaign Credit**: Budget deducted per scan, tracked in real-time
- **DA Client Referral**: 10% commission for referring clients
- **Venture Shares**: Token-based rewards for network growth
- **Real-time Tracking**: Live earnings dashboard
- **Monthly Payouts**: Automated payout processing
- **Performance Analytics**: Scan tracking and conversion metrics

### 📊 Admin Operations
- **Email-centric Operations**: No admin dashboard - all actions via secure email links
- **Daily Digest**: Executive summary emails with platform metrics
- **Real-time Alerts**: Instant notifications for critical events
- **Campaign Approval**: Secure email-based workflow
- **Automated Reporting**: Scheduled summaries and analytics

### 🔒 Security & Fraud Prevention
- **Cloudflare Turnstile**: Bot protection on all forms
- **Device Fingerprinting**: Duplicate scan prevention
- **Rate Limiting**: API protection against abuse
- **Secure Actions**: State-aware email links with expiration
- **Two-Factor Authentication**: Optional 2FA support

## 🏗️ Architecture

### System Architecture
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   React SPA     │    │   Laravel API   │    │   Database      │
│   (Frontend)    │◄──►│   (Backend)     │◄──►│   (MySQL)       │
│                 │    │                 │    │                 │
│ • Inertia.js    │    │ • REST API      │    │ • Users         │
│ • TypeScript    │    │ • Email System  │    │ • Campaigns     │
│ • Tailwind CSS  │    │ • Queue Jobs    │    │ • Scans         │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │                        │
                              ▼                        ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Email Service │    │   File Storage  │    │   External APIs │
│   (SMTP/Mailgun)│    │   (Local/S3)    │    │   (DWS Wallet)  │
│                 │    │                 │    │                 │
│ • Templates     │    │ • QR Codes      │    │ • Webhooks      │
│ • Automation    │    │ • PDFs          │    │ • Integration   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Service Layer Architecture
```
App\Services\
├── AdminDigestService.php      # Daily admin reporting
├── CampaignMatchingService.php # Campaign-DCD matching logic
├── QRCodeService.php          # QR code generation & management
├── ScanRewardService.php      # Earnings calculation
├── TurnstileService.php       # Bot protection
└── VentureShareService.php    # Token reward system
```

## 🛠️ Tech Stack

### Backend
- **Laravel 12**: PHP web framework with modern features
- **MySQL 8.0**: Primary database with JSON column support
- **Redis**: Caching and session storage
- **Queue System**: Laravel queues for background processing
- **Mail System**: SMTP with Blade templates

### Frontend
- **React 19**: Modern JavaScript library
- **TypeScript 5.0**: Type-safe JavaScript
- **Inertia.js 2.0**: SPA without API complexity
- **Tailwind CSS 4.0**: Utility-first CSS framework
- **Radix UI**: Accessible component primitives
- **Vite**: Fast build tool and dev server

### DevOps & Tools
- **Composer**: PHP dependency management
- **NPM**: Node.js package management
- **Pest**: PHP testing framework
- **ESLint + Prettier**: Code quality and formatting
- **Laravel Pint**: PHP code style fixer
- **Laravel Wayfinder**: Enhanced Inertia.js integration
- **Vite**: Fast build tool and dev server
- **Tailwind CSS v4**: Next-generation utility-first CSS

### Security & Performance
- **Cloudflare Turnstile**: Bot protection
- **Laravel Fortify**: Authentication system
- **Rate Limiting**: API protection
- **Device Fingerprinting**: Fraud prevention
- **Database Indexing**: Query optimization
- **Redis Caching**: Performance optimization

### Security & Performance
- **Cloudflare Turnstile**: Bot protection
- **Laravel Fortify**: Authentication system
- **Rate Limiting**: API protection
- **Device Fingerprinting**: Fraud prevention
- **Database Indexing**: Query optimization

## 👥 User Roles

### 1. Client (Advertiser)
**Purpose**: Brands and businesses running digital campaigns
**Key Actions**:
- Submit campaigns with budget and targeting
- Receive performance reports
- Pay invoices and track ROI
- Manage campaign lifecycle

**Journey**:
1. Register via web form
2. Submit campaign → Receive confirmation
3. Campaign approved → Receive invoice
4. Pay invoice → Campaign goes live
5. Receive daily/weekly performance reports

### 2. Digital Ambassador (DA)
**Purpose**: Network builders who recruit DCDs
**Key Actions**:
- Share referral codes
- Track recruited DCDs
- Earn 10% commissions on DCD campaign assignments
- Earn 10% commissions on referred client campaigns
- Receive venture share rewards
- Access recruitment materials

**Rewards**:
- 10% upfront commission when referred DCD gets campaign (or if DA refers client)
- Venture shares: 500 KeDDS/KeDWS per DA referral (max 10)
- 250 KeDDS/KeDWS per DCD referral (max 100)
- Milestone bonuses for volume achievements

### 3. Digital Content Distributor (DCD)
**Purpose**: Field agents who distribute QR codes
**Key Actions**:
- Receive unique QR codes
- Distribute codes to generate scans
- Track earnings and performance
- Access wallet for payouts

**Rewards**:
- Earn per successful scan (campaign-dependent rate)
- Venture shares for early adopters
- Direct payouts to DWS wallet

### 4. Admin (Operations Team)
**Purpose**: Platform management and oversight
**Key Actions**:
- Approve/reject campaigns via email
- Monitor platform metrics
- Process payouts
- Handle support issues
- Receive automated alerts

**Operations**:
- Email-centric: All actions via secure links
- Daily digest: Executive summary at 8 AM EAT
- Real-time alerts: Critical event notifications
- Monthly reporting: Payout summaries

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.2+
- Node.js 18+
- MySQL 8.0+
- Composer
- NPM

### 1. Clone Repository
```bash
git clone https://github.com/mwasobaddy/Daya.git
cd daya
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install Node Dependencies
```bash
npm install
```

### 4. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

**Configure .env**:
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=daya
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=mail.daya.africa
MAIL_PORT=465
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@daya.africa
MAIL_FROM_NAME="Daya Distribution"

# Cloudflare Turnstile
TURNSTILE_SITE_KEY=your_site_key
TURNSTILE_SECRET_KEY=your_secret_key
VITE_TURNSTILE_SITE_KEY="${TURNSTILE_SITE_KEY}"

# DWS Wallet Integration (Future)
DWS_API_URL=https://api.dws.africa
DWS_API_KEY=your_api_key
```

### 5. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 6. Build Assets
```bash
npm run build
```

### 7. Start Development Server
```bash
php artisan serve
```

### 8. Configure Scheduler (Production)
Add to crontab:
```bash
* * * * * cd /path-to-daya && php artisan schedule:run >> /dev/null 2>&1
```

### 9. Available Artisan Commands
```bash
# Daily admin digest (runs automatically at 8 AM EAT)
php artisan digest:send-admin-daily
php artisan digest:send-admin-daily --email=user@example.com
php artisan digest:send-admin-daily --date=2026-01-03

# Process earnings (mark old pending earnings as paid)
php artisan daya:process-earnings --days=7 --mark-paid

# Standard Laravel commands
php artisan migrate
php artisan queue:work
php artisan test
```

### 10. Setup & Utilities
- **Setup Script**: `setup.php` - Web-based setup for cPanel environments
- **Contrast Checker**: `scripts/contrast-check.js` - WCAG accessibility validation
- **Demo Graphics**: `demo-graphics.js` - Data visualization utilities
- **Debug Scripts**: Various debugging utilities in `scripts/` directory

## 📡 API Documentation

### Authentication
All API endpoints use Laravel Sanctum for authentication.

### Core Endpoints

#### User Registration
```http
POST /api/da/create
POST /api/dcd/create
POST /api/client/create
```

#### Campaign Management
```http
POST /api/client/campaign/submit
GET  /api/client/campaigns
POST /api/admin/campaign/approve/{id}
POST /api/admin/campaign/reject/{id}
POST /api/admin/campaign/mark-paid/{id}
```

#### QR Code & Scanning
```http
POST /api/qr/generate-dcd
POST /api/scan/track
GET  /api/scan/validate
```

#### Referral System
```http
POST /api/referral/validate
GET  /api/referral/my-code
GET  /api/referral/tree/{daId}
```

#### Earnings & Venture Shares
```http
GET  /api/earnings/dashboard
POST /api/ventureshares/allocate
GET  /api/ventureshares/balance/{userId}
```

#### Admin Operations
```http
GET  /api/admin/digest
POST /api/admin/action/authenticate
GET  /api/admin/test-digest
POST /api/admin/send-test-digest
```

### Geographic Data
```http
GET /api/countries
GET /api/counties
GET /api/subcounties
GET /api/wards
```

## 🗄️ Database Schema

### Core Tables

#### users
```sql
- id (bigint, PK)
- name (varchar)
- email (varchar, unique)
- password (varchar)
- role (enum: client, da, dcd, admin)
- referral_code (varchar, unique)
- qr_code (varchar)
- wallet_status (enum)
- phone (varchar)
- national_id (varchar)
- country_id (bigint, FK)
- county_id (bigint, FK)
- business_name (varchar)
- account_type (varchar)
- created_at (timestamp)
- updated_at (timestamp)
```

#### campaigns
```sql
- id (bigint, PK)
- client_id (bigint, FK → users)
- dcd_id (bigint, FK → users, nullable)
- title (varchar)
- budget (decimal 10,4)
- cost_per_click (decimal 10,4)
- spent_amount (decimal 10,4)
- max_scans (int)
- total_scans (int)
- county (varchar)
- status (enum)
- campaign_objective (varchar)
- target_audience (text)
- duration (varchar)
- objectives (text)
- digital_product_link (varchar)
- explainer_video_url (varchar)
- metadata (json)
- completed_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

#### scans
```sql
- id (bigint, PK)
- campaign_id (bigint, FK → campaigns)
- dcd_id (bigint, FK → users)
- device_fingerprint (varchar)
- ip_address (varchar)
- user_agent (varchar)
- location_data (json)
- scan_timestamp (timestamp)
- created_at (timestamp)
```

#### referrals
```sql
- id (bigint, PK)
- referrer_id (bigint, FK → users)
- referred_id (bigint, FK → users)
- referral_type (enum: da_to_da, da_to_dcd, da_to_client)
- status (enum: pending, active, inactive)
- created_at (timestamp)
```

#### earnings
```sql
- id (bigint, PK)
- user_id (bigint, FK → users)
- campaign_id (bigint, FK → campaigns)
- scan_id (bigint, FK → scans)
- amount (decimal 10,4)
- commission_amount (decimal 10,4, nullable)
- type (enum: scan_earning, commission, venture_share)
- status (enum: pending, paid, cancelled)
- description (text)
- created_at (timestamp)
```

#### venture_shares
```sql
- id (bigint, PK)
- user_id (bigint, FK → users)
- amount (decimal 10,4)
- type (enum: da_referral, dcd_referral, early_adopter, milestone)
- source_user_id (bigint, FK → users, nullable)
- description (text)
- allocated_at (timestamp)
- created_at (timestamp)
```

### Geographic Tables
- **countries**: id, name, code
- **counties**: id, name, country_id
- **subcounties**: id, name, county_id
- **wards**: id, name, subcounty_id

## 📧 Email System

### Email Templates
Located in `app/Mail/` and `resources/views/emails/`

#### User Onboarding
- `DaWelcome.php` - DA registration confirmation
- `DcdWelcome.php` - DCD registration with QR code
- `WalletCreated.php` - Wallet activation link

#### Campaign Workflow
- `CampaignConfirmation.php` - Campaign submitted
- `CampaignApproved.php` - Campaign approved with invoice
- `CampaignRejected.php` - Campaign rejected with reason
- `CampaignCompleted.php` - Campaign finished
- `PaymentCompleted.php` - Payment processed

#### Earnings & Rewards
- `DaReferralCommissionNotification.php` - Commission earned
- `DcdReferralBonusNotification.php` - Referral bonus
- `DcdTokenAllocationNotification.php` - Venture shares allocated

#### Admin Operations
- `AdminCampaignPending.php` - New campaign needs approval
- `AdminDaRegistration.php` - New DA registered
- `AdminDcdRegistration.php` - New DCD registered
- `AdminPaymentPending.php` - Payment awaiting processing
- `DailyAdminDigest.php` - Executive summary (8 AM daily)

### Email Features
- **Blade Templates**: Responsive HTML with Tailwind CSS
- **Dynamic Content**: Personalized data injection
- **Secure Actions**: State-aware links with expiration
- **SMTP Configuration**: Professional email delivery
- **Queue Support**: Background processing for performance

## 🔒 Security Features

### Authentication & Authorization
- **Laravel Fortify**: Secure authentication system
- **Two-Factor Authentication**: Optional 2FA support
- **Role-based Access**: Granular permissions
- **Session Management**: Secure session handling

### Fraud Prevention
- **Cloudflare Turnstile**: Bot protection on all forms
- **Device Fingerprinting**: Unique device identification
- **Rate Limiting**: API abuse prevention
- **Duplicate Detection**: Scan validation logic

### Data Protection
- **Password Hashing**: Bcrypt with salt
- **Data Encryption**: Sensitive data encryption
- **CSRF Protection**: Cross-site request forgery prevention
- **XSS Prevention**: Input sanitization

### Secure Operations
- **Email Action Links**: Time-limited secure URLs
- **State Validation**: Action verification
- **Audit Logging**: Admin action tracking
- **Input Validation**: Comprehensive validation rules

## 🚀 Deployment

### Production Requirements
- **Web Server**: Nginx or Apache
- **PHP**: 8.2+ with required extensions
- **Database**: MySQL 8.0+
- **SSL Certificate**: HTTPS required
- **SMTP Service**: Mailgun, SendGrid, or SMTP provider

### Environment Setup
```bash
# Production .env configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://daya.africa

# Database
DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_DATABASE=daya_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Redis (recommended for production)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=database  # or redis
```

### Deployment Steps
1. **Code Deployment**
   ```bash
   git pull origin main
   composer install --optimize-autoloader --no-dev
   npm run build
   php artisan migrate --force
   ```

2. **Cache Optimization**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Queue Setup**
   ```bash
   php artisan queue:table
   php artisan migrate
   ```

4. **Scheduler Setup**
   ```bash
   # Add to crontab
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

5. **Queue Worker**
   ```bash
   # Start queue worker
   php artisan queue:work --sleep=3 --tries=3
   ```

### Monitoring & Maintenance
- **Logs**: Monitor `storage/logs/laravel.log`
- **Queue Monitoring**: Check failed jobs table
- **Performance**: Use Laravel Telescope for debugging
- **Backups**: Regular database backups
- **Updates**: Keep dependencies updated

## 🤝 Contributing

### Development Workflow
1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/your-feature`
3. **Make** your changes with tests
4. **Run** tests: `php artisan test`
5. **Format** code: `npm run format && ./vendor/bin/pint`
6. **Commit** changes: `git commit -m 'Add your feature'`
7. **Push** to branch: `git push origin feature/your-feature`
8. **Create** Pull Request

### Code Standards
- **PHP**: PSR-12 standards with Laravel Pint
- **JavaScript/TypeScript**: ESLint + Prettier
- **CSS**: Tailwind CSS conventions
- **Testing**: Pest PHP testing framework
- **Documentation**: Comprehensive inline comments

### Testing
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=TestName

# Run with coverage
php artisan test --coverage
```

### Code Quality
```bash
# PHP formatting
./vendor/bin/pint

# JavaScript formatting
npm run format

# Linting
npm run lint
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📚 Additional Documentation

### Implementation Guides
- **[PRD.md](PRD.md)** - Complete Product Requirements Document
- **[DAILY_DIGEST_DOCUMENTATION.md](DAILY_DIGEST_DOCUMENTATION.md)** - Admin digest system guide
- **[BUDGET_TRACKING_IMPLEMENTATION.md](BUDGET_TRACKING_IMPLEMENTATION.md)** - Budget tracking system
- **[TURNSTILE_IMPLEMENTATION.md](TURNSTILE_IMPLEMENTATION.md)** - Bot protection setup
- **[Earning.md](Earning.md)** - Earnings and pricing structure
- **[ventures.md](ventures.md)** - Venture shares system

### Key Files Reference
- **Setup Script**: `setup.php` - Web-based deployment helper
- **Contrast Checker**: `scripts/contrast-check.js` - Accessibility validation
- **Demo Graphics**: `demo-graphics.js` - Data visualization utilities
- **Debug Scripts**: `scripts/debug_*.php` - Development utilities

### Recent Implementations
- ✅ **Daily Admin Digest**: Automated executive summaries (Jan 2026)
- ✅ **Budget Tracking**: Real-time campaign spending with auto-completion
- ✅ **Cloudflare Turnstile**: Bot protection across all forms
- ✅ **Enhanced Security**: Device fingerprinting and fraud prevention
- ✅ **Email Automation**: Comprehensive notification system
- ✅ **Venture Shares**: Token-based reward system for network growth
- ✅ **Enhanced Campaign Approval Workflow**: Automated status transitions with cron jobs and notifications (Feb 2026)
- ✅ **Scan Fingerprinting & Processing**: Device-based duplicate prevention and async processing (Feb 2026)
- ✅ **System Cleanup**: Removed obsolete commands and updated configurations (Feb 2026)

## 🗺️ Roadmap & Future Enhancements

### v2.0 Planned Features
- **Administrative Dashboard**: Web-based admin interface
- **Automated Payouts**: Direct DWS wallet integration
- **Advanced Fraud Detection**: AI-powered pattern analysis
- **Mobile Applications**: Native iOS and Android apps
- **Payment Gateway**: Direct payment processing
- **KYC Verification**: Document upload and validation
- **Venture Share Trading**: DMS integration for token exchange
- **Advanced Analytics**: Comprehensive reporting system
- **Multi-level Referrals**: Enhanced network tracking

### MVP v1.0 Status
- ✅ **Core User Journeys**: Client → DA → DCD → Scan → Earn
- ✅ **Venture Share System**: DA-to-DA, DA-to-DCD, Early Adopter rewards
- ✅ **Email-centric Operations**: No admin dashboard required
- ✅ **Security Measures**: Turnstile, fingerprinting, rate limiting
- ✅ **Automated Reporting**: Daily digest and real-time alerts

## 📞 Support

For support and questions:
- **Email**: support@daya.africa
- **Documentation**: [PRD.md](PRD.md), [DAILY_DIGEST_DOCUMENTATION.md](DAILY_DIGEST_DOCUMENTATION.md)
- **Issues**: GitHub Issues

## 🙏 Acknowledgments

- **Laravel Team** for the amazing framework
- **React Team** for the powerful frontend library
- **Tailwind CSS** for the utility-first approach
- **Cloudflare** for Turnstile bot protection
- **Inertia.js** for seamless SPA integration

---

**Built with ❤️ for the Kenyan digital economy**</content>
<parameter name="filePath">/Users/app/Desktop/Laravel/Daya/README.md
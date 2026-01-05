# Daily Admin Digest Email System

## Overview
Automated daily email digest system that sends comprehensive platform statistics to admin users every morning at 8:00 AM EAT.

## Features

### Executive Summary Style Email
- **Daily Snapshot**: Key metrics at a glance with comparison to 7-day average
- **Highlights**: Top achievements and completed campaigns
- **Attention Needed**: Alerts and action items requiring admin review
- **Top Performers**: Best performing DCDs, campaigns, and referrers
- **New Campaigns**: List of all campaigns submitted yesterday
- **Financial Summary**: Revenue, earnings, and budget utilization
- **Quick Actions**: Direct links to approve campaigns and view dashboard

### Metrics Included

#### Campaign Metrics
- New campaigns submitted (count + total budget)
- Campaigns approved/rejected
- Campaigns completed (auto-completed when budget reached)
- Active campaigns count
- Pending approvals count
- Campaigns nearing completion (>80% budget spent)

#### Scan & Earnings Metrics
- Total scans for the day
- Successful vs failed scans
- Duplicate scans prevented
- Total earnings generated
- Average scans per campaign

#### User Activity
- New DAs, DCDs, and Clients registered
- New referrals created
- Total active users by role

#### Financial Metrics
- Revenue today (new campaign budgets)
- Pending earnings (unpaid DCD earnings)
- Paid earnings
- Budget utilization rate across all active campaigns

#### Top Performers
- Top DCD by scans and earnings
- Top campaign by scans
- Top DA referrer by new referrals

#### Alerts
- Budget warnings (campaigns at 90%+ budget)
- Pending campaign approvals
- Inactive DCDs (no scans in 3+ days)

## Implementation

### Components Created

1. **AdminDigestService** (`app/Services/AdminDigestService.php`)
   - Collects all metrics and data
   - Generates comparison statistics
   - Identifies top performers and alerts

2. **DailyAdminDigest Mailable** (`app/Mail/DailyAdminDigest.php`)
   - Email structure with dynamic subject line
   - Passes digest data to Blade template

3. **Email Template** (`resources/views/emails/daily_admin_digest.blade.php`)
   - Beautiful HTML email with responsive design
   - Color-coded sections for quick scanning
   - Mobile-friendly layout

4. **Artisan Command** (`app/Console/Commands/SendDailyAdminDigest.php`)
   - `php artisan digest:send-admin-daily`
   - Options: `--date` (specify date) and `--email` (test with specific email)

5. **Scheduled Task** (`routes/console.php`)
   - Runs daily at 8:00 AM Africa/Nairobi timezone
   - Logs success/failure

## Usage

### Manual Testing

#### 1. Test Digest Data Generation (via API)
```bash
curl http://localhost:8000/api/admin/test-digest
```

#### 2. Send Test Email via Command
```bash
php artisan digest:send-admin-daily --email=your@email.com
```

#### 3. Send Test Email via API
```bash
curl -X POST http://localhost:8000/api/admin/send-test-digest \
  -H "Content-Type: application/json" \
  -d '{"email":"your@email.com"}'
```

#### 4. Generate Digest for Specific Date
```bash
php artisan digest:send-admin-daily --date=2026-01-03
```

### Production Deployment

#### 1. Configure Mail Settings
Update `.env` with your mail server settings:
```env
MAIL_MAILER=smtp
MAIL_HOST=your-mail-server.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@daya.africa
MAIL_FROM_NAME="Daya Platform"
```

#### 2. Set Up Cron Job
Add to your server's crontab:
```cron
* * * * * cd /path/to/daya && php artisan schedule:run >> /dev/null 2>&1
```

This runs Laravel's scheduler every minute, which will execute the daily digest at 8:00 AM.

#### 3. Test the Scheduler
```bash
php artisan schedule:list
```

Should show:
```
0 8 * * * Africa/Nairobi ......... digest:send-admin-daily
```

#### 4. Run Scheduler Manually (for testing)
```bash
php artisan schedule:run
```

## Email Recipients

By default, the digest is sent to all users with `role = 'admin'`.

To add admin users:
```sql
INSERT INTO users (name, email, role, password, email_verified_at, created_at, updated_at)
VALUES ('Admin Name', 'admin@daya.africa', 'admin', '$2y$12$...', NOW(), NOW(), NOW());
```

Or via tinker:
```bash
php artisan tinker
>>> $admin = new App\Models\User();
>>> $admin->name = 'Admin Name';
>>> $admin->email = 'admin@daya.africa';
>>> $admin->role = 'admin';
>>> $admin->password = Hash::make('secure-password');
>>> $admin->email_verified_at = now();
>>> $admin->save();
```

## Customization

### Change Email Time
Edit `routes/console.php`:
```php
Schedule::command('digest:send-admin-daily')
    ->dailyAt('09:00')  // Change to 9:00 AM
    ->timezone('Africa/Nairobi');
```

### Add More Recipients
Modify `SendDailyAdminDigest::getRecipients()` to include additional emails:
```php
protected function getRecipients(): array
{
    $admins = User::where('role', 'admin')->pluck('email')->toArray();
    
    // Add additional recipients
    $admins[] = 'ceo@daya.africa';
    $admins[] = 'operations@daya.africa';
    
    return $admins;
}
```

### Customize Metrics
Edit `AdminDigestService.php` to add/remove metrics or change calculations.

### Modify Email Design
Edit `resources/views/emails/daily_admin_digest.blade.php` to change layout, colors, or content.

## Testing Data

To test with actual data, create some test campaigns and scans:

```bash
# Create test data via tinker
php artisan tinker

>>> factory(App\Models\User::class, 5)->create(['role' => 'dcd']);
>>> factory(App\Models\Campaign::class, 10)->create(['created_at' => now()->subDay()]);
>>> factory(App\Models\Scan::class, 50)->create(['scanned_at' => now()->subDay()]);
```

Then run the digest command to see populated data.

## Monitoring

### Check Logs
```bash
tail -f storage/logs/laravel.log | grep "digest"
```

### Success Log Entry
```
[2026-01-05 08:00:12] local.INFO: Daily admin digest sent successfully
```

### Failure Log Entry
```
[2026-01-05 08:00:12] local.ERROR: Daily admin digest failed to send
```

## Troubleshooting

### Email Not Sending
1. Check mail configuration in `.env`
2. Test mail setup: `php artisan tinker` then `Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'))`
3. Check Laravel logs: `storage/logs/laravel.log`
4. Verify SMTP credentials and server access

### Scheduler Not Running
1. Verify cron job is set up correctly
2. Check cron logs: `grep CRON /var/log/syslog`
3. Run manually: `php artisan schedule:run`
4. Ensure scheduler is registered in `routes/console.php`

### No Data in Digest
1. Database might not have data for yesterday
2. Test with specific date: `php artisan digest:send-admin-daily --date=2026-01-03`
3. Check database has campaigns/scans with correct timestamps

### Wrong Timezone
1. Update `routes/console.php` timezone setting
2. Set application timezone in `config/app.php`: `'timezone' => 'Africa/Nairobi'`
3. Verify server timezone: `date` or `timedatectl`

## Future Enhancements

- [ ] Weekly summary digest (sent every Monday)
- [ ] Real-time critical alerts (separate from daily digest)
- [ ] Customizable digest preferences per admin
- [ ] Export digest as PDF attachment
- [ ] Slack integration for digest summary
- [ ] Dashboard to view historical digests
- [ ] SMS alerts for critical issues
- [ ] Configurable alert thresholds

## API Endpoints

### Test Digest Data
```
GET /api/admin/test-digest
```
Returns the digest data structure for yesterday.

### Send Test Email
```
POST /api/admin/send-test-digest
Body: {"email": "test@example.com"}
```
Sends a test digest email to the specified address.

## Example Email Preview

![Daily Digest Email](example-digest-email.png)

Subject: `Daya Daily Report - Jan 04, 2026 | 245 Scans | 12 New Campaigns`

Content includes:
- 📊 Daily Snapshot with key metrics
- 🚀 Highlights of achievements
- ⚠️ Attention Needed section with alerts
- 🏆 Top Performers (DCD, Campaign, Referrer)
- 📋 New Campaigns list
- 💰 Financial Summary
- Quick action buttons

---

**Implementation Date**: January 5, 2026  
**Version**: 1.0  
**Status**: Production Ready ✅

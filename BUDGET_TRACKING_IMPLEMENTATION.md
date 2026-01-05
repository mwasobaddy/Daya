# Campaign Budget Tracking & Auto-Completion Implementation

## Overview
This implementation adds comprehensive budget tracking, cost-per-click (CPC) management, automatic campaign completion, and enhanced duplicate scan prevention to ensure accurate campaign balance and fair scan distribution.

## Problem Statement

### Scenario
A client creates an **App Downloads** campaign with a budget of **KSh 1,000**:
- Cost per scan: **KSh 5**
- Expected scans: **200 verified scans** (1,000 ÷ 5)
- Challenge: How to track spending and stop at exactly 200 scans?
- Challenge: How to prevent the same device from scanning multiple times?

## Solution Components

### 1. Database Schema Changes

#### New Campaign Fields
```php
// Migration: 2026_01_05_142423_add_budget_tracking_to_campaigns_table.php

- cost_per_click (decimal 10,4): Stores the CPC rate for the campaign
- spent_amount (decimal 10,4): Tracks total amount spent on scans (sum of earnings)
- max_scans (integer): Maximum number of scans allowed (budget ÷ CPC)
- total_scans (integer): Total number of scans recorded
```

**Example Data:**
```
Campaign ID: 123
Budget: 1000.00 KSh
Cost per Click: 5.00 KSh
Max Scans: 200
Total Scans: 150 (currently)
Spent Amount: 750.00 KSh (150 × 5)
Remaining Budget: 250.00 KSh
Remaining Scans: 50
```

### 2. Cost Per Click Calculation

#### Rate Structure
```php
// Base rates in Kenyan Shillings (KSh)

Light-Touch Campaigns (1 KSh):
- Music Promotion: 1 KSh
- Brand Awareness (simple): 1 KSh
- Event Promotion (simple): 1 KSh
- Social Cause (simple): 1 KSh

Moderate-Touch Campaigns (5 KSh):
- App Downloads: 5 KSh
- Product Launch: 5 KSh
- Brand Awareness (with explainer video): 5 KSh
- Event Promotion (with explainer video): 5 KSh
- Social Cause (with explainer video): 5 KSh
```

#### Country-Specific Adjustments
```php
- Kenya (KE): Base rate (1 KSh or 5 KSh)
- Nigeria (NG): Base rate × 10 (10 ₦ or 50 ₦)
  // 1 KSh = 10 Naira conversion
```

### 3. Campaign Model Enhancements

#### New Methods
```php
// Check if campaign has reached scan limit
hasReachedScanLimit(): bool

// Check if campaign has exhausted budget
hasExhaustedBudget(): bool

// Get remaining budget
getRemainingBudget(): float

// Get remaining scans allowed
getRemainingScans(): int

// Check if campaign can accept more scans
canAcceptScans(): bool
```

#### Example Usage
```php
$campaign = Campaign::find(123);

if ($campaign->canAcceptScans()) {
    // Process scan
} else {
    // Auto-complete campaign
    $campaign->update([
        'status' => 'completed',
        'completed_at' => now()
    ]);
}
```

### 4. Scan Processing Flow

#### Step-by-Step Process

**1. User Scans QR Code**
```
User → QR Code → /scan/dcd?dcd=123
```

**2. Campaign Validation**
```php
// QRCodeService::recordDcdScan()

- Find active campaign for DCD
- Check if campaign can accept scans
- If budget exhausted → Auto-complete campaign
- If OK → Create scan record
```

**3. Scan Record Creation**
```php
Scan::create([
    'dcd_id' => $dcdId,
    'campaign_id' => $campaignId,
    'device_fingerprint' => $fingerprint,
    'scanned_at' => now(),
]);
```

**4. Reward Processing**
```php
// ScanRewardService::creditScanReward()

- Check for duplicate earning (by scan_id)
- Validate campaign budget availability
- Check device fingerprint for recent duplicate (1 hour)
- Create earning record
- Update scan earnings field
- Increment campaign spent_amount
- Increment campaign total_scans
- Check if limit reached → Auto-complete
```

**5. Auto-Completion**
```php
if (!$campaign->canAcceptScans()) {
    $campaign->update([
        'status' => 'completed',
        'completed_at' => now()
    ]);
    
    Log::info('Campaign auto-completed', [
        'campaign_id' => $campaign->id,
        'total_scans' => $campaign->total_scans,
        'spent_amount' => $campaign->spent_amount
    ]);
}
```

### 5. Duplicate Scan Prevention

#### Device Fingerprinting
```php
// When creating a scan
device_fingerprint: Unique identifier for device/browser

// Deduplication logic in ScanRewardService
- Check for scans with same fingerprint
- Within same campaign
- In last 1 hour
- If found with existing earning → Skip reward
```

#### Multi-Layer Protection
```
1. Earning Deduplication: One earning per scan_id
2. Device Fingerprint: One reward per device per hour
3. Budget Limit: Campaign stops at max_scans
4. Auto-Completion: Prevents further scans after limit
```

### 6. Frontend Integration

#### Campaign Submission Form
```tsx
// When user enters budget and selects objective:

Cost per scan: KSh 5
Maximum scans: 200 verified scans
Campaign will automatically complete when scan limit is reached.
```

#### Budget Breakdown Display
```tsx
<div className="budget-breakdown">
  <p>Cost per scan: {getCurrencySymbol(country)}{costPerClick}</p>
  <p>Maximum scans: {Math.floor(budget / costPerClick)} verified scans</p>
  <p>Campaign will automatically complete when the scan limit is reached.</p>
</div>
```

#### Review Section
```tsx
Budget: KSh 1,000
Cost per Click: KSh 5
Maximum Scans: 200 verified scans
```

## Real-World Example

### Campaign Lifecycle

**Initial State (Campaign Created)**
```json
{
  "id": 456,
  "title": "Download Our App",
  "campaign_objective": "app_downloads",
  "budget": 1000.00,
  "cost_per_click": 5.00,
  "max_scans": 200,
  "total_scans": 0,
  "spent_amount": 0.00,
  "status": "approved"
}
```

**After 50 Scans**
```json
{
  "total_scans": 50,
  "spent_amount": 250.00,  // 50 × 5
  "status": "approved",
  "remaining_budget": 750.00,
  "remaining_scans": 150
}
```

**After 199 Scans**
```json
{
  "total_scans": 199,
  "spent_amount": 995.00,  // 199 × 5
  "status": "approved",
  "remaining_budget": 5.00,
  "remaining_scans": 1
}
```

**After 200th Scan (Auto-Completion)**
```json
{
  "total_scans": 200,
  "spent_amount": 1000.00,  // 200 × 5 = budget
  "status": "completed",  // AUTO-COMPLETED
  "completed_at": "2026-01-05 14:30:00",
  "remaining_budget": 0.00,
  "remaining_scans": 0
}
```

**201st Scan Attempt**
```
Result: Campaign rejected (already completed)
Log: "Campaign 456 cannot accept more scans (budget exhausted)"
```

## Testing Scenarios

### Test Case 1: Normal Flow
```
1. Create campaign with 100 KSh budget, app_downloads (5 KSh CPC)
2. Expected max_scans: 20
3. Process 20 scans
4. Verify: Campaign auto-completes after 20th scan
5. Verify: 21st scan is rejected
```

### Test Case 2: Duplicate Prevention
```
1. Process scan with device_fingerprint "ABC123"
2. Immediately process another scan with same fingerprint
3. Verify: Second scan creates record but no earning
4. Wait 1 hour
5. Process scan again with same fingerprint
6. Verify: New earning created
```

### Test Case 3: Nigerian Rates
```
1. Create campaign in Nigeria with 1000 ₦ budget
2. Music promotion objective
3. Cost per click: 10 ₦ (1 KSh × 10)
4. Expected max_scans: 100
5. Verify calculations are correct
```

## Database Queries

### Check Campaign Budget Status
```sql
SELECT 
    id,
    title,
    budget,
    cost_per_click,
    spent_amount,
    (budget - spent_amount) as remaining_budget,
    max_scans,
    total_scans,
    (max_scans - total_scans) as remaining_scans,
    status
FROM campaigns
WHERE id = 123;
```

### Find Campaigns Near Completion
```sql
SELECT *
FROM campaigns
WHERE status = 'approved'
  AND total_scans >= (max_scans * 0.9)
ORDER BY total_scans DESC;
```

### Detect Potential Duplicate Scans
```sql
SELECT 
    device_fingerprint,
    campaign_id,
    COUNT(*) as scan_count
FROM scans
WHERE created_at >= NOW() - INTERVAL 1 HOUR
  AND device_fingerprint IS NOT NULL
GROUP BY device_fingerprint, campaign_id
HAVING scan_count > 1;
```

## Logging & Monitoring

### Key Log Events
```php
// Campaign auto-completion
Log::info('Campaign auto-completed', [
    'campaign_id' => $id,
    'total_scans' => $total,
    'spent_amount' => $spent,
    'reason' => 'budget_limit_reached'
]);

// Duplicate scan detected
Log::info('Duplicate scan deduped', [
    'scan_id' => $id,
    'device_fingerprint' => $fp,
    'campaign_id' => $campaignId
]);

// Budget check failure
Log::warning('Scan rejected - budget exhausted', [
    'campaign_id' => $id,
    'spent_amount' => $spent,
    'budget' => $budget
]);
```

## Benefits

1. **Accurate Budget Tracking**: Real-time tracking of spent vs. budget
2. **Automatic Completion**: Campaigns complete exactly at budget limit
3. **Fair Distribution**: Prevents scan abuse with device fingerprinting
4. **Transparent Pricing**: Users see exact scan count before submitting
5. **Cost Efficiency**: No overspending on campaigns
6. **Audit Trail**: Complete tracking of all scans and earnings

## Migration & Deployment

### Steps
1. Run migration: `php artisan migrate`
2. Build frontend: `npm run build`
3. Existing campaigns will have 0 for new fields
4. New campaigns automatically calculate and track budget

### Backward Compatibility
- Existing campaigns continue to work
- New fields default to 0 for old campaigns
- Can manually update old campaigns if needed

## Future Enhancements

1. **Budget Alerts**: Notify when 80% budget spent
2. **Budget Top-ups**: Allow clients to add more budget
3. **Advanced Analytics**: Detailed budget utilization reports
4. **Fraud Detection**: Enhanced device fingerprinting with IP/location
5. **Dynamic Pricing**: Adjust CPC based on campaign performance

## Support & Troubleshooting

### Common Issues

**Campaign not completing after reaching limit**
- Check: canAcceptScans() method returning correct value
- Verify: spent_amount is incrementing properly
- Review logs for auto-completion events

**Scans still being processed after completion**
- Check: Campaign status validation in QRCodeService
- Ensure: Frontend refreshes campaign status

**Device fingerprint not working**
- Verify: device_fingerprint being passed in geoData
- Check: 1-hour deduplication window logic

---

**Implementation Date**: January 5, 2026  
**Author**: AI Assistant  
**Version**: 1.0

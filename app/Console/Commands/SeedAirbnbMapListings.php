<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\Earning;
use App\Models\Scan;
use App\Models\User;
use App\Models\Ward;
use App\Services\QRCodeService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetException;

class SeedAirbnbMapListings extends Command
{
    protected $signature = 'dds:seed-airbnb-map
        {--file=storage/app/airbnb-map.xlsx : Path to the Airbnb Map Excel workbook}
        {--batch=airbnb_map_test : Identifier for this seed batch}
        {--cleanup : Remove the records belonging to the batch instead of importing}
        {--client-email=airbnb-map-client@daya.test}
        {--client-name="Airbnb Map Client"}
        {--dcd-email=airbnb-map-dcd@daya.test}
        {--dcd-name="Airbnb Map DCD"}
        {--objective=apartment_listing : Campaign objective for seeded listings}
        {--budget=5000 : Budget (KSh) for each seeded campaign}
        {--cpc=5 : Cost per click (KSh)}
        {--start-date= : Optional campaign start date (YYYY-mm-dd)}
        {--end-date= : Optional campaign end date (YYYY-mm-dd)}
        {--limit= : Optional row limit to import}}';

    protected $description = 'Seed Airbnb Map listings as location-based campaigns for a reusable test DCD/client pair';

    protected QRCodeService $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        parent::__construct();

        $this->qrCodeService = $qrCodeService;
    }

    public function handle(): int
    {
        $batchKey = (string) $this->option('batch');

        if ($this->option('cleanup')) {
            return $this->cleanupBatch($batchKey);
        }

        $file = $this->resolveFilePath($this->option('file'));
        if (! file_exists($file)) {
            $this->error("The spreadsheet could not be found at {$file}");

            return self::FAILURE;
        }

        $rows = $this->parseListingRows($file);
        if (count($rows) === 0) {
            $this->info('No data rows were found in the spreadsheet.');

            return self::SUCCESS;
        }

        $ward = Ward::with('subcounty.county.country')->first();
        if (! $ward) {
            $this->error('No ward metadata is available. Seed geographical data before running this command.');

            return self::FAILURE;
        }

        $client = $this->ensureTestClient(
            $this->option('client-email'),
            $this->option('client-name'),
            $ward,
            $batchKey
        );

        $dcd = $this->ensureTestDcd(
            $this->option('dcd-email'),
            $this->option('dcd-name'),
            $ward,
            $batchKey
        );

        $startDate = $this->resolveStartDate();
        $endDate = $this->resolveEndDate($startDate);
        $budget = (float) $this->option('budget');
        $cpc = (float) $this->option('cpc');
        $objective = (string) $this->option('objective');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $sourceLabel = pathinfo($file, PATHINFO_BASENAME);

        $this->info("Seeding Airbnb listings (batch: {$batchKey}) using {$sourceLabel}");
        $created = 0;
        foreach ($rows as $entry) {
            if ($limit !== null && $created >= $limit) {
                break;
            }

            if ($this->seedListingRow(
                $entry['data'],
                $entry['row_number'],
                $client,
                $dcd,
                $startDate,
                $endDate,
                $budget,
                $cpc,
                $objective,
                $batchKey,
                $sourceLabel
            )) {
                $created++;
            }
        }

        $this->info("Imported {$created} listings for batch {$batchKey}.");
        $this->info('Use --cleanup to remove these records when finished.');

        return self::SUCCESS;
    }

    protected function resolveFilePath(?string $path): string
    {
        if ($path === null) {
            return storage_path('app/airbnb-map.xlsx');
        }

        if ($path[0] === '~') {
            return Str::replaceFirst('~', $_SERVER['HOME'] ?? '', $path);
        }

        return $path;
    }

    protected function resolveStartDate(): Carbon
    {
        if ($this->option('start-date')) {
            return Carbon::parse($this->option('start-date'))->startOfDay();
        }

        return Carbon::now()->startOfDay();
    }

    protected function resolveEndDate(Carbon $startDate): Carbon
    {
        if ($this->option('end-date')) {
            return Carbon::parse($this->option('end-date'))->endOfDay();
        }

        return $startDate->copy()->addDays(30)->endOfDay();
    }

    protected function parseListingRows(string $file): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
        } catch (SpreadsheetException $exception) {
            $this->error('Unable to read the spreadsheet: '.$exception->getMessage());

            return [];
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $rowNumbers = array_keys($rows);
        if (empty($rowNumbers)) {
            return [];
        }

        $headerKey = array_shift($rowNumbers);
        $header = $rows[$headerKey] ?? [];
        $mapping = [];
        foreach ($header as $column => $value) {
            $normalized = $this->normalizeHeader((string) $value);
            if ($normalized !== '') {
                $mapping[$column] = $normalized;
            }
        }

        $result = [];
        foreach ($rowNumbers as $rowNumber) {
            $row = $rows[$rowNumber];
            $normalized = [];

            foreach ($mapping as $column => $field) {
                $normalized[$field] = trim((string) ($row[$column] ?? ''));
            }

            if (! $this->rowHasData($normalized)) {
                continue;
            }

            $result[] = [
                'row_number' => $rowNumber,
                'data' => $normalized,
            ];
        }

        return $result;
    }

    protected function normalizeHeader(string $value): string
    {
        return (string) Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_');
    }

    protected function rowHasData(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    protected function seedListingRow(
        array $row,
        int $rowNumber,
        User $client,
        User $dcd,
        Carbon $startDate,
        Carbon $endDate,
        float $budget,
        float $cpc,
        string $objective,
        string $batchKey,
        string $sourceLabel
    ): bool {
        $latitude = $this->extractCoordinate($row, ['latitude', 'lat']);
        $longitude = $this->extractCoordinate($row, ['longitude', 'lon', 'lng']);

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            $this->warn("Row {$rowNumber} has no usable coordinates. Skipping.");

            return false;
        }

        $listingUrl = $row['listing_url'] ?? $row['url'] ?? $row['link'] ?? null;
        $address = $row['address'] ?? $row['location'] ?? $row['city'] ?? 'Airbnb Map listing';
        $title = $row['name'] ?? $row['listing_name'] ?? $row['title'] ?? "Airbnb listing {$rowNumber}";
        $county = $row['county'] ?? $row['city'] ?? 'Airbnb Map District';
        $price = $row['price'] ?? $row['rate'] ?? $row['daily_rate'] ?? null;

        if ($listingUrl && Campaign::where('metadata', 'like', "%\"listing_url\":\"{$listingUrl}%")
            ->where('metadata->seed_batch', $batchKey)
            ->exists()) {
            $this->warn("Row {$rowNumber} already exists for batch {$batchKey} (same listing URL). Skipping.");

            return false;
        }

        $maxScans = max(1, (int) floor($budget / max($cpc, 0.01)));
        $campaignData = [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => $title,
            'description' => "Location matching test listing near {$address}",
            'budget' => $budget,
            'cost_per_click' => $cpc,
            'spent_amount' => 0,
            'campaign_credit' => $budget,
            'max_scans' => $maxScans,
            'total_scans' => 0,
            'county' => $county,
            'status' => 'live',
            'campaign_objective' => $objective,
            'digital_product_link' => $listingUrl ?: "https://www.google.com/maps?q={$latitude},{$longitude}",
            'target_audience' => 'Travelers near the listing',
            'duration' => $startDate->format('Y-m-d').' to '.$endDate->format('Y-m-d'),
            'objectives' => 'Location proximity and QR workflow testing',
            'metadata' => [
                'location_latitude' => $latitude,
                'location_longitude' => $longitude,
                'address' => $address,
                'listing_name' => $title,
                'listing_url' => $listingUrl,
                'listing_price' => $this->extractFloat($price),
                'listing_source' => $sourceLabel,
                'seed_batch' => $batchKey,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'row_number' => $rowNumber,
            ],
        ];

        try {
            $campaign = Campaign::create($campaignData);
            $this->info("Created campaign {$campaign->id} for row {$rowNumber} (lat: {$latitude}, lng: {$longitude}).");

            return true;
        } catch (\Exception $exception) {
            $this->error("Row {$rowNumber} could not be imported: {$exception->getMessage()}");

            return false;
        }
    }

    protected function extractCoordinate(array $row, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== '') {
                $value = str_replace(',', '.', $row[$key]);
                if (is_numeric($value)) {
                    return (float) $value;
                }
            }
        }

        return null;
    }

    protected function extractFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = str_replace([',', ' '], ['', ''], (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function ensureTestClient(string $email, string $name, Ward $ward, string $batchKey): User
    {
        $client = User::firstOrNew(['email' => $email]);

        $needsSave = false;
        if (! $client->exists) {
            $client->fill([
                'name' => $name,
                'password' => bcrypt(Str::random(12)),
                'role' => 'client',
                'phone' => '+2547'.rand(10000000, 99999999),
                'country_id' => $ward->subcounty->county->country->id,
                'county_id' => $ward->subcounty->county->id,
                'subcounty_id' => $ward->subcounty->id,
                'ward_id' => $ward->id,
                'business_name' => $name,
                'account_type' => 'business',
                'referral_code' => $this->generateReferralCode($batchKey, 'client'),
            ]);
            $needsSave = true;
        }

        if ($client->referral_code === null) {
            $client->referral_code = $this->generateReferralCode($batchKey, 'client');
            $needsSave = true;
        }

        if ($needsSave) {
            $client->save();
            $this->info("Created/updated client {$email} (ID: {$client->id}).");
        } else {
            $this->info("Reusing existing client {$email} (ID: {$client->id}).");
        }

        return $client;
    }

    protected function ensureTestDcd(string $email, string $name, Ward $ward, string $batchKey): User
    {
        $dcd = User::firstOrNew(['email' => $email]);
        $needsSave = false;

        if (! $dcd->exists) {
            $dcd->fill([
                'name' => $name,
                'password' => bcrypt(Str::random(12)),
                'role' => 'dcd',
                'national_id' => 'AIRBNB'.rand(1000, 9999),
                'phone' => '+2547'.rand(10000000, 99999999),
                'country_id' => $ward->subcounty->county->country->id,
                'county_id' => $ward->subcounty->county->id,
                'subcounty_id' => $ward->subcounty->id,
                'ward_id' => $ward->id,
                'wallet_pin' => bcrypt('1234'),
                'wallet_type' => 'business',
                'wallet_status' => 'active',
                'profile' => [
                    'full_name' => $name,
                    'date_of_birth' => '1995-01-01',
                    'gender' => 'unspecified',
                    'business_address' => 'Airbnb Map test address',
                    'business_name' => $name,
                    'business_types' => ['apartment_listing'],
                    'daily_foot_traffic' => '5-20',
                    'operating_hours_start' => '07:00',
                    'operating_hours_end' => '22:00',
                    'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday'],
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ],
                'account_type' => 'business',
                'referral_code' => $this->generateReferralCode($batchKey, 'dcd'),
            ]);
            $needsSave = true;
        }

        if ($dcd->referral_code === null) {
            $dcd->referral_code = $this->generateReferralCode($batchKey, 'dcd');
            $needsSave = true;
        }

        if ($needsSave) {
            $dcd->save();
            $this->info("Created/updated DCD {$email} (ID: {$dcd->id}).");
        } else {
            $this->info("Reusing existing DCD {$email} (ID: {$dcd->id}).");
        }

        $qrPath = $this->qrCodeService->generateDcdQr($dcd);
        $dcd->qr_code = $qrPath;
        $dcd->save();

        $this->info("Generated fresh QR code for DCD (stored at {$qrPath}).");

        return $dcd;
    }

    protected function generateReferralCode(string $batchKey, string $type): string
    {
        do {
            $code = Str::upper(Str::slug("{$batchKey}_{$type}_".Str::random(4), ''));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    protected function cleanupBatch(string $batchKey): int
    {
        $campaigns = Campaign::where('metadata->seed_batch', $batchKey)->get();
        if ($campaigns->isEmpty()) {
            $this->info("No campaigns found for batch {$batchKey}.");
        } else {
            $campaignIds = $campaigns->pluck('id');
            Scan::whereIn('campaign_id', $campaignIds)->delete();
            Earning::whereIn('campaign_id', $campaignIds)->delete();
            Campaign::whereIn('id', $campaignIds)->delete();

            $this->info("Deleted {$campaignIds->count()} campaigns and related scans/earnings for batch {$batchKey}.");
        }

        $this->cleanupUser($this->option('client-email'));
        $this->cleanupUser($this->option('dcd-email'));

        return self::SUCCESS;
    }

    protected function cleanupUser(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return;
        }

        if ($user->qr_code) {
            Storage::disk('public')->delete($user->qr_code);
        }

        $user->delete();
        $this->info("Removed user {$email} (ID: {$user->id}).");
    }
}

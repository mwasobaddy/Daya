<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table to modify the CHECK constraint
        // This is a simplified version that preserves data

        DB::beginTransaction();

        try {
            // Create temporary table with new schema
            DB::statement("
                CREATE TABLE users_temp AS
                SELECT * FROM users
            ");

            // Drop original table
            DB::statement("DROP TABLE users");

            // Create new table with updated role enum
            DB::statement("
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(255),
                    national_id VARCHAR(255),
                    role VARCHAR(255) CHECK (role IN ('da', 'dcd', 'client', 'admin', 'company') OR role IS NULL),
                    referral_code VARCHAR(255),
                    qr_code VARCHAR(255),
                    wallet_status VARCHAR(255) DEFAULT 'pending',
                    wallet_pin VARCHAR(255),
                    wallet_type VARCHAR(255) CHECK (wallet_type IN ('personal', 'business', 'both') OR wallet_type IS NULL),
                    profile TEXT,
                    country_id INTEGER,
                    county_id INTEGER,
                    subcounty_id INTEGER,
                    ward_id INTEGER,
                    business_name VARCHAR(255),
                    account_type VARCHAR(255) CHECK (account_type IN ('startup', 'artist', 'label', 'ngo', 'agency', 'business') OR account_type IS NULL),
                    email_verified_at DATETIME,
                    password VARCHAR(255) NOT NULL,
                    remember_token VARCHAR(100),
                    created_at DATETIME,
                    updated_at DATETIME,
                    two_factor_secret TEXT,
                    two_factor_recovery_codes TEXT,
                    two_factor_confirmed_at DATETIME
                )
            ");

            // Copy data back
            DB::statement("
                INSERT INTO users (
                    id, name, email, phone, national_id, role, referral_code, qr_code,
                    wallet_status, wallet_pin, wallet_type, profile, country_id, county_id,
                    subcounty_id, ward_id, business_name, account_type, email_verified_at,
                    password, remember_token, created_at, updated_at, two_factor_secret,
                    two_factor_recovery_codes, two_factor_confirmed_at
                )
                SELECT
                    id, name, email, phone, national_id, role, referral_code, qr_code,
                    wallet_status, wallet_pin, wallet_type, profile, country_id, county_id,
                    subcounty_id, ward_id, business_name, account_type, email_verified_at,
                    password, remember_token, created_at, updated_at, two_factor_secret,
                    two_factor_recovery_codes, two_factor_confirmed_at
                FROM users_temp
            ");

            // Drop temp table
            DB::statement("DROP TABLE users_temp");

            // Recreate indexes
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users (email)");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS users_phone_unique ON users (phone)");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS users_national_id_unique ON users (national_id)");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS users_referral_code_unique ON users (referral_code)");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse by recreating with original enum
        DB::beginTransaction();

        try {
            DB::statement("
                CREATE TABLE users_temp AS
                SELECT * FROM users
            ");

            DB::statement("DROP TABLE users");

            DB::statement("
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(255),
                    national_id VARCHAR(255),
                    role VARCHAR(255) CHECK (role IN ('da', 'dcd', 'client', 'admin') OR role IS NULL),
                    referral_code VARCHAR(255),
                    qr_code VARCHAR(255),
                    wallet_status VARCHAR(255) DEFAULT 'pending',
                    wallet_pin VARCHAR(255),
                    wallet_type VARCHAR(255) CHECK (wallet_type IN ('personal', 'business', 'both') OR wallet_type IS NULL),
                    profile TEXT,
                    country_id INTEGER,
                    county_id INTEGER,
                    subcounty_id INTEGER,
                    ward_id INTEGER,
                    business_name VARCHAR(255),
                    account_type VARCHAR(255) CHECK (account_type IN ('startup', 'artist', 'label', 'ngo', 'agency', 'business') OR account_type IS NULL),
                    email_verified_at DATETIME,
                    password VARCHAR(255) NOT NULL,
                    remember_token VARCHAR(100),
                    created_at DATETIME,
                    updated_at DATETIME,
                    two_factor_secret TEXT,
                    two_factor_recovery_codes TEXT,
                    two_factor_confirmed_at DATETIME
                )
            ");

            DB::statement("
                INSERT INTO users (
                    id, name, email, phone, national_id, role, referral_code, qr_code,
                    wallet_status, wallet_pin, wallet_type, profile, country_id, county_id,
                    subcounty_id, ward_id, business_name, account_type, email_verified_at,
                    password, remember_token, created_at, updated_at, two_factor_secret,
                    two_factor_recovery_codes, two_factor_confirmed_at
                )
                SELECT
                    id, name, email, phone, national_id,
                    CASE WHEN role = 'company' THEN 'admin' ELSE role END,
                    referral_code, qr_code, wallet_status, wallet_pin, wallet_type, profile,
                    country_id, county_id, subcounty_id, ward_id, business_name, account_type,
                    email_verified_at, password, remember_token, created_at, updated_at,
                    two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at
                FROM users_temp
            ");

            DB::statement("DROP TABLE users_temp");

            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users (email)");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS users_phone_unique ON users (phone)");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS users_national_id_unique ON users (national_id)");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS users_referral_code_unique ON users (referral_code)");

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
};

<?php

use App\Models\Earning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('process earnings command marks old pending earnings as paid and notifies', function () {
    Mail::fake();

    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $user = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);

    // Create old pending earning
    $earning = Earning::create([
        'user_id' => $user->id,
        'amount' => 1.0,
        'type' => 'scan',
        'description' => 'Old earning',
        'related_id' => 12345,
        'status' => 'pending',
        'month' => now()->subDays(10)->format('Y-m'),
    ]);

    // Adjust created_at so it's older than - created_at isn't mass-assignable by default
    $earning->created_at = now()->subDays(10);
    $earning->updated_at = now()->subDays(10);
    $earning->save();

    expect(Earning::where('status','pending')->count())->toBe(1);
    expect(Earning::first()->created_at->lessThan(now()->subDays(7)))->toBeTrue();

    $this->artisan('daya:process-earnings', ['--days' => 7, '--mark-paid' => true])
        ->expectsOutput('Found 1 pending earnings older than 7 days.')
        ->expectsOutput('Marked 1 earnings as paid.')
        ->assertExitCode(0);

    $this->assertDatabaseHas('earnings', ['id' => $earning->id, 'status' => 'paid']);
    Mail::assertSent(\App\Mail\PaymentCompleted::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

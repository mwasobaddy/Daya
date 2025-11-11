<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Referral;
use App\Services\VentureShareService;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mail;

class DcdController extends Controller
{
    protected $ventureShareService;
    protected $qrCodeService;

    public function __construct(VentureShareService $ventureShareService, QRCodeService $qrCodeService)
    {
        $this->ventureShareService = $ventureShareService;
        $this->qrCodeService = $qrCodeService;
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'referral_code' => 'required|string|exists:users,referral_code',
        ]);

        // Find the DA who referred this DCD
        $referrer = User::where('referral_code', $request->referral_code)->first();

        if ($referrer->role !== 'da') {
            return response()->json(['message' => 'Invalid referral code'], 400);
        }

        // Generate QR code URL (placeholder - in production, generate actual QR)
        $qrCodeUrl = 'https://daya.com/qr/' . Str::uuid();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'dcd',
            'qr_code' => $qrCodeUrl,
        ]);

        // Generate actual QR code
        $qrCodeFilename = $this->qrCodeService->generateDCDQRCode($user);
        $qrCodeUrl = $this->qrCodeService->getQRCodeUrl($qrCodeFilename);

        // Create referral record
        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $user->id,
            'type' => 'da_to_dcd',
        ]);

        // Allocate venture shares for the referral
        $this->ventureShareService->allocateSharesForReferral($referral);

        // Send welcome email
        Mail::to($user->email)->send(new \App\Mail\DcdWelcome($user, $referrer));

        return response()->json([
            'message' => 'DCD registered successfully',
            'qr_code' => $qrCodeUrl
        ]);
    }
}

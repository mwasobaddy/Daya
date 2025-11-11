<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mail;

class DaController extends Controller
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
        ]);

        $referralCode = Str::upper(Str::random(8));

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'da',
            'referral_code' => $referralCode,
        ]);

        // Generate QR code for referral
        $qrCodeFilename = $this->qrCodeService->generateDAReferralQRCode($user);
        $qrCodeUrl = $this->qrCodeService->getQRCodeUrl($qrCodeFilename);

        // Update user with QR code
        $user->update(['qr_code' => $qrCodeFilename]);

        // Send welcome email
        Mail::to($user->email)->send(new \App\Mail\DaWelcome($user));

        return response()->json(['message' => 'DA registered successfully', 'referral_code' => $referralCode]);
    }
}

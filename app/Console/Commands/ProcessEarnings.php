<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Earning;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ProcessEarnings extends Command
{
    protected $signature = 'daya:process-earnings {--days=7} {--mark-paid}';
    protected $description = 'Process pending earnings older than X days. If --mark-paid is set, mark as paid and notify users.';

    public function handle()
    {
        $days = (int) $this->option('days');
        $markPaid = $this->option('mark-paid');

        $cutoff = Carbon::now()->subDays($days);
        $earnings = Earning::where('status', 'pending')->where('created_at', '<', $cutoff)->get();

        $this->info('Found ' . $earnings->count() . ' pending earnings older than ' . $days . ' days.');

        if ($markPaid && $earnings->count() > 0) {
            foreach ($earnings as $earning) {
                $earning->update(['status' => 'paid', 'paid_at' => Carbon::now()]);
                // send user notification
                try {
                    Mail::to($earning->user->email)->send(new \App\Mail\PaymentCompleted($earning));
                } catch (\Exception $e) {
                    $this->warn('Failed to send PaymentCompleted mail to ' . $earning->user->email);
                }
            }
            $this->info('Marked ' . $earnings->count() . ' earnings as paid.');
        }

        return 0;
    }
}

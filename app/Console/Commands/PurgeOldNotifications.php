<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;

class PurgeOldNotifications extends Command
{
    protected $signature   = 'notifications:purge';
    protected $description = 'Supprime les notifications de plus de 15 jours';

    public function handle(): void
    {
        $count = Notification::where('created_at', '<', now()->subDays(15))->delete();
        $this->info("$count notification(s) supprimée(s).");
    }
}
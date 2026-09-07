<?php

use App\Actions\Subscription\ExpireSubscriptionsAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('subscriptions:expire', function (ExpireSubscriptionsAction $action): int {
    $count = $action->execute();
    $this->info("{$count} subscription diperbarui menjadi expired.");

    return 0;
})->purpose('Expire trials and active subscriptions that have reached their end time');

Schedule::command('subscriptions:expire')->hourly()->withoutOverlapping();

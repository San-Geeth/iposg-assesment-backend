<?php

use Illuminate\Console\Scheduling\Schedule;

return function (Schedule $schedule) {
    $schedule->command('app:send-daily-invoices')->dailyAt('21:00');
};

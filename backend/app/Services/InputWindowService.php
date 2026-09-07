<?php

namespace App\Services;

use App\Models\Company;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class InputWindowService
{
    public function assertAllowed(Company $company, string|\DateTimeInterface $activityDate): void
    {
        $timezone = $company->timezone ?: 'Asia/Jakarta';
        // Date casts carry the application timezone. Rebuild the calendar date
        // in the tenant timezone so a UTC midnight cannot look like tomorrow.
        $calendarDate = $activityDate instanceof \DateTimeInterface
            ? $activityDate->format('Y-m-d')
            : $activityDate;
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $calendarDate, $timezone);
        $now = CarbonImmutable::now($timezone);

        if ($date->isAfter($now->startOfDay())) {
            throw ValidationException::withMessages([
                'activity_date' => ['Tanggal aktivitas tidak boleh berada di masa depan.'],
            ]);
        }

        // activity_date tidak menyimpan jam. Cutoff dibuat eksplisit pada akhir
        // hari kalender kedua setelah tanggal aktivitas dalam zona waktu company.
        if ($now->isAfter($date->endOfDay()->addDays(2))) {
            throw ValidationException::withMessages([
                'activity_date' => ['Batas input aktivitas maksimal dua hari setelah tanggal aktivitas.'],
            ]);
        }
    }
}

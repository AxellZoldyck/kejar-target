<?php

namespace App\Support;

use App\Models\Company;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final readonly class PeriodRange
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public string $type,
    ) {}

    public static function monthly(Company $company, ?string $period = null): self
    {
        $timezone = $company->timezone ?: 'Asia/Jakarta';
        $period ??= CarbonImmutable::now($timezone)->format('Y-m');

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            throw ValidationException::withMessages(['period' => ['Periode bulanan harus berformat YYYY-MM.']]);
        }

        $start = CarbonImmutable::createFromFormat('!Y-m-d', $period.'-01', $timezone);

        return new self($start, $start->endOfMonth(), 'monthly');
    }

    public static function fromRequest(Company $company, ?string $type, ?string $anchor): self
    {
        $timezone = $company->timezone ?: 'Asia/Jakarta';
        $type = $type === 'weekly' ? 'weekly' : 'monthly';

        if ($type === 'monthly') {
            $period = $anchor && preg_match('/^\d{4}-\d{2}$/', $anchor)
                ? $anchor
                : ($anchor ? substr($anchor, 0, 7) : null);

            return self::monthly($company, $period);
        }

        if ($anchor !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $anchor)) {
            throw ValidationException::withMessages(['period' => ['Periode mingguan harus berupa tanggal YYYY-MM-DD.']]);
        }

        $point = $anchor
            ? CarbonImmutable::createFromFormat('!Y-m-d', $anchor, $timezone)
            : CarbonImmutable::now($timezone);
        $start = $point->startOfWeek(CarbonImmutable::MONDAY);

        return new self($start, $start->endOfWeek(CarbonImmutable::SUNDAY), 'weekly');
    }

    public function startDate(): string
    {
        return $this->start->format('Y-m-d');
    }

    public function endDate(): string
    {
        return $this->end->format('Y-m-d');
    }

    public function key(): string
    {
        return $this->type === 'monthly'
            ? $this->start->format('Y-m')
            : $this->start->format('Y-m-d');
    }
}

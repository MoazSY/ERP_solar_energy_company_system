<?php

namespace App\Support;

use App\Models\Solar_company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class CompanyBanHelper
{
    public static function isBanned(Solar_company|int|null $company): bool
    {
        if ($company === null) {
            return false;
        }

        if (is_int($company)) {
            $company = Solar_company::find($company);
            if (!$company) {
                return false;
            }
        }

        return $company->banned_until !== null
            && Carbon::parse($company->banned_until)->isFuture();
    }

    public static function scopeNotBanned(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder
                ->whereNull('banned_until')
                ->orWhere('banned_until', '<=', now());
        });
    }

    public static function calculateBanEnd(string $blockType, int $durationValue, ?Carbon $from = null): Carbon
    {
        $from = $from ?? now();

        return match ($blockType) {
            'hour' => $from->copy()->addHours($durationValue),
            'day' => $from->copy()->addDays($durationValue),
            'week' => $from->copy()->addWeeks($durationValue),
            default => $from->copy(),
        };
    }

    public static function banErrorMessage(Solar_company $company): string
    {
        $until = Carbon::parse($company->banned_until)->toDateTimeString();

        return "This company is temporarily banned and cannot receive new requests until {$until}";
    }

    public static function assertAcceptsCustomerRequests(Solar_company|int|null $company): ?string
    {
        if (!self::isBanned($company)) {
            return null;
        }

        if (is_int($company)) {
            $company = Solar_company::find($company);
        }

        return $company ? self::banErrorMessage($company) : 'company not found';
    }
}

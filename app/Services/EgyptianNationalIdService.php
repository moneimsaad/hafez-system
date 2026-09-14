<?php

namespace App\Services;

use Carbon\Carbon;

class EgyptianNationalIdService
{
    public function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return strtr(trim($value), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    public function parse(?string $value): ?array
    {
        $nationalId = $this->normalize($value);
        if ($nationalId === null || ! preg_match('/^[23]\d{13}$/', $nationalId)) {
            return null;
        }

        $year = ((int) $nationalId[0] === 2 ? 1900 : 2000) + (int) substr($nationalId, 1, 2);
        $month = (int) substr($nationalId, 3, 2);
        $day = (int) substr($nationalId, 5, 2);
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        $birthDate = Carbon::createSafe($year, $month, $day);
        if (! $birthDate || $birthDate->isFuture()) {
            return null;
        }

        $governorateCode = substr($nationalId, 7, 2);
        $governorate = config("egyptian_national_id.governorates.{$governorateCode}");
        if ($governorate === null) {
            return null;
        }

        return [
            'national_id' => $nationalId,
            'birth_date' => $birthDate->format('Y-m-d'),
            'gender' => ((int) $nationalId[12]) % 2 === 1 ? 'Male' : 'Female',
            'governorate' => $governorate,
            'governorate_code' => $governorateCode,
        ];
    }
}

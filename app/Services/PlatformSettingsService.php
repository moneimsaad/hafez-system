<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

class PlatformSettingsService
{
    private const CACHE_KEY = 'platform.settings';

    public function defaults(): array
    {
        return [
            'platform.name' => 'Hafez System',
            'organization.name' => '',
            'organization.description' => '',
            'organization.address' => '',
            'organization.phone' => '',
            'organization.email' => '',
            'platform.footer' => '',
            'certificate.issuer' => '',
            'certificate.title' => 'شهادة تقدير',
            'certificate.footer' => '',
            'certificate.number_prefix' => 'CERT',
            'certificate.show_qr' => '1',
            'certificate.verification_text' => '',
        ];
    }

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $settings = $this->defaults();

            PlatformSetting::query()->get(['key', 'value'])->each(function (PlatformSetting $setting) use (&$settings): void {
                $settings[$setting->key] = $setting->value ?? '';
            });

            return $settings;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            [$group] = explode('.', $key, 2);
            PlatformSetting::query()->updateOrCreate(
                ['key' => $key],
                ['group' => $group, 'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value],
            );
        }

        Cache::forget(self::CACHE_KEY);
    }
}

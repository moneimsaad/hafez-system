<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePlatformSettingsRequest;
use App\Models\PlatformSetting;
use App\Services\AuditLogService;
use App\Services\PlatformSettingsService;
use Illuminate\Support\Facades\Gate;

class PlatformSettingsController extends Controller
{
    public function edit(PlatformSettingsService $settings)
    {
        Gate::authorize('viewAny', PlatformSetting::class);

        return view('settings.edit', ['settings' => $settings->all()]);
    }

    public function update(UpdatePlatformSettingsRequest $request, PlatformSettingsService $settings, AuditLogService $audit)
    {
        Gate::authorize('update', new PlatformSetting);

        $data = $request->validated();
        $mapped = [
            'platform.name' => $data['platform_name'],
            'organization.name' => $data['organization_name'] ?? '',
            'organization.description' => $data['organization_description'] ?? '',
            'organization.address' => $data['organization_address'] ?? '',
            'organization.phone' => $data['organization_phone'] ?? '',
            'organization.email' => $data['organization_email'] ?? '',
            'platform.footer' => $data['platform_footer'] ?? '',
            'certificate.issuer' => $data['certificate_issuer'] ?? '',
            'certificate.title' => $data['certificate_title'],
            'certificate.footer' => $data['certificate_footer'] ?? '',
            'certificate.number_prefix' => $data['certificate_number_prefix'],
            'certificate.show_qr' => $request->boolean('certificate_show_qr'),
            'certificate.verification_text' => $data['certificate_verification_text'] ?? '',
        ];

        $old = $settings->all();
        $settings->update($mapped);

        foreach (array_keys($mapped) as $key) {
            $setting = PlatformSetting::query()->where('key', $key)->first();
            if ($setting) {
                $audit->record($request->user()->id, 'updated', $setting, null, [$key => $old[$key] ?? null], [$key => $setting->value]);
            }
        }

        return redirect()->route('platform-admin.settings.edit')->with('status', 'تم تحديث إعدادات المنصة بنجاح.');
    }
}

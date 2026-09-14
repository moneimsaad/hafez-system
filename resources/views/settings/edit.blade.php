<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="small text-success fw-bold mb-1">إدارة المنصة</p>
            <h1 class="h3 mb-0">إعدادات المنصة</h1>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <x-ui.alert />
<form method="POST" action="{{ route('platform-admin.settings.update') }}" class="row g-4" novalidate data-hafez-submit>
            @csrf @method('PUT')
            <div class="col-12 col-xl-6">
                <div class="card hafez-card border-0 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">بيانات المنصة والجهة</h2>
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label" for="platform_name">اسم المنصة</label><input class="form-control" id="platform_name" name="platform_name" value="{{ old('platform_name', $settings['platform.name']) }}" required>@error('platform_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><label class="form-label" for="organization_name">اسم الجهة</label><input class="form-control" id="organization_name" name="organization_name" value="{{ old('organization_name', $settings['organization.name']) }}"></div>
                            <div class="col-12"><label class="form-label" for="organization_description">نبذة الجهة</label><textarea class="form-control" id="organization_description" name="organization_description" rows="3">{{ old('organization_description', $settings['organization.description']) }}</textarea></div>
                            <div class="col-12"><label class="form-label" for="organization_address">العنوان</label><input class="form-control" id="organization_address" name="organization_address" value="{{ old('organization_address', $settings['organization.address']) }}"></div>
                            <div class="col-md-6"><label class="form-label" for="organization_phone">الهاتف</label><input class="form-control" id="organization_phone" name="organization_phone" value="{{ old('organization_phone', $settings['organization.phone']) }}"></div>
                            <div class="col-md-6"><label class="form-label" for="organization_email">البريد الإلكتروني</label><input type="email" class="form-control" id="organization_email" name="organization_email" value="{{ old('organization_email', $settings['organization.email']) }}">@error('organization_email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><label class="form-label" for="platform_footer">تذييل المنصة</label><input class="form-control" id="platform_footer" name="platform_footer" value="{{ old('platform_footer', $settings['platform.footer']) }}"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="card hafez-card border-0 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">إعدادات الشهادات</h2>
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label" for="certificate_issuer">الجهة المصدرة</label><input class="form-control" id="certificate_issuer" name="certificate_issuer" value="{{ old('certificate_issuer', $settings['certificate.issuer']) }}"></div>
                            <div class="col-12"><label class="form-label" for="certificate_title">عنوان الشهادة</label><input class="form-control" id="certificate_title" name="certificate_title" value="{{ old('certificate_title', $settings['certificate.title']) }}" required></div>
                            <div class="col-12"><label class="form-label" for="certificate_footer">النص الختامي</label><textarea class="form-control" id="certificate_footer" name="certificate_footer" rows="3">{{ old('certificate_footer', $settings['certificate.footer']) }}</textarea></div>
                            <div class="col-md-6"><label class="form-label" for="certificate_number_prefix">بادئة رقم الشهادة</label><input class="form-control" id="certificate_number_prefix" name="certificate_number_prefix" value="{{ old('certificate_number_prefix', $settings['certificate.number_prefix']) }}" required></div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="certificate_show_qr" name="certificate_show_qr" value="1" @checked(old('certificate_show_qr', $settings['certificate.show_qr'])=='1' )><label class="form-check-label" for="certificate_show_qr">إظهار QR في الشهادة</label></div>
                            </div>
                            <div class="col-12"><label class="form-label" for="certificate_verification_text">نص التحقق</label><textarea class="form-control" id="certificate_verification_text" name="certificate_verification_text" rows="3">{{ old('certificate_verification_text', $settings['certificate.verification_text']) }}</textarea></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end"><button class="btn btn-success px-5" type="submit">حفظ الإعدادات</button></div>
        </form>
    </div>
</x-app-layout>

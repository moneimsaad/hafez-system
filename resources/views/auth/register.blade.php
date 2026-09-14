<x-guest-layout>
    <section class="hafez-auth-flow" aria-labelledby="register-title">
        <div class="hafez-auth-flow__intro">
            <span class="hafez-auth-flow__eyebrow">حساب منظم</span>
            <h2 id="register-title">ابدأ إدارة مسابقاتك بثقة</h2>
            <p>أنشئ حساب الجهة المنظمة، ثم فعّل بريدك الإلكتروني برمز تحقق قصير.</p>
        </div>

        <x-ui.validation-errors />

        <form method="POST" action="{{ route('organizer.register.store') }}" data-auth-submit data-loading-text="جاري إنشاء الحساب..." data-password-form>
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label for="name" class="form-label">الاسم</label>
                    <input id="name" class="form-control @error('name') is-invalid @enderror" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" aria-invalid="@error('name') true @else false @enderror">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label for="organization_name" class="form-label">اسم الجهة</label>
                    <input id="organization_name" class="form-control @error('organization_name') is-invalid @enderror" type="text" name="organization_name" value="{{ old('organization_name') }}" required aria-invalid="@error('organization_name') true @else false @enderror">
                    @error('organization_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label for="username" class="form-label">اسم المستخدم</label>
                    <input id="username" class="form-control @error('username') is-invalid @enderror" type="text" name="username" value="{{ old('username') }}" required pattern="[a-z0-9_-]+" dir="ltr" autocomplete="username" aria-describedby="username-help" aria-invalid="@error('username') true @else false @enderror">
                    <div id="username-help" class="form-text">أحرف إنجليزية صغيرة وأرقام وشرطة سفلية. سيظهر في رابط التسجيل العام.</div>
                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" aria-invalid="@error('email') true @else false @enderror">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label for="phone" class="form-label">رقم الهاتف</label>
                    <input id="phone" class="form-control @error('phone') is-invalid @enderror" type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel" aria-describedby="phone-help" aria-invalid="@error('phone') true @else false @enderror">
                    <div id="phone-help" class="form-text">يستخدم للتواصل وإدارة الحساب.</div>
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <div class="hafez-password-field">
                        <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required minlength="8" autocomplete="new-password" data-password-input data-password-role="primary" aria-describedby="password-help password-error" aria-invalid="@error('password') true @else false @enderror">
                        <button type="button" class="hafez-password-toggle" data-password-toggle data-password-target="password" data-show-label="إظهار" data-hide-label="إخفاء" aria-label="إظهار كلمة المرور" aria-controls="password" aria-pressed="false"><span data-password-toggle-label>إظهار</span></button>
                    </div>
                    <div id="password-help" class="form-text hafez-password-guidance">استخدم 8 أحرف على الأقل، ويفضّل الجمع بين الحروف والأرقام والرموز.</div>
                    @error('password') <div id="password-error" class="invalid-feedback" role="alert">{{ $message }}</div> @else <div id="password-error" class="visually-hidden">كلمة المرور مطلوبة.</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="password_confirmation" class="form-label">تأكيد كلمة المرور</label>
                    <div class="hafez-password-field">
                        <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password" data-password-confirmation aria-describedby="password-confirmation-error">
                        <button type="button" class="hafez-password-toggle" data-password-toggle data-password-target="password_confirmation" data-show-label="إظهار" data-hide-label="إخفاء" aria-label="إظهار تأكيد كلمة المرور" aria-controls="password_confirmation" aria-pressed="false"><span data-password-toggle-label>إظهار</span></button>
                    </div>
                    <div id="password-confirmation-error" class="hafez-field-error" data-password-match-error role="alert" aria-live="polite" hidden>تأكيد كلمة المرور غير مطابق.</div>
                </div>
            </div>

            <div class="hafez-auth-flow__actions">
                <a class="hafez-auth-flow__link" href="{{ route('login') }}">لديك حساب؟ سجّل الدخول</a>
                <button class="btn btn-success hafez-auth-flow__submit" type="submit">إنشاء الحساب</button>
            </div>
        </form>
    </section>
</x-guest-layout>

<x-guest-layout>
    <section class="hafez-auth-flow" aria-labelledby="login-title">
        <div class="hafez-auth-flow__intro">
            <span class="hafez-auth-flow__eyebrow">مرحباً بعودتك</span>
            <h2 id="login-title">تسجيل الدخول</h2>
            <p>أدخل بياناتك للوصول إلى لوحة إدارة مسابقات حفظ القرآن الكريم.</p>
        </div>

        <x-ui.validation-errors />
        <x-auth-session-status class="alert alert-success mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" data-auth-submit data-loading-text="جاري تسجيل الدخول...">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">البريد الإلكتروني</label>
                <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" aria-describedby="login-email-error" aria-invalid="@error('email') true @else false @enderror">
                @error('email') <div id="login-email-error" class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">كلمة المرور</label>
                <div class="hafez-password-field">
                    <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="current-password" data-password-input aria-describedby="login-password-help login-password-error" aria-invalid="@error('password') true @else false @enderror">
                    <button type="button" class="hafez-password-toggle" data-password-toggle data-password-target="password" data-show-label="إظهار" data-hide-label="إخفاء" aria-label="إظهار كلمة المرور" aria-controls="password" aria-pressed="false"><span data-password-toggle-label>إظهار</span></button>
                </div>
                <div id="login-password-help" class="form-text">استخدم كلمة المرور التي اخترتها عند إنشاء الحساب.</div>
                @error('password') <div id="login-password-error" class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
            </div>
            <div class="hafez-auth-remember mb-4">
                <input id="remember_me" type="checkbox" class="form-check-input" name="remember" value="1">
                <label for="remember_me" class="form-check-label">تذكرني على هذا الجهاز</label>
            </div>
            <div class="hafez-auth-flow__actions">
                <a class="hafez-auth-flow__link" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
                <button class="btn btn-success hafez-auth-flow__submit" type="submit">تسجيل الدخول</button>
            </div>
        </form>

        @if (Route::has('organizer.register'))
            <p class="hafez-auth-flow__secondary-action">ليس لديك حساب؟ <a class="hafez-auth-flow__link" href="{{ route('organizer.register') }}">إنشاء حساب منظم</a></p>
        @endif
    </section>
</x-guest-layout>

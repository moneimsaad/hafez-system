<x-guest-layout>
    <section class="hafez-auth-flow" aria-labelledby="forgot-password-title">
        <div class="hafez-auth-flow__intro">
            <span class="hafez-auth-flow__eyebrow">استعادة الوصول</span>
            <h2 id="forgot-password-title">هل نسيت كلمة المرور؟</h2>
            <p>أدخل بريدك الإلكتروني. إذا كان الحساب مسجلاً، سنرسل رمز تحقق من أربعة أرقام.</p>
        </div>

        <x-auth-session-status class="alert alert-success mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" data-auth-submit data-loading-text="جاري إرسال الرمز...">
            @csrf
            <div>
                <label for="email" class="form-label">البريد الإلكتروني</label>
                <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" aria-describedby="forgot-password-help forgot-password-error" aria-invalid="@error('email') true @else false @enderror">
                <div id="forgot-password-help" class="form-text">لأمان الحسابات، ستكون الرسالة نفسها سواء كان البريد مسجلاً أم لا.</div>
                @error('email') <div id="forgot-password-error" class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
            </div>

            <div class="hafez-auth-flow__actions">
                <a class="hafez-auth-flow__link" href="{{ route('login') }}">العودة لتسجيل الدخول</a>
                <button class="btn btn-success hafez-auth-flow__submit" type="submit">إرسال الرمز</button>
            </div>
        </form>
    </section>
</x-guest-layout>

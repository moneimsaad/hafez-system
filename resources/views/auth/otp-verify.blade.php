<x-guest-layout>
    <section class="hafez-auth-flow hafez-otp-flow" aria-labelledby="otp-title">
        <div class="hafez-auth-flow__intro">
            <span class="hafez-auth-flow__eyebrow">تأكيد آمن</span>
            <h2 id="otp-title">{{ $purpose === 'registration' ? 'فعّل حسابك' : 'تحقق من هويتك' }}</h2>
            <p>{{ $purpose === 'registration' ? 'أرسلنا رمزاً من أربعة أرقام إلى بريدك الإلكتروني لإتمام إنشاء الحساب.' : 'أدخل الرمز المرسل إلى بريدك الإلكتروني للمتابعة بأمان.' }}</p>
        </div>

        <div role="status" aria-live="polite">
            <x-auth-session-status class="alert alert-success mb-4" :status="session('status')" />
        </div>

        @if($purpose === 'registration' && $email)
            <p class="hafez-otp-flow__email" dir="ltr">{{ $email }}</p>
        @endif

        <form method="POST" action="{{ route('otp.verify') }}" data-auth-submit data-loading-text="جاري التحقق...">
            @csrf
            <div>
                <label for="code" class="form-label">رمز التحقق</label>
                <input id="code" class="form-control hafez-otp-flow__code @error('code') is-invalid @enderror" type="text" name="code" inputmode="numeric" enterkeyhint="done" pattern="[0-9]{4}" maxlength="4" autocomplete="one-time-code" autocapitalize="off" spellcheck="false" required autofocus dir="ltr" aria-describedby="otp-code-help otp-delivery-help otp-code-error" aria-invalid="@error('code') true @else false @enderror">
                <div id="otp-code-help" class="form-text">أدخل الأرقام الأربعة كما وصلت إليك. تنتهي صلاحية الرمز خلال {{ config('otp.expires_minutes') }} دقائق.</div>
                <div id="otp-delivery-help" class="form-text hafez-otp-flow__delivery-help" role="note">لم يصلك الرمز؟ راجع مجلد الرسائل غير المرغوب فيها أو البريد المهمل، ثم اطلب رمزاً جديداً عند انتهاء المؤقت.</div>
                @error('code') <div id="otp-code-error" class="invalid-feedback" role="alert" aria-live="assertive">{{ $message }}</div> @else <div id="otp-code-error" class="visually-hidden">رمز التحقق غير صحيح.</div> @enderror
            </div>

            @error('resend')
                <div id="otp-resend-error" class="alert alert-danger mt-3 mb-0" role="alert" aria-live="assertive">{{ $message }}</div>
            @enderror

            <div class="hafez-auth-flow__actions">
                <button class="hafez-auth-flow__link hafez-otp-flow__resend" type="submit" formaction="{{ route('otp.resend') }}" formnovalidate data-otp-resend data-resend-available-at="{{ $resendAvailableAt }}" data-no-loading aria-describedby="otp-delivery-help">
                    <span data-resend-label aria-live="polite">إرسال رمز جديد</span>
                </button>
                <button class="btn btn-success hafez-auth-flow__submit" type="submit">تحقق</button>
            </div>
            <p class="hafez-otp-flow__resend-status" data-otp-resend-status aria-live="polite">يمكنك طلب رمز جديد بعد انتهاء المؤقت إذا لم يصلك البريد.</p>
        </form>
    </section>
</x-guest-layout>

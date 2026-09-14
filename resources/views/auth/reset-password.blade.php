<x-guest-layout>
    <section class="hafez-auth-flow" aria-labelledby="reset-password-title">
        <div class="hafez-auth-flow__intro">
            <span class="hafez-auth-flow__eyebrow">تم التحقق</span>
            <h2 id="reset-password-title">عيّن كلمة مرور جديدة</h2>
            <p>اختر كلمة مرور قوية وفريدة لحماية حسابك.</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" data-auth-submit data-loading-text="جاري حفظ كلمة المرور..." data-password-form>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="mb-3">
                <label for="password" class="form-label">كلمة المرور الجديدة</label>
                <div class="hafez-password-field">
                    <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required minlength="8" autofocus autocomplete="new-password" data-password-input data-password-role="primary" aria-describedby="password-help password-error" aria-invalid="@error('password') true @else false @enderror">
                    <button type="button" class="hafez-password-toggle" data-password-toggle data-password-target="password" data-show-label="إظهار" data-hide-label="إخفاء" aria-label="إظهار كلمة المرور" aria-controls="password" aria-pressed="false"><span data-password-toggle-label>إظهار</span></button>
                </div>
                <div id="password-help" class="form-text hafez-password-guidance">استخدم 8 أحرف على الأقل، ويفضّل الجمع بين الحروف والأرقام والرموز.</div>
                @error('password') <div id="password-error" class="invalid-feedback" role="alert">{{ $message }}</div> @else <div id="password-error" class="visually-hidden">كلمة المرور مطلوبة.</div> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="form-label">تأكيد كلمة المرور الجديدة</label>
                <div class="hafez-password-field">
                    <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password" data-password-confirmation aria-describedby="password-confirmation-error">
                    <button type="button" class="hafez-password-toggle" data-password-toggle data-password-target="password_confirmation" data-show-label="إظهار" data-hide-label="إخفاء" aria-label="إظهار تأكيد كلمة المرور" aria-controls="password_confirmation" aria-pressed="false"><span data-password-toggle-label>إظهار</span></button>
                </div>
                <div id="password-confirmation-error" class="hafez-field-error" data-password-match-error role="alert" aria-live="polite" hidden>تأكيد كلمة المرور غير مطابق.</div>
            </div>

            <div class="hafez-auth-flow__actions hafez-auth-flow__actions--end">
                <button class="btn btn-success hafez-auth-flow__submit" type="submit">حفظ كلمة المرور</button>
            </div>
        </form>
    </section>
</x-guest-layout>

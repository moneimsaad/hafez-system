<x-guest-layout>
    <section class="hafez-auth-flow" aria-labelledby="confirm-password-title">
        <div class="hafez-auth-flow__intro">
            <span class="hafez-auth-flow__eyebrow">حماية إضافية</span>
            <h2 id="confirm-password-title">تأكيد كلمة المرور</h2>
            <p>أدخل كلمة المرور الحالية للمتابعة إلى هذه المنطقة الآمنة.</p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" data-auth-submit data-loading-text="جاري التحقق...">
            @csrf
            <div>
                <label for="password" class="form-label">كلمة المرور الحالية</label>
                <div class="hafez-password-field">
                    <input id="password" class="form-control @error('password') is-invalid @enderror" type="password" name="password" required autocomplete="current-password" data-password-input aria-describedby="confirm-password-error" aria-invalid="@error('password') true @else false @enderror">
                    <button type="button" class="hafez-password-toggle" data-password-toggle data-password-target="password" data-show-label="إظهار" data-hide-label="إخفاء" aria-label="إظهار كلمة المرور" aria-controls="password" aria-pressed="false"><span data-password-toggle-label>إظهار</span></button>
                </div>
                @error('password') <div id="confirm-password-error" class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
            </div>

            <div class="hafez-auth-flow__actions hafez-auth-flow__actions--end">
                <button class="btn btn-success hafez-auth-flow__submit" type="submit">تأكيد والمتابعة</button>
            </div>
        </form>
    </section>
</x-guest-layout>

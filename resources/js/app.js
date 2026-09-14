import './bootstrap';
import * as bootstrap from 'bootstrap';
import flatpickr from 'flatpickr';
import { Arabic } from 'flatpickr/dist/l10n/ar.js';
import 'flatpickr/dist/flatpickr.min.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.bootstrap = bootstrap;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const appShell = document.body;
    if (sidebarToggle && window.matchMedia('(min-width: 992px)').matches) {
        const collapsed = localStorage.getItem('hafez-sidebar-collapsed') === 'true';
        appShell.classList.toggle('hafez-sidebar-collapsed', collapsed);
        sidebarToggle.classList.toggle('is-collapsed', collapsed);
        sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
        sidebarToggle.addEventListener('click', () => {
            const next = !appShell.classList.contains('hafez-sidebar-collapsed');
            appShell.classList.toggle('hafez-sidebar-collapsed', next);
            sidebarToggle.classList.toggle('is-collapsed', next);
            localStorage.setItem('hafez-sidebar-collapsed', String(next));
            sidebarToggle.setAttribute('aria-expanded', String(!next));
        });
    }

    document.querySelectorAll('[data-hafez-date]').forEach((element) => {
        const datetime = element.dataset.hafezDate === 'datetime';
        flatpickr(element, {
            locale: Arabic,
            enableTime: datetime,
            time_24hr: true,
            dateFormat: datetime ? 'Y-m-d\\TH:i' : 'Y-m-d',
            altInput: true,
            altFormat: datetime ? 'd/m/Y - H:i' : 'd/m/Y',
            allowInput: false,
        });
    });

    document.querySelectorAll('form[data-hafez-submit]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const button = event.submitter || form.querySelector('button[type="submit"]');
            if (!button || button.disabled || button.dataset.noLoading === 'true') {
                return;
            }

            // Disabled submit buttons are omitted from the native form payload.
            // Preserve the clicked action (e.g. select-all/clear-all) before
            // disabling it for the loading state.
            if (button.name) {
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = button.name;
                actionInput.value = button.value;
                form.appendChild(actionInput);
            }

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.dataset.originalLabel = button.innerHTML;
            button.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${button.dataset.loadingText || 'جاري الحفظ...'}`;
        });
    });

    document.querySelectorAll('form[data-auth-submit]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const button = event.submitter;
            if (!button || button.dataset.noLoading === 'true' || button.disabled) {
                return;
            }

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.dataset.originalLabel = button.innerHTML;
            button.innerHTML = `<span class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>${button.dataset.loadingText || form.dataset.loadingText || 'جاري المتابعة...'}`;
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordTarget);
        const label = button.querySelector('[data-password-toggle-label]');
        if (!input) return;

        button.addEventListener('click', () => {
            const isVisible = input.type === 'password';
            input.type = isVisible ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(isVisible));
            button.setAttribute('aria-label', isVisible
                ? (button.dataset.hideLabel || 'إخفاء كلمة المرور')
                : (button.dataset.showLabel || 'إظهار كلمة المرور'));
            if (label) {
                label.textContent = isVisible
                    ? (button.dataset.hideLabel || 'إخفاء')
                    : (button.dataset.showLabel || 'إظهار');
            }
        });
    });

    document.querySelectorAll('[data-password-form]').forEach((form) => {
        const password = form.querySelector('[data-password-role="primary"]');
        const confirmation = form.querySelector('[data-password-confirmation]');
        const feedback = form.querySelector('[data-password-match-error]');
        if (!password || !confirmation) return;

        const updateMatchState = () => {
            const mismatch = confirmation.value !== '' && password.value !== confirmation.value;
            confirmation.setCustomValidity(mismatch ? 'تأكيد كلمة المرور غير مطابق.' : '');
            confirmation.classList.toggle('is-invalid', mismatch);
            confirmation.setAttribute('aria-invalid', String(mismatch));
            if (feedback) {
                feedback.hidden = !mismatch;
            }
        };

        password.addEventListener('input', updateMatchState);
        confirmation.addEventListener('input', updateMatchState);
        form.addEventListener('submit', updateMatchState);
        updateMatchState();
    });

    document.querySelectorAll('.hafez-otp-flow__code').forEach((input) => {
        const arabicDigits = '٠١٢٣٤٥٦٧٨٩';
        const persianDigits = '۰۱۲۳۴۵۶۷۸۹';
        const normalizeDigits = (value) => value
            .replace(/[٠-٩]/g, (digit) => String(arabicDigits.indexOf(digit)))
            .replace(/[۰-۹]/g, (digit) => String(persianDigits.indexOf(digit)))
            .replace(/\D/g, '')
            .slice(0, 4);

        const focusInput = () => {
            if (document.activeElement === document.body || document.activeElement === document.documentElement) {
                window.requestAnimationFrame(() => input.focus());
            }
        };

        input.addEventListener('input', () => {
            const previousValue = input.value;
            input.value = normalizeDigits(input.value);
            if (input.value !== previousValue) {
                input.classList.remove('is-invalid');
                input.setAttribute('aria-invalid', 'false');
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.ctrlKey || event.metaKey || event.altKey
                || ['Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)
                || /^[0-9٠-٩۰-۹]$/.test(event.key)) {
                return;
            }

            event.preventDefault();
        });

        focusInput();
    });

    document.querySelectorAll('[data-otp-resend]').forEach((button) => {
        const availableAt = Number(button.dataset.resendAvailableAt || 0);
        const label = button.querySelector('[data-resend-label]');
        const status = document.querySelector('[data-otp-resend-status]');
        const update = () => {
            const remaining = Math.max(0, availableAt - Math.floor(Date.now() / 1000));
            button.disabled = remaining > 0;
            if (label) {
                label.textContent = remaining > 0 ? `إرسال رمز جديد بعد ${remaining} ث` : 'إرسال رمز جديد';
            }
            if (status && remaining === 0) {
                status.textContent = 'يمكنك الآن طلب رمز تحقق جديد.';
            }
            return remaining;
        };

        if (update() > 0) {
            const timer = window.setInterval(() => {
                if (update() === 0) {
                    window.clearInterval(timer);
                }
            }, 1000);
        }
    });

    document.querySelectorAll('[data-autosave-row]').forEach((row) => {
        const inputs = [...row.querySelectorAll('[data-score-index]')];
        const status = row.querySelector('[data-row-status]');
        const error = row.querySelector('[data-row-error]');
        const edit = row.querySelector('[data-row-edit]');
        const cancel = row.querySelector('[data-row-cancel]');
        let timer = null;
        let controller = null;
        let sequence = 0;
        let saved = inputs.map((input) => input.value);
        const setLocked = (locked) => inputs.forEach((input) => { input.readOnly = locked; });
        const setStatus = (label, style) => { status.textContent = label; status.className = `badge text-bg-${style}`; };
        const clearFieldErrors = () => inputs.forEach((input) => { input.classList.remove('is-invalid'); const target = input.nextElementSibling; if (target?.dataset.scoreError !== undefined) target.textContent = ''; });
        const validate = () => {
            clearFieldErrors();
            const values = inputs.map((input) => input.value.trim());
            if (values.every((value) => value === '')) return {state: 'empty', values};
            let invalid = false;
            inputs.forEach((input, index) => {
                const value = values[index];
                const min = Number(input.dataset.min ?? input.min ?? 0);
                const max = Number(input.dataset.max ?? input.max);
                let message = '';
                if (value !== '') {
                    const number = Number(value);
                    if (!Number.isFinite(number)) message = 'يرجى إدخال درجة صحيحة.';
                    else if (number < min) message = `الدرجة لا يمكن أن تقل عن ${min}.`;
                    else if (Number.isFinite(max) && number > max) message = `الدرجة يجب ألا تتجاوز ${max}.`;
                }
                if (message) { invalid = true; input.classList.add('is-invalid'); const target = input.nextElementSibling; if (target?.dataset.scoreError !== undefined) target.textContent = message; }
            });
            if (invalid) return {state: 'invalid', values};
            if (values.some((value) => value === '')) return {state: 'incomplete', values};
            return {state: 'valid', values};
        };
        const save = async (requestSequence) => {
            const result = validate();
            if (requestSequence !== sequence || result.state !== 'valid' || result.values.join('|') === saved.join('|')) return;
            if (controller) controller.abort();
            controller = new AbortController();
            setStatus('جاري الحفظ...', 'info'); error.textContent = ''; setLocked(true);
            const scores = {}; inputs.forEach((input) => { scores[input.dataset.scoreIndex] = { score: input.value }; });
            try {
                const response = await fetch(row.dataset.autosaveUrl, { method: 'PATCH', signal: controller.signal, headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''}, body: JSON.stringify({registration_id: row.dataset.registrationId, scores}) });
                if (response.status === 422) { const payload = await response.json(); throw {validation: payload.errors || {}}; }
                if (!response.ok) throw new Error();
                if (requestSequence !== sequence) return;
                const wasSaved = row.dataset.saved === 'true';
                saved = inputs.map((input) => input.value); row.dataset.saved = 'true'; setStatus('تم الحفظ', 'success'); error.textContent = ''; edit.disabled = false; edit.classList.remove('d-none'); cancel.classList.add('d-none'); setLocked(true);
                if (!wasSaved) {
                    const completed = document.querySelector('#bulk-completed-count');
                    const remaining = document.querySelector('#bulk-remaining-count');
                    if (completed && remaining) { completed.textContent = String(Number(completed.textContent) + 1); remaining.textContent = String(Math.max(0, Number(remaining.textContent) - 1)); }
                }
            } catch (exception) {
                if (exception.name === 'AbortError' || requestSequence !== sequence) return;
                setLocked(false); setStatus(exception.validation ? 'يرجى مراجعة الدرجات' : 'خطأ في الحفظ', 'danger'); error.textContent = exception.validation ? 'يرجى مراجعة القيم.' : 'تعذر حفظ التقييم. حاول مرة أخرى.';
                inputs.forEach((input) => { const key = `scores.${input.dataset.scoreIndex}.score`; input.classList.toggle('is-invalid', Boolean(exception.validation?.[key])); const target = input.nextElementSibling; if (target?.dataset.scoreError !== undefined) target.textContent = exception.validation?.[key]?.[0] || ''; });
            } finally {
                if (requestSequence === sequence && status.textContent === 'جاري الحفظ...') { setLocked(false); setStatus('خطأ في الحفظ', 'danger'); error.textContent = 'تعذر حفظ التقييم. حاول مرة أخرى.'; }
            }
        };
        inputs.forEach((input) => input.addEventListener('input', () => {
            clearTimeout(timer); sequence += 1; if (controller) controller.abort();
            const result = validate();
            if (result.state === 'empty') setStatus('غير مقيم', 'warning');
            else if (result.state === 'incomplete') setStatus('غير مكتمل', 'warning');
            else if (result.state === 'invalid') setStatus('يرجى مراجعة الدرجات', 'danger');
            else if (result.values.join('|') !== saved.join('|')) { setStatus('جاري الحفظ...', 'info'); const requestSequence = sequence; timer = setTimeout(() => save(requestSequence), 700); }
        }));
        edit?.addEventListener('click', () => { setLocked(false); edit.classList.add('d-none'); cancel.classList.remove('d-none'); status.textContent = 'جاري التعديل'; });
        cancel?.addEventListener('click', () => { inputs.forEach((input, index) => { input.value = saved[index]; }); setLocked(true); edit.classList.remove('d-none'); cancel.classList.add('d-none'); status.textContent = 'تم الحفظ'; error.textContent = ''; });
    });
});

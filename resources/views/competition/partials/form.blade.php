<form method="POST" action="{{ $formAction }}" class="card hafez-card border-0" novalidate data-hafez-submit>
    @csrf @if($formMethod !== 'POST') @method($formMethod) @endif
    <div class="card-body p-4 p-lg-5">
        <x-ui.validation-errors />
        <div class="row g-3">
            <div class="col-12">
                <x-ui.text-input name="title" label="عنوان المسابقة" :value="$competition?->title" :required="true" />
            </div>
            <div class="col-12">
                <details class="hafez-form-optional">
                    <summary>وصف المسابقة (اختياري)</summary>
                    <x-ui.textarea-input name="description" label="الوصف" :value="$competition?->description" rows="3" />
                </details>
            </div>
            <div class="col-12">
                <x-ui.textarea-input name="additional_terms" label="شروط وملاحظات التسجيل" :value="$competition?->additional_terms" rows="4" maxlength="5000" />
                <div class="form-text">اكتب أي شروط أو تعليمات يجب على المتقدم قراءتها قبل التسجيل في المسابقة.</div>
            </div>
            <div class="col-12">
                <h2 class="h6 text-success border-bottom pb-2">المواعيد</h2>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="registration_start_date">بداية التسجيل</label>
                <x-ui.date-input name="registration_start_date" label="بداية التسجيل" type="datetime" :value="$competition?->registration_start_date?->format('Y-m-d\\TH:i')" :required="true" />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="registration_end_date">نهاية التسجيل</label>
                <x-ui.date-input name="registration_end_date" label="نهاية التسجيل" type="datetime" :value="$competition?->registration_end_date?->format('Y-m-d\\TH:i')" :required="true" />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="exam_start_date">بداية الاختبارات</label>
                <x-ui.date-input name="exam_start_date" label="بداية الاختبارات" type="datetime" :value="$competition?->exam_start_date?->format('Y-m-d\\TH:i')" :required="true" />
            </div>
            <div class="col-md-6">
                <label class="form-label" for="exam_end_date">نهاية الاختبارات</label>
                <x-ui.date-input name="exam_end_date" label="نهاية الاختبارات" type="datetime" :value="$competition?->exam_end_date?->format('Y-m-d\\TH:i')" :required="true" />
            </div>
            <div class="col-12">
                <x-ui.text-input name="location" label="الموقع" :value="$competition?->location" :required="true" />
            </div>
            <div class="col-12">
                <div class="alert alert-info mb-0">يتم تحديد حالة المسابقة تلقائياً بناءً على المواعيد وتجهيز مستويات المسابقة.</div>
            </div>
            <div class="col-12">
                <h2 class="h6 text-success border-bottom pb-2">نطاق إتاحة المسابقة</h2>
                <p class="text-muted small">حدد كيف يمكن للمتسابقين الوصول إلى المسابقة والتسجيل فيها.</p>
                @php($scope = old('publication_scope', $competition?->publication_scope ?? 'unlisted'))
                <div class="row g-2">
                    @foreach(['unlisted'=>['خاص بالرابط فقط','لن تظهر في الصفحة الرئيسية ويمكنك مشاركة الرابط مباشرة.'],'governorate'=>['متاحة لسكان محافظة محددة','ستظهر في الصفحة الرئيسية لسكان المحافظة المختارة.'],'nationwide'=>['متاحة على مستوى الجمهورية','ستظهر في الصفحة الرئيسية ويمكن التسجيل من جميع المحافظات.']] as $value => [$label,$help])
                        <div class="col-md-4"><label class="border rounded p-3 d-block h-100"><input class="form-check-input ms-2" type="radio" name="publication_scope" value="{{ $value }}" @checked($scope === $value) data-scope-option> <strong>{{ $label }}</strong><span class="d-block small text-muted mt-2">{{ $help }}</span></label></div>
                    @endforeach
                </div>
                <div id="target-governorate-wrap" class="mt-3 {{ $scope === 'governorate' ? '' : 'd-none' }}">
                    <x-ui.select-input name="target_governorate" label="المحافظة المستهدفة" :options="array_combine(config('governorates'), config('governorates'))" :value="old('target_governorate', $competition?->target_governorate)" />
                </div>
            </div>
            @php($scoringRules = app(\App\Services\CompetitionScoringRulesService::class))
            @php($savedCriteria = $scoringRules->criteria($competition?->rules))
            @php($customScoring = old('custom_scoring', $savedCriteria !== []))
            @php($criteria = old('scoring_criteria', $savedCriteria ?: $scoringRules->defaultCriteria()))
            <div class="col-12">
                <section class="hafez-scoring-builder" data-scoring-builder data-default-criteria='@json($scoringRules->defaultCriteria())'>
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <h2 class="h6 text-success mb-1">إعدادات احتساب الدرجات</h2>
                            <p class="text-muted small mb-0">المعايير الافتراضية: الحفظ 50، التجويد 25، الأداء 25 (الإجمالي 100).</p>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="custom_scoring" name="custom_scoring" value="1" @checked($customScoring) data-scoring-toggle>
                            <label class="form-check-label fw-semibold" for="custom_scoring">استخدام إعدادات مخصصة</label>
                        </div>
                    </div>
                    <p class="small text-muted mt-3 mb-0 @if($customScoring) d-none @endif" data-scoring-default-summary>ستُطبّق المعايير الافتراضية تلقائياً على جميع التقييمات.</p>
                    <div class="hafez-scoring-builder__content mt-3 @unless($customScoring) d-none @endunless" data-scoring-content>
                        <div class="hafez-scoring-builder__table" role="group" aria-label="معايير احتساب الدرجات">
                            <div class="hafez-scoring-builder__header"><span>معيار التقييم</span><span>الدرجة القصوى</span><span class="visually-hidden">إزالة</span></div>
                            <div data-scoring-rows>
                                @foreach($criteria as $index => $criterion)
                                    <div class="hafez-scoring-builder__row" data-scoring-row>
                                        <input class="form-control @error("scoring_criteria.$index.name") is-invalid @enderror" name="scoring_criteria[{{ $index }}][name]" value="{{ data_get($criterion, 'name') }}" placeholder="مثال: الحفظ" maxlength="100" data-scoring-input @disabled(! $customScoring)>
                                        <input class="form-control text-start @error("scoring_criteria.$index.max_score") is-invalid @enderror" name="scoring_criteria[{{ $index }}][max_score]" value="{{ data_get($criterion, 'max_score') }}" placeholder="0" type="number" min="0.01" step="0.01" inputmode="decimal" dir="ltr" data-scoring-input @disabled(! $customScoring)>
                                        <button class="btn btn-outline-secondary btn-sm" type="button" data-remove-criterion aria-label="إزالة المعيار">إزالة</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @error('scoring_criteria')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                            <button class="btn btn-outline-success btn-sm" type="button" data-add-criterion>+ إضافة معيار جديد</button>
                            <span class="small text-muted">إجمالي الدرجات: <strong dir="ltr" data-scoring-total>0</strong></span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <div class="card-footer bg-transparent border-0 px-4 pb-4 d-flex flex-wrap justify-content-end gap-2">
        <a href="{{ $competition ? route('competitions.show', $competition) : route('competitions.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        <button class="btn btn-success" type="submit">{{ $submitLabel }}</button>
    </div>
</form>
<script>
document.querySelectorAll('[data-scope-option]').forEach((radio) => radio.addEventListener('change', () => document.getElementById('target-governorate-wrap')?.classList.toggle('d-none', radio.value !== 'governorate' || !radio.checked)));

document.querySelectorAll('[data-scoring-builder]').forEach((builder) => {
    const toggle = builder.querySelector('[data-scoring-toggle]');
    const content = builder.querySelector('[data-scoring-content]');
    const rows = builder.querySelector('[data-scoring-rows]');
    const total = builder.querySelector('[data-scoring-total]');
    const defaultSummary = builder.querySelector('[data-scoring-default-summary]');
    const defaults = JSON.parse(builder.dataset.defaultCriteria || '[]');
    const createRow = (criterion = {}) => {
        const row = document.createElement('div');
        row.className = 'hafez-scoring-builder__row';
        row.dataset.scoringRow = '';
        row.innerHTML = `<input class="form-control" placeholder="مثال: الحفظ" maxlength="100" data-scoring-input><input class="form-control text-start" placeholder="0" type="number" min="0.01" step="0.01" inputmode="decimal" dir="ltr" data-scoring-input><button class="btn btn-outline-secondary btn-sm" type="button" data-remove-criterion aria-label="إزالة المعيار">إزالة</button>`;
        row.querySelectorAll('input')[0].value = criterion.name || '';
        row.querySelectorAll('input')[1].value = criterion.max_score || '';
        rows.append(row);
        return row;
    };
    const refresh = () => {
        [...rows.querySelectorAll('[data-scoring-row]')].forEach((row, index) => {
            const inputs = row.querySelectorAll('input');
            inputs[0].name = `scoring_criteria[${index}][name]`;
            inputs[1].name = `scoring_criteria[${index}][max_score]`;
            inputs.forEach((input) => input.disabled = !toggle.checked);
        });
        content.classList.toggle('d-none', !toggle.checked);
        defaultSummary.classList.toggle('d-none', toggle.checked);
        const value = [...rows.querySelectorAll('input[type="number"]')].reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
        total.textContent = Number.isInteger(value) ? value : value.toFixed(2);
    };
    builder.querySelector('[data-add-criterion]').addEventListener('click', () => { createRow(); refresh(); });
    rows.addEventListener('click', (event) => {
        if (!event.target.matches('[data-remove-criterion]')) return;
        event.target.closest('[data-scoring-row]').remove();
        if (!rows.children.length) toggle.checked = false;
        refresh();
    });
    rows.addEventListener('input', refresh);
    toggle.addEventListener('change', () => {
        if (toggle.checked && !rows.children.length) defaults.forEach(createRow);
        refresh();
    });
    refresh();
});
</script>

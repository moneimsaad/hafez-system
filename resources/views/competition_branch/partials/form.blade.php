<form method="POST" action="{{ $formAction }}" class="card hafez-card border-0" novalidate data-hafez-submit>
    @csrf @if($formMethod !== 'POST') @method($formMethod) @endif<div class="card-body p-4 p-lg-5">
        <x-ui.validation-errors />
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="competition_id">المسابقة (اختياري)</label>
                <select class="form-select" id="competition_id" name="competition_id">
                    <option value="">بدون ربط بمسابقة حالياً</option>
                    @foreach($competitions as $competition)<option value="{{ $competition->id }}" @selected(old('competition_id', $branch?->competition_id ?? ($selectedCompetitionId ?? null)) == $competition->id)>{{ $competition->title }}</option>@endforeach
                </select>
                <div class="form-text">يمكنك إنشاء المستوى الآن وربطه بمسابقة لاحقاً.</div>
            </div>
            @if(!$branch)
            @php($assignedLevelIds = collect($assignedLevelIds ?? [])->map(fn ($id) => (int) $id)->all())
            <div class="col-12">
                <div class="border rounded-3 p-3 bg-light-subtle">
                    <h2 class="h6 mb-2">اختر مستوى للمسابقة</h2>
                    <p class="small text-muted mb-3">اختر مستوى من مستويات المنصة أو من مستوياتي، أو أنشئ مستوى خاصاً بك لإعادة استخدامه لاحقاً.</p>
                    <select class="form-select" name="competition_level_id">
                        <option value="">إنشاء مستوى جديد</option>
                        @if(($systemLevels ?? collect())->isNotEmpty())
                            <optgroup label="مستويات المنصة">
                                @foreach($systemLevels as $level)
                                    @php($assigned = in_array((int) $level->id, $assignedLevelIds, true))
                                    <option value="{{ $level->id }}" @disabled($assigned)>{{ $level->name }} — {{ $level->memorization_amount }}{{ $assigned ? ' (هذا المستوى مضاف بالفعل لهذه المسابقة)' : '' }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if(($organizerLevels ?? collect())->isNotEmpty())
                            <optgroup label="مستوياتي">
                                @foreach($organizerLevels as $level)
                                    @php($assigned = in_array((int) $level->id, $assignedLevelIds, true))
                                    <option value="{{ $level->id }}" @disabled($assigned)>{{ $level->name }} — {{ $level->memorization_amount }}{{ $assigned ? ' (هذا المستوى مضاف بالفعل لهذه المسابقة)' : '' }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    <div class="form-text">المستويات المضافة بالفعل معروضة للتوضيح ولا يمكن اختيارها مرة أخرى.</div>
                </div>
            </div>
            <div class="col-md-6"><x-ui.text-input name="name" label="اسم المستوى الجديد (عند الإنشاء)" :value="$branch?->name" :required="true" /></div>
            @else
            <div class="col-md-6"><x-ui.text-input name="name" label="اسم المستوى" :value="$branch?->name" :required="true" /></div>
            @endif
            <div class="col-12">
                <x-ui.textarea-input name="description" label="الوصف" :value="$branch?->description" rows="3" />
            </div>
            <div class="col-md-6">
                <x-ui.text-input name="memorization_amount" label="مقدار الحفظ" :value="$branch?->memorization_amount" :required="true" />
            </div>
            <div class="col-12">
                <h2 class="h6 text-success border-bottom pb-2 mt-2">إعدادات الفئة والدرجات</h2>
            </div>
            <div class="col-md-6">
                <x-ui.text-input name="min_age" label="الحد الأدنى للعمر (اختياري)" type="number" min="0" :value="$branch?->min_age" :required="false" />
                <div class="form-text mt-n2 mb-2">إما أن تترك الحقلين فارغين بدون شرط عمر، أو تحدد الحد الأدنى والحد الأقصى معاً.</div>
            </div>
            <div class="col-md-6">
                <x-ui.text-input name="max_age" label="الحد الأقصى للعمر (اختياري)" type="number" min="0" :value="$branch?->max_age" :required="false" />
                <div class="form-text mt-n2 mb-2">يجب تحديد الحدين معاً عند إضافة شرط عمر.</div>
            </div>
            <div class="col-md-4">
                <x-ui.text-input name="total_score" label="الدرجة الكلية" type="number" min="0" step="0.01" :value="$branch?->total_score" :required="true" />
                <div class="form-text mt-n2 mb-2">إجمالي الدرجات المتاحة للتقييم.</div>
            </div>
            <div class="col-md-4">
                <x-ui.text-input name="passing_score" label="درجة النجاح" type="number" min="0" step="0.01" :value="$branch?->passing_score" :required="true" />
                <div class="form-text mt-n2 mb-2">يجب ألا تتجاوز الدرجة الكلية.</div>
            </div>
            <div class="col-md-4">
            </div>
        </div>
    </div>
    <div class="card-footer bg-transparent border-0 px-4 pb-4 d-flex flex-wrap justify-content-end gap-2">
        <a href="{{ $branch ? route('competition-branches.show', $branch) : route('competition-branches.index') }}" class="btn btn-outline-secondary">إلغاء</a>
        <button class="btn btn-success" type="submit">{{ $submitLabel }}</button>
    </div>
</form>

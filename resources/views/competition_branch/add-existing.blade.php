<x-app-layout>
    <x-slot name="header">
        <div>
            <nav class="small text-muted mb-2" aria-label="مسار التنقل">
                <a href="{{ route('dashboard') }}">لوحة التحكم</a> / <a href="{{ route('competitions.index') }}">مسابقاتي</a> / <a href="{{ route('competitions.show', $competition) }}">{{ $competition->title }}</a> / <a href="{{ route('competition-branches.index', ['competition_id' => $competition->id]) }}">مستويات المسابقة</a> / إضافة مستوى
            </nav>
            <p class="small text-success fw-bold mb-1">إعداد المسابقة</p>
            <h1 class="h3 mb-1">إضافة مستوى للمسابقة</h1>
            <p class="text-muted mb-0">اختر مستوى موجوداً لإضافته إلى: <strong>{{ $competition->title }}</strong></p>
        </div>
    </x-slot>

    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">
                <x-ui.alert />
                <form method="POST" action="{{ route('competitions.levels.store-existing', $competition) }}" class="card hafez-card border-0" novalidate data-hafez-submit>
                    @csrf
                    <input type="hidden" name="competition_id" value="{{ $competition->id }}">
                    <div class="card-body p-4 p-lg-5">
                        <x-ui.validation-errors />
                        <p class="alert alert-info small">اختيار هذا المستوى لن يعدّل المستوى الأصلي، ويمكن استخدامه في مسابقات أخرى.</p>

                        @if($levels->isNotEmpty())
                            <div class="mb-4">
                                <label class="form-label" for="level-search">ابحث عن مستوى</label>
                                <input id="level-search" type="search" class="form-control" placeholder="ابحث عن مستوى..." autocomplete="off">
                            </div>
                            <div class="row g-3" id="available-levels">
                                @foreach(['system' => 'مستويات المنصة', 'organizer' => 'مستوياتي'] as $type => $heading)
                                    @if(($levels[$type] ?? collect())->isNotEmpty())
                                        <div class="col-12" data-level-group="{{ $type }}">
                                            <h2 class="h6 text-success border-bottom pb-2 mb-3">{{ $heading }}</h2>
                                            <div class="row g-3">
                                                @foreach($levels[$type] as $level)
                                                    <div class="col-12 col-md-6 level-option" data-level-search="{{ mb_strtolower($level->name.' '.$level->memorization_amount.' '.$level->description) }}">
                                                        <label class="card h-100 border level-choice p-3 cursor-pointer">
                                                            <span class="d-flex gap-3 align-items-start">
                                                                <input class="form-check-input mt-1" type="radio" name="competition_level_id" value="{{ $level->id }}" data-min-age="{{ $level->default_min_age }}" data-max-age="{{ $level->default_max_age }}" data-total-score="{{ $level->default_total_score }}" data-passing-score="{{ $level->default_passing_score }}" @checked(old('competition_level_id') == $level->id) required>
                                                                <span>
                                                                    <strong class="d-block">{{ $level->name }}</strong>
                                                                    <span class="small text-muted d-block">مقدار الحفظ: {{ $level->memorization_amount }}</span>
                                                                    @if($level->description)<span class="small text-muted d-block mt-1">{{ $level->description }}</span>@endif
                                                                    <span class="badge text-bg-light border mt-2">{{ $type === 'system' ? 'مستوى منصة' : 'مستوى خاص بي' }}</span>
                                                                </span>
                                                            </span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            @error('competition_level_id')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror
                        @else
                            <x-ui.empty-state message="لا توجد مستويات متاحة للإضافة حالياً." :action="route('competition-branches.create', ['competition_id' => $competition->id])" action-label="إنشاء مستوى جديد" />
                        @endif

                        @if($levels->isNotEmpty())
                            <div class="row g-3 mt-2">
                                <div class="col-12"><h2 class="h6 text-success border-bottom pb-2">إعدادات هذا المستوى في المسابقة</h2></div>
                                <div class="col-md-6"><x-ui.text-input name="min_age" label="العمر الأدنى" type="number" min="0" :value="old('min_age')" :required="true" /></div>
                                <div class="col-md-6"><x-ui.text-input name="max_age" label="العمر الأقصى" type="number" min="0" :value="old('max_age')" :required="true" /></div>
                                <div class="col-md-6"><x-ui.text-input name="total_score" label="الدرجة الكلية" type="number" min="0" step="0.01" :value="old('total_score')" :required="true" /></div>
                                <div class="col-md-6"><x-ui.text-input name="passing_score" label="درجة النجاح" type="number" min="0" step="0.01" :value="old('passing_score')" :required="true" /></div>
                            </div>
                        @endif
                    </div>
                    @if($levels->isNotEmpty())
                        <div class="card-footer bg-transparent border-0 px-4 pb-4 d-flex flex-wrap justify-content-end gap-2">
                            <a href="{{ route('competition-branches.index', ['competition_id' => $competition->id]) }}" class="btn btn-outline-secondary">إلغاء</a>
                            <button class="btn btn-success" type="submit">إضافة المستوى للمسابقة</button>
                        </div>
                    @endif
                </form>
                @if($levels->isNotEmpty())
                    <div id="no-search-results" class="alert alert-light border mt-3 d-none">لا توجد مستويات مطابقة للبحث.</div>
                @endif
            </div>
        </div>
    </div>
    @if($levels->isNotEmpty())
        <script>
            const levelSearch = document.getElementById('level-search');
            levelSearch?.addEventListener('input', () => {
                const term = levelSearch.value.trim().toLocaleLowerCase('ar');
                let visible = 0;
                document.querySelectorAll('.level-option').forEach(option => {
                    const matches = !term || option.dataset.levelSearch.includes(term);
                    option.classList.toggle('d-none', !matches);
                    visible += matches ? 1 : 0;
                });
                document.querySelectorAll('[data-level-group]').forEach(group => group.classList.toggle('d-none', !group.querySelector('.level-option:not(.d-none)')));
                document.getElementById('no-search-results').classList.toggle('d-none', visible !== 0);
            });

            document.querySelectorAll('input[name="competition_level_id"]').forEach(level => {
                level.addEventListener('change', () => {
                    if (!level.checked) return;
                    const fields = {
                        min_age: level.dataset.minAge,
                        max_age: level.dataset.maxAge,
                        total_score: level.dataset.totalScore,
                        passing_score: level.dataset.passingScore,
                    };
                    Object.entries(fields).forEach(([name, value]) => {
                        const field = document.querySelector(`[name="${name}"]`);
                        if (field) field.value = value ?? '';
                    });
                });
            });
        </script>
    @endif
</x-app-layout>

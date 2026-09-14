<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><p class="small text-success fw-bold mb-1">تفاصيل اللجنة</p><h1 class="h3 mb-0">{{ $committee->name }}</h1></div>
            <x-ui.action-buttons>
                <a href="{{ route('committees.edit', $committee) }}" class="btn btn-success">تعديل</a>
                @if(auth()->user()->role === 'Platform Admin' || $committee->competition?->created_by === auth()->id() || $committee->committeeJudges->contains('judge_id', auth()->id()))
                    <a href="{{ route('committees.evaluations.bulk', $committee) }}" class="btn btn-success">إدخال التقييمات</a>
                @endif
                <a href="{{ route('evaluations.index', ['competition_id' => $committee->competition_id, 'branch_id' => $committee->branch_id]) }}" class="btn btn-outline-success">عرض التقييمات</a>
                <a href="{{ route('committees.index') }}" class="btn btn-outline-secondary">العودة</a>
            </x-ui.action-buttons>
        </div>
    </x-slot>

    <div class="container-fluid py-4 px-3 px-lg-4">
        <x-ui.alert />
        <x-ui.validation-errors />
        <div class="card hafez-card border-0 mb-4"><div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h2 class="h5 mb-0">معلومات اللجنة</h2><span class="badge text-bg-light border">أنشئت في {{ $committee->created_at?->format('Y-m-d') }}</span></div>
            <div class="row g-3">
                <div class="col-md-6"><small class="text-muted d-block">المسابقة</small><strong>{{ $committee->competition?->title }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">مستوى المسابقة</small><strong>{{ $committee->competitionBranch?->name }} — {{ $committee->competitionBranch?->memorization_amount }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">موعد الاختبار</small><strong>{{ optional($committee->exam_date)->format('Y-m-d H:i') }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">الموقع</small><strong>{{ $committee->location ?: 'غير محدد' }}</strong></div>
            </div>
        </div></div>

        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <div class="card hafez-card border-0 h-100"><div class="card-body p-4">
                    <div class="mb-4"><h2 class="h5 mb-1">الحكام</h2><p class="text-muted small mb-0">أضف حكاماً بالاسم، أو جهّز بريد الحكم لربطه بحسابه على المنصة عند توفر الميزة.</p></div>
                    <section class="mb-4">
                        <h3 class="h6">الحكام المضافون بالاسم <span class="badge text-bg-light border">{{ $committee->manualJudges->count() }}</span></h3>
                        @if($committee->manualJudges->isNotEmpty())
                            <div class="vstack gap-2 mb-3">
                                @foreach($committee->manualJudges as $manualJudge)
                                    <div class="border rounded p-2 d-flex align-items-center justify-content-between gap-2"><span>{{ $manualJudge->name }} <small class="text-muted">— حكم بالاسم فقط</small></span><form method="POST" action="{{ route('committees.manual-judges.destroy', [$committee, $manualJudge]) }}" onsubmit="return confirm('هل تريد حذف هذا الحكم؟');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">حذف</button></form></div>
                                @endforeach
                            </div>
                        @else
                            <p class="small text-muted">لم يتم إضافة حكام بالاسم لهذه اللجنة.</p>
                        @endif
                        <form method="POST" action="{{ route('committees.manual-judges.store', $committee) }}" class="hafez-inline-form d-flex flex-wrap gap-2" novalidate data-hafez-submit>
                            @csrf <label class="visually-hidden" for="manual-judge-name">اسم الحكم</label><input id="manual-judge-name" name="name" class="form-control flex-grow-1 @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="اسم الحكم" maxlength="100" required><button class="btn btn-outline-success" type="submit">إضافة حكم</button>
                            @error('name')<div class="invalid-feedback d-block w-100">{{ $message }}</div>@enderror
                        </form>
                    </section>
                    <section>
                        <h3 class="h6">ربط حكم بحساب المنصة</h3>
                        <p class="small text-muted mb-3">أدخل البريد الإلكتروني الخاص بالحكم.</p>
                        <form method="POST" action="{{ route('committees.assign-judges', $committee) }}" novalidate data-hafez-submit>
                            @csrf
                            <label class="form-label" for="judge-email">البريد الإلكتروني للحكم</label>
                            <div class="hafez-inline-form d-flex flex-wrap gap-2">
                                <input id="judge-email" name="judge_email" type="email" class="form-control flex-grow-1 @error('judge_email') is-invalid @enderror" value="{{ old('judge_email') }}" placeholder="judge@example.com" maxlength="255" required>
                                <button class="btn btn-success" type="submit">إضافة الحكم</button>
                            </div>
                            @error('judge_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </form>
                    </section>
                </div></div>
            </div>
            <div class="col-12 col-xl-7">
                <div class="card hafez-card border-0 h-100"><div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 mb-1">الطلاب المعيّنون</h2><p class="text-muted small mb-0">توزيع الطلاب المقبولين في مستوى هذه اللجنة فقط.</p></div><div class="text-start"><span class="badge text-bg-light border d-block mb-1">الطلاب المعيّنون: {{ $committee->students_count }}</span><small class="text-muted">المقبولون في هذا المستوى: {{ $eligibleRegistrationCount }}</small></div></div>
                    @if($eligibleRegistrationCount === 0)<x-ui.empty-state message="لا يوجد طلاب مقبولون في مستوى هذه اللجنة حالياً." />
                    @else
                        <form method="GET" action="{{ route('committees.show', $committee) }}" class="mb-3"><label class="visually-hidden" for="student_search">ابحث عن طالب</label><div class="input-group"><input id="student_search" class="form-control" name="student_search" value="{{ request('student_search') }}" placeholder="ابحث باسم الطالب أو رقم التسجيل..."><button class="btn btn-outline-success" type="submit">بحث</button></div></form>
                        <form method="POST" action="{{ route('committees.assign-students', $committee) }}" novalidate data-hafez-submit>
                            @csrf @foreach($registrations as $registration)<input type="hidden" name="visible_registration_ids[]" value="{{ $registration->id }}">@endforeach
                            <div class="d-flex flex-wrap gap-2 mb-3"><button class="btn btn-outline-success" type="submit" name="select_all_accepted" value="1">اختيار كل الطلاب المقبولين</button><button class="btn btn-outline-secondary" type="submit" name="clear_all" value="1">إلغاء تحديد الكل</button></div>
                            <div class="committee-selection-list vstack gap-2">
                                @foreach($registrations as $registration)<label class="border rounded p-2 d-flex align-items-center justify-content-between gap-2"><span class="d-flex align-items-center gap-2"><input class="form-check-input mt-0" type="checkbox" name="registration_ids[]" value="{{ $registration->id }}" @checked(in_array($registration->id, $assignedRegistrationIds))><span>{{ $registration->student?->full_name }}<small class="text-muted d-block">رقم التسجيل: <span dir="ltr">{{ $registration->registration_number }}</span></small></span></span><small class="text-muted" dir="ltr">{{ $registration->student?->phone }}</small></label>@endforeach
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"><span class="small text-muted">تُحفظ اختيارات هذه الصفحة فقط؛ لا تتأثر الاختيارات الموجودة في صفحات البحث الأخرى.</span><button class="btn btn-success" type="submit">حفظ الطلاب</button></div>
                        </form>
                        <div class="mt-3">{{ $registrations->links() }}</div>
                    @endif
                </div></div>
            </div>
        </div>
    </div>
</x-app-layout>

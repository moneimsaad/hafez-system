<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="small text-success fw-bold mb-1">التحليل والمتابعة</p>
            <h1 class="h3 mb-0">التقارير</h1>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="hafez-dashboard-intro mb-4">تقارير موجزة عن المسابقات والتسجيلات والنتائج والشهادات ضمن نطاق صلاحياتك.</div>
        <section class="mb-5">
            <h2 class="h5 mb-3">نظرة عامة على المسابقات</h2>
            <div class="row g-3">
                @foreach([['label'=>'المسابقات','key'=>'competitions'],['label'=>'مستويات المسابقة','key'=>'branches'],['label'=>'التسجيلات','key'=>'registrations']] as $card)
                    <div class="col-6 col-xl-4"><div class="card hafez-stat-card border-0"><div class="card-body"><small class="text-muted">{{ $card['label'] }}</small><div class="h3 mb-0">{{ $statistics[$card['key']] }}</div></div></div></div>
                @endforeach
            </div>
        </section>
        <section class="mb-5">
            <h2 class="h5 mb-3">ملخص التقييم والنتائج</h2>
            <div class="row g-3">
                @foreach([['label'=>'اللجان','key'=>'committees'],['label'=>'التقييمات','key'=>'evaluations'],['label'=>'النتائج','key'=>'results'],['label'=>'الناجحون','key'=>'successful_results'],['label'=>'النتائج النهائية','key'=>'approved_results']] as $card)
                    <div class="col-6 col-xl-3"><div class="card hafez-stat-card border-0"><div class="card-body"><small class="text-muted">{{ $card['label'] }}</small><div class="h3 mb-0">{{ $statistics[$card['key']] }}</div></div></div></div>
                @endforeach
            </div>
        </section>
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">تقرير المسابقات</h2>
                <span class="small text-muted">حسب المسابقة</span>
            </div>
            @if($competitions->count())
            <div class="report-mobile-list d-lg-none" aria-label="تقرير المسابقات">
                @foreach($competitions as $competition)
                    <article class="report-mobile-card"><h3>{{ $competition->title }}</h3><dl><div><dt>المستويات</dt><dd>{{ $competition->competition_branches_count }}</dd></div><div><dt>التسجيلات</dt><dd>{{ $competition->registrations_count }}</dd></div><div><dt>اللجان</dt><dd>{{ $competition->committees_count }}</dd></div><div><dt>التقييمات</dt><dd>{{ $competition->evaluations_count }}</dd></div><div><dt>النتائج</dt><dd>{{ $competition->results_count }}</dd></div><div><dt>الشهادات</dt><dd>{{ $competition->certificates_count }}</dd></div></dl></article>
                @endforeach
            </div>
            <div class="d-none d-lg-block"><x-ui.data-table :headers="['المسابقة','مستويات المسابقة','التسجيلات','اللجان','التقييمات','النتائج','الشهادات']">
                @foreach($competitions as $competition)<tr>
                    <td class="fw-semibold">{{ $competition->title }}</td>
                    <td>{{ $competition->competition_branches_count }}</td>
                    <td>{{ $competition->registrations_count }}</td>
                    <td>{{ $competition->committees_count }}</td>
                    <td>{{ $competition->evaluations_count }}</td>
                    <td>{{ $competition->results_count }}</td>
                    <td>{{ $competition->certificates_count }}</td>
                </tr>
                @endforeach</x-ui.data-table></div>
            <x-ui.pagination :paginator="$competitions" />
            @else<x-ui.empty-state message="لا توجد مسابقات في التقرير" />
            @endif
        </section>
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">تقرير تسجيلات الطلاب</h2>
                <span class="small text-muted">آخر التسجيلات</span>
            </div>
            @if($registrations->count())
            <div class="report-mobile-list d-lg-none" aria-label="تقرير تسجيلات الطلاب">
                @foreach($registrations as $registration)
                    <article class="report-mobile-card"><h3>{{ $registration->student?->full_name }}</h3><dl><div><dt>المسابقة</dt><dd>{{ $registration->competition?->title }}</dd></div><div><dt>المستوى</dt><dd>{{ $registration->competitionBranch?->name }}</dd></div><div><dt>الحالة</dt><dd><x-ui.status-badge :status="$registration->status" /></dd></div><div><dt>تاريخ التسجيل</dt><dd dir="ltr">{{ optional($registration->registered_at)->format('Y-m-d H:i') }}</dd></div></dl></article>
                @endforeach
            </div>
            <div class="d-none d-lg-block"><x-ui.data-table :headers="['الطالب','المسابقة','المستوى','الحالة','تاريخ التسجيل']">
                @foreach($registrations as $registration)<tr>
                    <td class="fw-semibold">{{ $registration->student?->full_name }}</td>
                    <td>{{ $registration->competition?->title }}</td>
                    <td>{{ $registration->competitionBranch?->name }}</td>
                    <td>
                        <x-ui.status-badge :status="$registration->status" />
                    </td>
                    <td>{{ optional($registration->registered_at)->format('Y-m-d H:i') }}</td>
                </tr>
                @endforeach</x-ui.data-table></div>
            <x-ui.pagination :paginator="$registrations" />
            @else<x-ui.empty-state message="لا توجد تسجيلات في التقرير" />
            @endif
        </section>
        <div class="row g-4">
            <section class="col-12 col-lg-6">
                <div class="card hafez-card border-0 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">ملخص النتائج</h2>
                        @if(count($resultsSummary))<div class="list-group list-group-flush">
                            @foreach($resultsSummary as $status=>$total)<div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <x-ui.status-badge :status="$status" />
                                <strong>{{ $total }}</strong>
                            </div>
                            @endforeach</div>
                        @else<x-ui.empty-state message="لا توجد نتائج" />
                        @endif
                    </div>
                </div>
            </section>
            <section class="col-12 col-lg-6">
                <div class="card hafez-card border-0 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">ملخص الشهادات</h2>
                        @if(count($certificatesSummary))<div class="list-group list-group-flush">
                            @foreach($certificatesSummary as $type=>$total)<div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>{{ $type }}</span>
                                <strong>{{ $total }}</strong>
                            </div>
                            @endforeach</div>
                        @else<x-ui.empty-state message="لا توجد شهادات" />
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

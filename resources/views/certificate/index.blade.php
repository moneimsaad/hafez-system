<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="small text-success fw-bold mb-1">التكريم والشهادات</p>
            <h1 class="h3 mb-0">الشهادات</h1>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="hafez-dashboard-intro mb-4">أنشئ شهادات النتائج النهائية وتابع أرقامها وبيانات التحقق.</div>
<x-ui.alert />
<x-ui.validation-errors />
<div class="row g-3 mb-4">
@foreach([['label'=>'إجمالي الشهادات','key'=>'total'],['label'=>'نتائج متاحة لإصدار شهادة','key'=>'available_results'],['label'=>'شهادات صادرة اليوم','key'=>'issued_today']] as $card)
    <div class="col-6 col-xl-3"><div class="card hafez-stat-card border-0 h-100"><div class="card-body"><p class="small text-muted mb-2">{{ $card['label'] }}</p><p class="h3 mb-0">{{ $summary[$card['key']] }}</p></div></div></div>
@endforeach
</div>
        <x-ui.search-filter placeholder="البحث برقم الشهادة أو الطالب أو المسابقة">
            <div class="col-12 col-md-3">
                <label class="form-label" for="competition_id">المسابقة</label>
                <select name="competition_id" id="competition_id" class="form-select">
                    <option value="">كل المسابقات</option>
                    @foreach($competitions as $competition)
                        <option value="{{ $competition->id }}" @selected((string) request('competition_id') === (string) $competition->id)>{{ $competition->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="branch_id">المستوى</label>
                <select name="branch_id" id="branch_id" class="form-select">
                    <option value="">كل المستويات</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }} — {{ $branch->competition?->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="filter_certificate_type">نوع الشهادة</label>
                <select name="certificate_type" id="filter_certificate_type" class="form-select">
                    <option value="">كل الأنواع</option>
                    @foreach($certificateTypes as $type)
                        <option value="{{ $type }}" @selected(request('certificate_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3"><label class="form-label" for="issue_from">إصدار من تاريخ</label><input id="issue_from" name="issue_from" type="date" class="form-control" value="{{ request('issue_from') }}"></div>
            <div class="col-12 col-md-3"><label class="form-label" for="issue_to">إصدار إلى تاريخ</label><input id="issue_to" name="issue_to" type="date" class="form-control" value="{{ request('issue_to') }}"></div>
        </x-ui.search-filter>
        @if(request()->hasAny(['search', 'competition_id', 'branch_id', 'certificate_type', 'issue_from', 'issue_to']))
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3" aria-label="الفلاتر النشطة">
                <span class="small text-muted">الفلاتر النشطة:</span>
                @if(request('search'))<span class="badge text-bg-light border">البحث: {{ request('search') }}</span>@endif
                @if(request('competition_id'))<span class="badge text-bg-light border">المسابقة محددة</span>@endif
                @if(request('branch_id'))<span class="badge text-bg-light border">المستوى محدد</span>@endif
                @if(request('certificate_type'))<span class="badge text-bg-light border">النوع: {{ request('certificate_type') }}</span>@endif
                @if(request('issue_from'))<span class="badge text-bg-light border">إصدار من: {{ request('issue_from') }}</span>@endif
                @if(request('issue_to'))<span class="badge text-bg-light border">إصدار إلى: {{ request('issue_to') }}</span>@endif
            </div>
        @endif
        @if($certificates->count())
        <div class="certificate-mobile-list d-lg-none" aria-label="قائمة الشهادات">
            @foreach($certificates as $certificate)
                <article class="certificate-mobile-card">
                    <div class="certificate-mobile-card__header">
                        <div>
                            <span class="certificate-mobile-card__label">رقم الشهادة</span>
                            <a class="certificate-mobile-card__number" href="{{ route('certificates.show', $certificate) }}">{{ $certificate->certificate_number }}</a>
                        </div>
                        <span class="badge text-bg-success">متاح للتحقق</span>
                    </div>
                    <dl class="certificate-mobile-card__details">
                        <div><dt>الطالب</dt><dd>{{ $certificate->student?->full_name }}</dd></div>
                        <div><dt>المسابقة</dt><dd>{{ $certificate->competition?->title }}</dd></div>
                        <div><dt>مستوى المسابقة</dt><dd>{{ $certificate->competitionBranch?->name }}</dd></div>
                        <div><dt>نوع الشهادة</dt><dd>{{ $certificate->certificate_type }}</dd></div>
                        <div><dt>تاريخ الإصدار</dt><dd dir="ltr">{{ optional($certificate->issued_at)->format('Y-m-d H:i') }}</dd></div>
                    </dl>
                    <a href="{{ route('certificates.show', $certificate) }}" class="btn btn-outline-success w-100">عرض الشهادة</a>
                </article>
            @endforeach
        </div>
        <div class="d-none d-lg-block">
        <x-ui.data-table :headers="['رقم الشهادة','الطالب','المسابقة','المستوى','نوع الشهادة','تاريخ الإصدار','التحقق','الإجراءات']">
            @foreach($certificates as $certificate)<tr>
                <td>
                    <a class="link-success fw-semibold text-decoration-none" href="{{ route('certificates.show',$certificate) }}">{{ $certificate->certificate_number }}</a>
                </td>
                <td>{{ $certificate->student?->full_name }}</td>
                <td>{{ $certificate->competition?->title }}</td>
                <td>{{ $certificate->competitionBranch?->name }}</td>
                <td>{{ $certificate->certificate_type }}</td>
                <td>{{ optional($certificate->issued_at)->format('Y-m-d H:i') }}</td>
                <td>متاح للتحقق</td>
                <td>
                    <a href="{{ route('certificates.show',$certificate) }}" class="btn btn-sm btn-outline-success">عرض</a>
                </td>
            </tr>
            @endforeach</x-ui.data-table>
        </div>
        <x-ui.pagination :paginator="$certificates" />
        @else
            @if(request()->hasAny(['search', 'competition_id', 'branch_id', 'certificate_type', 'issue_from', 'issue_to']))
                <x-ui.empty-state message="لا توجد شهادات مطابقة للفلاتر" :action="url()->current()" action-label="مسح الفلاتر" />
            @else
                <x-ui.empty-state message="لم يتم إصدار شهادات حتى الآن" />
            @endif
        @endif
    </div>
</x-app-layout>

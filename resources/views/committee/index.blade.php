<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="small text-success fw-bold mb-1">تنظيم المسابقات</p>
                <h1 class="h3 mb-0">اللجان</h1>
            </div>
            <a href="{{ route('committees.create') }}" class="btn btn-success">إنشاء لجنة</a>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="hafez-dashboard-intro mb-4">نسّق مواعيد اللجان ووزّع الحكام والطلاب على مستويات المسابقة المناسبة.</div>
        <x-ui.alert />
        <div class="row g-3 mb-4">
            @foreach([['label'=>'إجمالي اللجان','key'=>'committees'],['label'=>'المحكمون المعيّنون','key'=>'judges'],['label'=>'الطلاب الموزّعون','key'=>'students']] as $card)
                <div class="col-12 col-md-4"><div class="card hafez-stat-card border-0 h-100"><div class="card-body"><p class="small text-muted mb-2">{{ $card['label'] }}</p><p class="h3 mb-0">{{ $summary[$card['key']] }}</p></div></div></div>
            @endforeach
        </div>
        <x-ui.search-filter placeholder="البحث باسم اللجنة أو المسابقة أو المستوى">
            <div class="col-12 col-md-3">
                <label class="form-label" for="competition_id">المسابقة</label>
                <select id="competition_id" name="competition_id" class="form-select">
                    <option value="">كل المسابقات</option>
                    @foreach($competitions as $competition)
                        <option value="{{ $competition->id }}" @selected((string) request('competition_id') === (string) $competition->id)>{{ $competition->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="branch_id">المستوى</label>
                <select id="branch_id" name="branch_id" class="form-select">
                    <option value="">كل المستويات</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }} — {{ $branch->competition?->title }}</option>
                    @endforeach
                </select>
            </div>
        </x-ui.search-filter>
        @if(request()->hasAny(['search', 'competition_id', 'branch_id']))
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3" aria-label="الفلاتر النشطة">
                <span class="small text-muted">الفلاتر النشطة:</span>
                @if(request('search'))<span class="badge text-bg-light border">البحث: {{ request('search') }}</span>@endif
                @if(request('competition_id'))<span class="badge text-bg-light border">المسابقة محددة</span>@endif
                @if(request('branch_id'))<span class="badge text-bg-light border">المستوى محدد</span>@endif
            </div>
        @endif
        @if($committees->count())<div class="d-none d-md-block"><x-ui.data-table :headers="['اللجنة','المسابقة','المستوى','الحكام','الطلاب','تاريخ الإنشاء','الإجراءات']">
            @foreach($committees as $committee)<tr>
                <td class="fw-semibold">{{ $committee->name }}</td>
                <td>{{ $committee->competition?->title }}</td>
                <td>{{ $committee->competitionBranch?->name }}</td>
                <td>
                    <span class="badge text-bg-light border">{{ $committee->users_count }}</span>
                </td>
                <td>
                    <span class="badge text-bg-light border">{{ $committee->students_count }}</span>
                </td>
                <td>{{ $committee->created_at?->format('Y-m-d') }}</td>
                <td>
                    <x-ui.action-buttons>
                        <a href="{{ route('committees.show',$committee) }}" class="btn btn-sm btn-outline-success">عرض</a>
                        <a href="{{ route('committees.edit',$committee) }}" class="btn btn-sm btn-outline-secondary">تعديل</a>
                    </x-ui.action-buttons>
                </td>
            </tr>
            @endforeach</x-ui.data-table></div><div class="committee-mobile-list d-md-none">@foreach($committees as $committee)<article class="committee-mobile-card"><h2 class="h6 mb-2">{{ $committee->name }}</h2><dl><div><dt>المسابقة</dt><dd>{{ $committee->competition?->title }}</dd></div><div><dt>المستوى</dt><dd>{{ $committee->competitionBranch?->name }}</dd></div><div><dt>الحكام / الطلاب</dt><dd>{{ $committee->users_count }} / {{ $committee->students_count }}</dd></div><div><dt>تاريخ الإنشاء</dt><dd dir="ltr">{{ $committee->created_at?->format('Y-m-d') }}</dd></div></dl><div class="d-flex gap-2"><a href="{{ route('committees.show',$committee) }}" class="btn btn-sm btn-outline-success flex-fill">عرض</a><a href="{{ route('committees.edit',$committee) }}" class="btn btn-sm btn-outline-secondary flex-fill">تعديل</a></div></article>@endforeach</div>
        <x-ui.pagination :paginator="$committees" />
        @else
            @if(request()->hasAny(['search', 'competition_id', 'branch_id']))
                <x-ui.empty-state message="لا توجد نتائج مطابقة للفلاتر الحالية" :action="url()->current()" action-label="مسح الفلاتر" />
            @else
                <x-ui.empty-state message="لا توجد لجان حتى الآن" :action="route('committees.create')" action-label="إنشاء أول لجنة" />
            @endif
        @endif
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="small text-success fw-bold mb-1">متابعة الأداء</p>
            <h1 class="h3 mb-0">التقييمات</h1>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="hafez-dashboard-intro mb-4">{{ auth()->user()->role === 'User' ? 'راجع التقييمات المسندة إليك أو التابعة لمسابقاتك ضمن نطاق صلاحياتك.' : 'راجع تقييمات الطلاب ودرجاتهم ضمن نطاق التكليفات والمسابقات المسموح بها.' }}</div>
        <x-ui.alert />
        <div class="row g-3 mb-4">
            @foreach([['label'=>'إجمالي التقييمات','key'=>'total'],['label'=>'بانتظار التقييم','key'=>'pending'],['label'=>'تم التقييم','key'=>'completed'],['label'=>'متوسط نسبة الإنجاز','key'=>'average_percentage','suffix'=>'%']] as $card)
                <div class="col-6 col-xl-3"><div class="card hafez-stat-card border-0 h-100"><div class="card-body"><p class="small text-muted mb-2">{{ $card['label'] }}</p><p class="h3 mb-0">{{ $summary[$card['key']] }}{{ $card['suffix'] ?? '' }}</p></div></div></div>
            @endforeach
        </div>
        <x-ui.search-filter placeholder="البحث باسم الطالب">
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
            <div class="col-12 col-md-3">
                <label class="form-label" for="judge_id">الحكم</label>
                <select id="judge_id" name="judge_id" class="form-select">
                    <option value="">كل الحكام</option>
                    @foreach($judges as $judge)
                        <option value="{{ $judge->id }}" @selected((string) request('judge_id') === (string) $judge->id)>{{ $judge->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="status">الحالة</label>
                <select id="status" name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected((string) request('status') === (string) $status)>{{ ['pending' => 'بانتظار التقييم', 'submitted' => 'تم التقييم'][$status] ?? $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="committee_id">اللجنة</label>
                <select id="committee_id" name="committee_id" class="form-select">
                    <option value="">كل اللجان</option>
                    @foreach($committees as $committee)
                        <option value="{{ $committee->id }}" @selected((string) request('committee_id') === (string) $committee->id)>{{ $committee->name }} — {{ $committee->competition?->title }}</option>
                    @endforeach
                </select>
            </div>
        </x-ui.search-filter>
        @if(request()->hasAny(['search', 'competition_id', 'branch_id', 'judge_id', 'status', 'committee_id']))
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3" aria-label="الفلاتر النشطة">
                <span class="small text-muted">الفلاتر النشطة:</span>
                @if(request('search'))<span class="badge text-bg-light border">الطالب: {{ request('search') }}</span>@endif
                @if(request('competition_id'))<span class="badge text-bg-light border">المسابقة محددة</span>@endif
                @if(request('branch_id'))<span class="badge text-bg-light border">المستوى محدد</span>@endif
                @if(request('judge_id'))<span class="badge text-bg-light border">الحكم محدد</span>@endif
                @if(request('status'))<span class="badge text-bg-light border">الحالة: {{ request('status') }}</span>@endif
                @if(request('committee_id'))<span class="badge text-bg-light border">اللجنة محددة</span>@endif
            </div>
        @endif
        @if($evaluations->count())
        <div class="evaluation-mobile-list d-lg-none" aria-label="قائمة التقييمات">
            @foreach($evaluations as $evaluation)
                @php($committee = $evaluation->registration?->committeeStudents?->firstWhere('student_id', $evaluation->student_id)?->committee)
                @php($statusLabel = ['pending' => 'بانتظار التقييم', 'submitted' => 'تم التقييم'][$evaluation->status] ?? $evaluation->status)
                <article class="evaluation-mobile-card">
                    <div class="evaluation-mobile-card__header">
                        <a class="evaluation-mobile-card__student" href="{{ route('evaluations.show',$evaluation) }}">{{ $evaluation->student?->full_name }}</a>
                        <span class="badge text-bg-{{ $evaluation->status === 'submitted' ? 'success' : 'warning' }}">{{ $statusLabel }}</span>
                    </div>
                    <dl class="evaluation-mobile-card__details">
                        <div><dt>المسابقة</dt><dd>{{ $evaluation->competition?->title }}</dd></div>
                        <div><dt>المستوى</dt><dd>{{ $evaluation->competitionBranch?->name }}</dd></div>
                        <div><dt>اللجنة</dt><dd>{{ $committee?->name ?? '—' }}</dd></div>
                        <div><dt>الحكم</dt><dd>{{ $evaluation->judge?->name }}</dd></div>
                        <div><dt>المجموع</dt><dd>{{ $evaluation->total_score }} <span class="text-muted">({{ $evaluation->percentage }}%)</span></dd></div>
                    </dl>
                    <a href="{{ route('evaluations.show',$evaluation) }}" class="btn btn-outline-success w-100">عرض التقييم</a>
                </article>
            @endforeach
        </div>
        <div class="d-none d-lg-block"><x-ui.data-table :headers="['الطالب','المسابقة','المستوى','اللجنة','الحكم','الحالة','المجموع','الإجراءات']">
            @foreach($evaluations as $evaluation)<tr>
                <td class="fw-semibold">{{ $evaluation->student?->full_name }}</td>
                <td>{{ $evaluation->competition?->title }}</td>
                <td>{{ $evaluation->competitionBranch?->name }}</td>
                @php($committee = $evaluation->registration?->committeeStudents?->firstWhere('student_id', $evaluation->student_id)?->committee)
                <td>{{ $committee?->name ?? '—' }}</td>
                <td>{{ $evaluation->judge?->name }}</td>
                <td>
                    @php($statusLabel = ['pending' => 'بانتظار التقييم', 'submitted' => 'تم التقييم'][$evaluation->status] ?? $evaluation->status)
                    <span class="badge text-bg-{{ $evaluation->status === 'submitted' ? 'success' : 'warning' }}">{{ $statusLabel }}</span>
                </td>
                <td>{{ $evaluation->total_score }} <small class="text-muted">({{ $evaluation->percentage }}%)</small>
                </td>
                <td>
                    <a href="{{ route('evaluations.show',$evaluation) }}" class="btn btn-sm btn-outline-success">عرض</a>
                </td>
            </tr>
            @endforeach</x-ui.data-table></div>
        <x-ui.pagination :paginator="$evaluations" />
        @else
            @if(auth()->user()->role === 'User' && ($summary['total'] ?? 0) === 0)
                <x-ui.empty-state message="لا توجد تقييمات مسندة إليك حالياً" :action="route('evaluations.create')" action-label="مراجعة التكليفات" />
            @elseif(request()->hasAny(['search', 'competition_id', 'branch_id', 'judge_id', 'status', 'committee_id']))
                <x-ui.empty-state message="لا توجد نتائج مطابقة للفلاتر الحالية" :action="url()->current()" action-label="مسح الفلاتر" />
            @else
                <x-ui.empty-state message="لا توجد تقييمات لعرضها" :action="route('evaluations.create')" action-label="إضافة تقييم" />
            @endif
        @endif
    </div>
</x-app-layout>

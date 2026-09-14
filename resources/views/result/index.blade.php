<x-app-layout>
<x-slot name="header"><div><p class="small text-success fw-bold mb-1">إدارة النتائج</p><h1 class="h3 mb-1">النتائج</h1><p class="text-muted mb-0">ولّد نتائج المسابقة وراجع الدرجات والترتيب.</p></div></x-slot>
<div class="container-fluid py-4 px-3 px-lg-4 hafez-results-page">
<x-ui.alert/><x-ui.validation-errors/>
<div class="row g-3 mb-4">@foreach([['label'=>'إجمالي النتائج','key'=>'total'],['label'=>'الناجحون','key'=>'successful'],['label'=>'غير الناجحين','key'=>'failed']] as $card)<div class="col-6 col-xl-4"><div class="card hafez-stat-card border-0 h-100"><div class="card-body"><p class="small text-muted mb-2">{{ $card['label'] }}</p><p class="h3 mb-0">{{ $summary[$card['key']] }}</p></div></div></div>@endforeach</div>
<section class="card hafez-card border-0 mb-4 hafez-generation-panel"><div class="card-body p-4"><h2 id="generation-title" class="h5 mb-1">توليد النتائج النهائية</h2><p class="text-muted mb-4">اختر المسابقة ومستواها لتجميع تقييمات الحكام وتوليد الترتيب والنتائج.</p><form method="POST" action="{{ route('results.generate') }}">@csrf<div class="row g-3 align-items-end"><div class="col-12 col-lg-5"><label class="form-label">المسابقة</label><select name="competition_id" id="generate_competition_id" class="form-select" required><option value="">اختر المسابقة</option>@foreach($generationCompetitions as $competition)<option value="{{ $competition->id }}">{{ $competition->title }}</option>@endforeach</select></div><div class="col-12 col-lg-5"><label class="form-label">مستوى المسابقة</label><select name="branch_id" id="generate_branch_id" class="form-select" required disabled><option value="">اختر المسابقة أولاً</option>@foreach($generationCompetitions as $competition)@foreach($competition->competitionBranches as $branch)<option value="{{ $branch->id }}" data-competition="{{ $competition->id }}" hidden>{{ $branch->name }}</option>@endforeach @endforeach</select></div><div class="col-12 col-lg-2"><button class="btn btn-success w-100">توليد النتائج</button></div></div></form></div></section>
<section aria-labelledby="results-list-title"><div class="mb-3"><h2 id="results-list-title" class="h5 mb-1">النتائج المنشأة</h2></div><x-ui.search-filter placeholder="البحث باسم الطالب"><div class="col-12 col-md-4"><label class="form-label">المسابقة</label><select name="competition_id" id="result_filter_competition_id" class="form-select"><option value="">كل المسابقات</option>@foreach($competitions as $competition)<option value="{{ $competition->id }}">{{ $competition->title }}</option>@endforeach</select></div><div class="col-12 col-md-4"><label class="form-label">المستوى</label><select name="branch_id" id="result_filter_branch_id" class="form-select"><option value="">كل المستويات</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" data-competition="{{ $branch->competition_id }}">{{ $branch->name }}</option>@endforeach</select></div><div class="col-12 col-md-4"><label class="form-label">نتيجة الطالب</label><select name="status" class="form-select"><option value="">كل النتائج</option>@foreach($statuses as $status)<option value="{{ $status }}">{{ ['successful'=>'ناجح','failed'=>'غير ناجح'][$status] ?? $status }}</option>@endforeach</select></div></x-ui.search-filter>
@if($results->count())<form method="POST" action="{{ route('results.certificates.bulk') }}" id="bulk-certificate-form">@csrf<div id="bulk-certificate-bar" class="hafez-bulk-bar d-none mb-3"><span id="bulk-certificate-count"></span><button class="btn btn-success btn-sm">إصدار شهادات المحددين</button></div><div class="results-mobile-list d-lg-none" aria-label="قائمة النتائج">@foreach($results as $result)<article class="result-mobile-card"><div class="result-mobile-card__header"><label class="result-mobile-card__select">@if($result->certificates->isEmpty())<input class="bulk-result-checkbox" type="checkbox" name="result_ids[]" value="{{ $result->id }}"><span>تحديد</span>@else<span class="text-muted">صدرت الشهادة</span>@endif</label><span class="badge {{ $result->result_status === 'successful' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $result->result_status === 'successful' ? 'ناجح' : 'غير ناجح' }}</span></div><a class="result-mobile-card__student" href="{{ route('results.show',$result) }}">{{ $result->student?->full_name }}</a><dl class="result-mobile-card__details"><div><dt>المسابقة</dt><dd>{{ $result->competition?->title }}</dd></div><div><dt>المستوى</dt><dd>{{ $result->competitionBranch?->name }}</dd></div><div><dt>الدرجة</dt><dd>{{ $result->final_score }}</dd></div><div><dt>النسبة</dt><dd>{{ $result->percentage }}%</dd></div><div><dt>الترتيب</dt><dd>{{ $result->rank ?? '—' }}</dd></div></dl><div class="result-mobile-card__actions"><a class="btn btn-outline-success" href="{{ route('results.show',$result) }}">عرض</a>@if($result->certificates->isNotEmpty())<a class="btn btn-outline-secondary" href="{{ route('certificates.show',$result->certificates->first()) }}">عرض الشهادة</a>@else<button class="btn btn-success" type="submit" formaction="{{ route('results.certificate',$result) }}" formmethod="POST">إصدار شهادة</button>@endif</div></article>@endforeach</div><div class="d-none d-lg-block"><div class="table-responsive hafez-table-wrap hafez-results-table"><table class="table align-middle"><thead><tr><th><label><input type="checkbox" id="bulk-select-all"> تحديد الكل</label></th><th>الطالب</th><th>المسابقة</th><th>المستوى</th><th>الدرجة</th><th>النسبة</th><th>الترتيب</th><th>نتيجة الطالب</th><th>الإجراءات</th></tr></thead><tbody>@foreach($results as $result)<tr><td>@if($result->certificates->isEmpty())<input class="bulk-result-checkbox" type="checkbox" name="result_ids[]" value="{{ $result->id }}">@else<span class="text-muted">—</span>@endif</td><td><a class="link-success" href="{{ route('results.show',$result) }}">{{ $result->student?->full_name }}</a></td><td>{{ $result->competition?->title }}</td><td>{{ $result->competitionBranch?->name }}</td><td>{{ $result->final_score }}</td><td>{{ $result->percentage }}%</td><td>{{ $result->rank ?? '—' }}</td><td>{{ $result->result_status === 'successful' ? 'ناجح' : 'غير ناجح' }}</td><td><a class="btn btn-sm btn-outline-success" href="{{ route('results.show',$result) }}">عرض</a>@if($result->certificates->isNotEmpty())<a class="btn btn-sm btn-outline-secondary" href="{{ route('certificates.show',$result->certificates->first()) }}">عرض الشهادة</a>@else<button class="btn btn-sm btn-success" type="submit" formaction="{{ route('results.certificate',$result) }}" formmethod="POST">إصدار شهادة</button>@endif</td></tr>@endforeach</tbody></table></div></div></form><x-ui.pagination :paginator="$results" />@else<x-ui.empty-state message="لم يتم توليد نتائج بعد."/>@endif</section></div>
<script>document.addEventListener('DOMContentLoaded',()=>{const f=document.getElementById('bulk-certificate-form'),a=document.getElementById('bulk-select-all'),b=document.getElementById('bulk-certificate-bar'),c=document.getElementById('bulk-certificate-count');const refresh=()=>{const n=f?.querySelectorAll('.bulk-result-checkbox:checked').length||0;if(b)b.classList.toggle('d-none',!n);if(c)c.textContent=`تم تحديد ${n} نتيجة`;if(a){const x=[...f.querySelectorAll('.bulk-result-checkbox')];a.checked=x.length>0&&x.every(i=>i.checked);a.indeterminate=n>0&&n<x.length}};a?.addEventListener('change',()=>{f.querySelectorAll('.bulk-result-checkbox').forEach(i=>i.checked=a.checked);refresh()});f?.querySelectorAll('.bulk-result-checkbox').forEach(i=>i.addEventListener('change',refresh));f?.addEventListener('submit',e=>{const n=f.querySelectorAll('.bulk-result-checkbox:checked').length;if(!n||!confirm(`سيتم إصدار شهادة تقدير لـ ${n} طالباً. هل تريد المتابعة؟`))e.preventDefault()})});</script>
</x-app-layout>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const generationCompetition = document.getElementById('generate_competition_id');
    const generationBranch = document.getElementById('generate_branch_id');
    if (generationCompetition && generationBranch) {
        const syncGenerationBranches = () => {
            const competitionId = generationCompetition.value;
            generationBranch.value = '';
            generationBranch.disabled = !competitionId;
            generationBranch.querySelectorAll('option[data-competition]').forEach((option) => {
                option.hidden = option.dataset.competition !== competitionId;
            });
            const prompt = generationBranch.querySelector('option:not([data-competition])');
            if (prompt) prompt.textContent = competitionId ? 'اختر المستوى' : 'اختر المسابقة أولاً';
        };
        generationCompetition.addEventListener('change', syncGenerationBranches);
        syncGenerationBranches();
    }

    const form = document.getElementById('bulk-certificate-form');
    if (!form) return;

    const mobileList = form.querySelector('.results-mobile-list');
    if (mobileList && !document.getElementById('bulk-select-all-mobile')) {
        const button = document.createElement('button');
        button.id = 'bulk-select-all-mobile';
        button.type = 'button';
        button.className = 'btn btn-outline-success w-100 d-lg-none mb-3';
        button.textContent = 'تحديد كل النتائج القابلة للشهادة';
        button.addEventListener('click', () => {
            const inputs = [...mobileList.querySelectorAll('.bulk-result-checkbox:not(:disabled)')];
            const shouldSelect = inputs.some((input) => !input.checked);
            inputs.forEach((input) => {
                input.checked = shouldSelect;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
            button.textContent = shouldSelect ? 'إلغاء تحديد النتائج' : 'تحديد كل النتائج القابلة للشهادة';
        });
        mobileList.before(button);
    }

    const syncBulkInputsForViewport = () => {
        const mobile = window.matchMedia('(max-width: 991.98px)').matches;
        form.querySelectorAll('.results-mobile-list .bulk-result-checkbox').forEach((input) => input.disabled = !mobile);
        form.querySelectorAll('.hafez-results-table .bulk-result-checkbox').forEach((input) => input.disabled = mobile);
    };

    syncBulkInputsForViewport();
    window.addEventListener('resize', syncBulkInputsForViewport);
});
</script>

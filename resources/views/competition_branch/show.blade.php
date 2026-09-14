<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="small text-success fw-bold mb-1">تفاصيل مستوى المسابقة</p>
                <h1 class="h3 mb-0">{{ $competitionBranch->name }}</h1>
            </div>
            <x-ui.action-buttons>
                <a href="{{ $competitionBranch->competition_level_id ? route('competition-branches.edit', $competitionBranch->competition_level_id) : route('competition-branches.legacy.edit', $competitionBranch) }}" class="btn btn-success">تعديل</a>
                <a href="{{ route('competition-branches.index') }}" class="btn btn-outline-secondary">العودة</a>
            </x-ui.action-buttons>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <x-ui.alert />
        <div class="card hafez-card border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">نظرة عامة</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-3">المسابقة</dt>
                    <dd class="col-sm-9">@if($competitionBranch->competition)<a href="{{ route('competitions.show', $competitionBranch->competition) }}">{{ $competitionBranch->competition->title }}</a>@else<span class="badge rounded-pill text-bg-light border">غير مرتبط بمسابقة حالياً</span>@endif</dd>
                    <dt class="col-sm-3">الوصف</dt>
                    <dd class="col-sm-9">{{ $competitionBranch->description ?: 'لا يوجد وصف مضاف.' }}</dd>
                    <dt class="col-sm-3">مقدار الحفظ</dt>
                    <dd class="col-sm-9">{{ $competitionBranch->memorization_amount }}</dd>
                </dl>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card hafez-stat-card border-0 h-100">
                    <div class="card-body">
                        <p class="small text-muted mb-2">الفئة العمرية</p>
                        <p class="h4 mb-0">@if($competitionBranch->min_age !== null && $competitionBranch->max_age !== null)من {{ $competitionBranch->min_age }} إلى {{ $competitionBranch->max_age }} سنة @else لا يوجد شرط محدد للعمر @endif</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card hafez-stat-card border-0 h-100">
                    <div class="card-body">
                        <p class="small text-muted mb-2">إعداد الدرجات</p>
                        <p class="h4 mb-0">{{ $competitionBranch->passing_score }} / {{ $competitionBranch->total_score }}</p>
                        <small class="text-muted">درجة النجاح / الدرجة الكلية</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex flex-wrap gap-2">
                @if($competitionBranch->competition)<a href="{{ route('competitions.show', $competitionBranch->competition) }}" class="btn btn-outline-success">عرض المسابقة</a>@endif
                <a href="{{ route('competition-branches.index') }}" class="btn btn-outline-secondary">عرض مستويات المسابقة</a>
            </div>
            <form method="POST" action="{{ route('competition-branches.destroy', $competitionBranch) }}" onsubmit="return confirm('هل تريد حذف هذا المستوى؟');">
                @csrf @method('DELETE')<x-ui.confirm-button label="حذف المستوى" />
            </form>
        </div>
    </div>
</x-app-layout>

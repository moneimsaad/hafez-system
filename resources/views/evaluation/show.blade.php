<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="small text-success fw-bold mb-1">تفاصيل التقييم</p>
                <h1 class="h3 mb-0">{{ $evaluation->student?->full_name }}</h1>
            </div>
            <a href="{{ route('evaluations.index') }}" class="btn btn-outline-secondary">العودة</a>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <x-ui.alert />
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="card hafez-card border-0 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="fw-bold text-success">{{ mb_substr($evaluation->student?->full_name ?? 'ط',0,1) }}</span>
                            <div>
                                <h2 class="h5 mb-1">ملف الطالب</h2>
                                <p class="text-muted mb-0">{{ $evaluation->student?->full_name }}</p>
                            </div>
                        </div>
                        <dl class="mb-0">
                            <dt>المسابقة</dt>
                            <dd>{{ $evaluation->competition?->title }}</dd>
                            <dt>المستوى</dt>
                            <dd>{{ $evaluation->competitionBranch?->name }}</dd>
                            <dt>العمر</dt>
                            <dd>{{ $evaluation->student?->birth_date?->age }} سنة</dd>
                            <dt>اللجنة</dt>
                            <dd>{{ $committee?->name ?? 'غير محددة' }}</dd>
                            <dt>الحكم</dt>
                            <dd>{{ $evaluation->judge?->name }}</dd>
                            <dt>الحالة</dt>
                            <dd>
                                <x-ui.status-badge :status="$evaluation->status" />
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="card hafez-card border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="h5 mb-0">درجات التقييم</h2>
                            <div class="text-success fw-bold">{{ $evaluation->percentage }}%</div>
                        </div>
                        <div class="row g-3">
                            @foreach($evaluation->scoreItems() as $score)<div class="col-6 col-md-3">
                                <div class="border rounded p-3 text-center">
                                    <small class="text-muted d-block mb-2">{{ $score['name'] }}</small>
                                    <strong class="h4">{{ $score['score'] }}</strong>
                                    @if($score['max_score'] !== null)<small class="text-muted d-block mt-1">من {{ $score['max_score'] }}</small>@endif
                                </div>
                            </div>
                            @endforeach</div>
                        <div class="alert alert-success mt-4 mb-0">
                            <span>المجموع النهائي:</span> <strong>{{ $evaluation->total_score }}</strong> <span class="mx-2">|</span> <span>النسبة:</span> <strong>{{ $evaluation->percentage }}%</strong>
                        </div>
                        @if($evaluation->notes)
                        <hr>
                        <h3 class="h6">ملاحظات</h3>
                        <p class="mb-0">{{ $evaluation->notes }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="small text-success fw-bold mb-1">تفاصيل المسابقة</p>
                <h1 class="h3 mb-0">{{ $competition->title }}</h1>
            </div>
            <x-ui.action-buttons>
                <a href="{{ route('competitions.edit', $competition) }}" class="btn btn-success">تعديل</a>
                <a href="{{ route('competitions.index') }}" class="btn btn-outline-secondary">العودة</a>
            </x-ui.action-buttons>
        </div>
    </x-slot>
        <div class="container-fluid py-4 px-3 px-lg-4">
        <x-ui.alert />
        @php
            $hasBranches = $competition->competitionBranches()->exists();
            $registrationReady = app(\App\Services\CompetitionStatusService::class)->isRegistrationOpen($competition);
            $availableTransitions = $availableTransitions ?? [];
            $currentLifecycleState = $currentLifecycleState ?? app(\App\Services\CompetitionLifecycleService::class)->state($competition);
            $currentLifecycleLabel = $currentLifecycleLabel ?? app(\App\Services\CompetitionLifecycleService::class)->label($currentLifecycleState);
            $publicUrl = $competition->creator?->username && $competition->competition_number
                ? route('competitions.public-register-canonical', [$competition->creator->username, $competition->competition_number])
                : null;
        @endphp
        <div class="card hafez-card border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">خطوات تجهيز المسابقة</h2>
                <ol class="mb-3 ps-3">
                    <li class="mb-2">إنشاء المسابقة <span class="text-success">✓</span></li>
                    <li class="mb-2">إضافة مستويات المسابقة @if($hasBranches)<span class="text-success">✓</span>@else<span class="text-muted">— أضف مستويات المسابقة للبدء</span>@endif</li>
                    <li class="mb-2">تهيئة قواعد المستويات @if($hasBranches)<span class="text-success">✓</span>@else<span class="text-muted">— بعد إضافة المستويات</span>@endif</li>
                    <li class="mb-2">فتح التسجيل @if($registrationReady)<span class="text-success">✓</span>@else<span class="text-muted">— اختر الحالة وافتح فترة التسجيل</span>@endif</li>
                    <li>مشاركة رابط التسجيل العام @if($publicUrl && $registrationReady)<span class="text-success">✓</span>@else<span class="text-muted">— سيظهر بعد اكتمال المتطلبات</span>@endif</li>
                </ol>
                @if(!$hasBranches)
                    <a href="{{ route('competitions.levels.add-existing', $competition) }}" class="btn btn-success">إضافة مستوى لهذه المسابقة</a>
                @elseif(!$registrationReady)
                    <div class="alert alert-warning mb-0">أضف مستويات المسابقة وتأكد من فترة التسجيل والحالة «مفتوح للتسجيل» قبل مشاركة الرابط.</div>
                @else
                    <div class="alert alert-success mb-0">المسابقة جاهزة لاستقبال تسجيلات الطلاب.</div>
                @endif
            </div>
        </div>
        @if($availableTransitions)
            <div class="card hafez-card border-0 mb-4">
                <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="h5 mb-1">حالة المسابقة</h2>
                        <p class="text-muted mb-0">الحالة الحالية: {{ $competition->display_status }}</p>
                    </div>
                    @foreach($availableTransitions as $transition)
                        @php($isEvaluationRollback = $currentLifecycleState === \App\Services\CompetitionLifecycleService::EVALUATION && $transition['state'] === \App\Services\CompetitionLifecycleService::REGISTRATION_CLOSED)
                        @if($isEvaluationRollback)
                            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#evaluation-rollback-modal">العودة إلى {{ $transition['label'] }}</button>
                        @else
                            <form method="POST" action="{{ route('competitions.status.update', $competition) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="{{ $transition['state'] }}">
                                <input type="hidden" name="expected_status" value="{{ $competition->status }}">
                                <button type="submit" class="btn btn-success">الانتقال إلى {{ $transition['label'] }}</button>
                            </form>
                        @endif
                    @endforeach
                </div>
            </div>
            @if(collect($availableTransitions)->contains('state', \App\Services\CompetitionLifecycleService::REGISTRATION_CLOSED) && $currentLifecycleState === \App\Services\CompetitionLifecycleService::EVALUATION)
                <div class="modal fade" id="evaluation-rollback-modal" tabindex="-1" aria-labelledby="evaluation-rollback-title" aria-hidden="true">
                    <div class="modal-dialog">
                        <form method="POST" action="{{ route('competitions.status.update', $competition) }}" class="modal-content">
                            @csrf
                            <input type="hidden" name="status" value="{{ \App\Services\CompetitionLifecycleService::REGISTRATION_CLOSED }}">
                            <input type="hidden" name="expected_status" value="{{ $competition->status }}">
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="evaluation-rollback-title">تأكيد العودة إلى مرحلة سابقة</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-2">الحالة الحالية: <strong>{{ $currentLifecycleLabel }}</strong></p>
                                <p class="mb-0">الحالة المستهدفة: <strong>التسجيل مغلق</strong></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-warning">تأكيد العودة إلى التسجيل مغلق</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endif
        <div class="card hafez-card border-0 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="h5">نظرة عامة</h2>
                        <p class="text-muted mb-0">{{ $competition->description ?: 'لا يوجد وصف مضاف.' }}</p>
                    </div>
                    <x-ui.status-badge :status="$competition->display_status" />
                </div>
                <hr>
                <dl class="row mb-0">
                    <dt class="col-sm-3">الموقع</dt>
                    <dd class="col-sm-9">{{ $competition->location }}</dd>
                    <dt class="col-sm-3">نطاق الإتاحة</dt>
                    <dd class="col-sm-9">{{ $competition->publication_scope === 'nationwide' ? 'جميع محافظات مصر' : ($competition->publication_scope === 'governorate' ? 'محافظة '.$competition->target_governorate : 'خاص بالرابط فقط') }}</dd>
                    <dt class="col-sm-3">فترة التسجيل</dt>
                    <dd class="col-sm-9">{{ $competition->registration_start_date?->format('Y-m-d H:i') }} إلى {{ $competition->registration_end_date?->format('Y-m-d H:i') }}</dd>
                    <dt class="col-sm-3">فترة الاختبارات</dt>
                    <dd class="col-sm-9">{{ $competition->exam_start_date?->format('Y-m-d H:i') }} إلى {{ $competition->exam_end_date?->format('Y-m-d H:i') }}</dd>
                </dl>
            </div>
        </div>
        <div class="row g-3 mb-4">
            @foreach([['label'=>'مستويات المسابقة','value'=>$competition->competitionBranches()->count()],['label'=>'التسجيلات','value'=>$competition->registrations()->count()],['label'=>'اللجان','value'=>$competition->committees()->count()],['label'=>'التقييمات','value'=>$competition->evaluations()->count()],['label'=>'النتائج','value'=>$competition->results()->count()],['label'=>'الشهادات','value'=>$competition->certificates()->count()]] as $stat)<div class="col-6 col-lg-4 col-xl-2">
                <div class="card hafez-stat-card border-0 h-100">
                    <div class="card-body">
                        <p class="small text-muted mb-2">{{ $stat['label'] }}</p>
                        <p class="h3 mb-0">{{ $stat['value'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach</div>
        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <div class="card hafez-card border-0 h-100">
                    <div class="card-body p-4">
                        <x-competition.scoring-criteria :rules="$competition->rules" />
                        @if(! app(\App\Services\CompetitionScoringRulesService::class)->criteria($competition->rules))
                            <p class="text-muted mb-0">تُطبّق المعايير الافتراضية لهذه المسابقة.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="card hafez-card border-0 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5">إجراءات</h2>
                        <x-ui.action-buttons class="flex-column align-items-stretch">
                            <a href="{{ route('competitions.levels.add-existing', $competition) }}" class="btn btn-outline-success">إضافة مستوى لهذه المسابقة</a>
                            @if ($publicUrl)
                                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="btn btn-success">فتح صفحة تسجيل الطلاب</a>
                                <div class="input-group input-group-sm mt-2" dir="ltr"><input id="public-registration-url" class="form-control" value="{{ $publicUrl }}" readonly aria-label="رابط التسجيل العام"><button type="button" class="btn btn-outline-success" data-copy-url="#public-registration-url">نسخ الرابط</button></div>
                                <div id="copy-url-feedback" class="small text-success mt-1" role="status" aria-live="polite"></div>
                            @else
                                <div class="small text-muted">سيظهر رابط التسجيل العام بعد توفر بيانات الرابط canonical.</div>
                            @endif
                            <a href="{{ route('competition-branches.index', ['competition_id' => $competition->id]) }}" class="btn btn-outline-success">إدارة مستويات المسابقة</a>
                            <a href="{{ route('registrations.index') }}" class="btn btn-outline-secondary">متابعة التسجيلات</a>
                            <a href="{{ route('committees.index') }}" class="btn btn-outline-secondary">إدارة اللجان</a>
                        </x-ui.action-buttons>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <form method="POST" action="{{ route('competitions.destroy', $competition) }}" onsubmit="return confirm('هل تريد حذف هذه المسابقة؟');">
                @csrf @method('DELETE')<x-ui.confirm-button label="حذف المسابقة" />
            </form>
        </div>
    </div>
    @if($publicUrl)
        <script>
            document.querySelector('[data-copy-url]')?.addEventListener('click', async function () {
                const input = document.querySelector(this.dataset.copyUrl);
                try { await navigator.clipboard.writeText(input.value); }
                catch (_) { input.select(); document.execCommand('copy'); }
                document.getElementById('copy-url-feedback').textContent = 'تم نسخ الرابط';
            });
        </script>
    @endif
</x-app-layout>

@props(['activities' => collect()])

@if($activities->isEmpty())
    <div class="hafez-empty-state">
        <div class="hafez-empty-icon">⌁</div>
        <p class="mb-1 fw-semibold">لا توجد أنشطة معروضة</p>
        <p class="small text-muted mb-0">ستظهر هنا أحدث العمليات المسجلة.</p>
    </div>
@else
    @php
        $actions = ['created' => 'إنشاء', 'updated' => 'تحديث', 'deleted' => 'حذف', 'approved' => 'اعتماد', 'rejected' => 'رفض', 'generated' => 'إصدار', 'submitted' => 'إرسال', 'judges_assigned' => 'إسناد الحكام', 'students_assigned' => 'إسناد الطلاب', 'manual_judge_added' => 'إضافة حكم إلى لجنة'];
        $tables = ['users' => 'المستخدمون', 'competitions' => 'المسابقات', 'competition_branches' => 'مستويات المسابقة', 'registrations' => 'التسجيلات', 'committees' => 'اللجان', 'evaluations' => 'التقييمات', 'results' => 'النتائج', 'certificates' => 'الشهادات', 'settings' => 'الإعدادات'];
    @endphp
    <div class="list-group list-group-flush">
@foreach($activities->take(6) as $activity)
            <div class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3">
                <div>
                    <p class="mb-1 fw-semibold">{{ $actions[$activity->action] ?? $activity->action }} — {{ $tables[$activity->table_name] ?? $activity->table_name }}</p>
                    <p class="small text-muted mb-0">{{ $activity->user?->name ?? 'النظام' }}</p>
                </div>
                <time class="small text-muted text-nowrap" datetime="{{ $activity->created_at?->toIso8601String() }}">{{ $activity->created_at?->diffForHumans() }}</time>
            </div>
        @endforeach
    </div>
@endif

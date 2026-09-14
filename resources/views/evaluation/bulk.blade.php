<x-app-layout>
    <x-slot name="header"><div><p class="small text-success fw-bold mb-1">إدارة التقييم</p><h1 class="h3 mb-1">إدخال التقييمات</h1><p class="text-muted mb-0">{{ $committee->competition?->title }} — {{ $committee->competitionBranch?->name }} — {{ $committee->name }}</p></div></x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <x-ui.alert />
        <x-ui.validation-errors />
        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-4">
            <div class="d-flex flex-wrap gap-3 small"><span>إجمالي الطلاب: <strong id="bulk-total-count">{{ $summary['total'] }}</strong></span><span>تم التقييم: <strong id="bulk-completed-count" class="text-success">{{ $summary['completed'] }}</strong></span><span>متبقي: <strong id="bulk-remaining-count">{{ $summary['remaining'] }}</strong></span></div>
            <form method="GET" action="{{ route('committees.evaluations.bulk', $committee) }}" class="d-flex flex-wrap gap-2">
                <input class="form-control" name="search" value="{{ request('search') }}" placeholder="ابحث باسم الطالب أو رقم التسجيل..." aria-label="البحث">
                <select class="form-select" name="status" aria-label="حالة التقييم"><option value="">كل الطلاب</option><option value="unevaluated" @selected(request('status') === 'unevaluated')>غير مقيّمين</option><option value="evaluated" @selected(request('status') === 'evaluated')>تم التقييم</option></select>
                <button class="btn btn-outline-success" type="submit">تطبيق</button><a class="btn btn-outline-secondary" href="{{ route('committees.evaluations.bulk', $committee) }}">مسح</a>
            </form>
        </div>
        @if($rows->count())
            <form method="POST" action="{{ route('committees.evaluations.bulk.store', $committee) }}" novalidate data-hafez-submit>
                @csrf<input type="hidden" name="page" value="{{ $rows->currentPage() }}">
                <div class="table-responsive hafez-table-wrap hafez-bulk-evaluation-table">
                    <table class="table align-middle hafez-data-table">
                        <thead><tr><th>#</th><th>الطالب</th><th>رقم التسجيل</th>@foreach($criteria as $criterion)<th>{{ $criterion['name'] }}<small class="d-block text-muted">من {{ $criterion['max_score'] }}</small></th>@endforeach<th>الحالة</th><th>الإجراء</th></tr></thead>
                        <tbody>
                        @foreach($rows as $row)
                            @php($evaluation = $evaluations->get($row->registration_id))
                            @php($rowKey = $row->registration_id)
                            <tr data-autosave-row data-registration-id="{{ $rowKey }}" data-autosave-url="{{ route('committees.evaluations.autosave', [$committee, $rowKey]) }}" data-saved="{{ $evaluation ? 'true' : 'false' }}"><td class="bulk-row-number">{{ $rows->firstItem() + $loop->index }}<input type="hidden" name="rows[{{ $rowKey }}][registration_id]" value="{{ $row->registration_id }}"></td><td class="fw-semibold text-nowrap" data-label="الطالب">{{ $row->student?->full_name }}</td><td dir="ltr" class="text-nowrap" data-label="رقم التسجيل">{{ $row->registration?->registration_number }}</td>
                                @foreach($criteria as $criterionIndex => $criterion)
                                    <td data-label="{{ $criterion['name'] }}"><input class="form-control @error("rows.{$rowKey}.scores.{$criterionIndex}.score") is-invalid @enderror" style="min-width:90px" type="number" min="0" max="{{ $criterion['max_score'] }}" data-min="0" data-max="{{ $criterion['max_score'] }}" step="0.01" inputmode="decimal" dir="ltr" data-score-index="{{ $criterionIndex }}" name="rows[{{ $rowKey }}][scores][{{ $criterionIndex }}][score]" value="{{ old("rows.{$rowKey}.scores.{$criterionIndex}.score", data_get($evaluation?->scores, "{$criterionIndex}.score")) }}" aria-label="{{ $criterion['name'] }} - {{ $row->student?->full_name }}" @readonly($evaluation)><div class="invalid-feedback d-block" data-score-error></div></td>
                                @endforeach
                                <td data-label="الحالة"><span data-row-status class="badge text-bg-{{ $evaluation ? 'success' : 'warning' }}">{{ $evaluation ? 'تم الحفظ' : 'غير مقيم' }}</span></td><td data-label="الإجراء"><button type="button" class="btn btn-sm btn-outline-secondary" data-row-edit @disabled(!$evaluation)>تعديل</button><button type="button" class="btn btn-sm btn-link text-secondary d-none" data-row-cancel>إلغاء</button><span class="small text-danger d-block" data-row-error role="alert"></span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex flex-wrap justify-content-end gap-2 mt-3"><button class="btn btn-outline-success" type="submit" name="save_and_next" value="0">حفظ التقييمات</button><button class="btn btn-success" type="submit" name="save_and_next" value="1">حفظ والانتقال للتالي</button></div>
            </form>
            <div class="mt-3">{{ $rows->links() }}</div>
        @else
            <x-ui.empty-state message="لا توجد طلاب مطابقة للبحث أو الفلاتر الحالية." :action="route('committees.evaluations.bulk', $committee)" action-label="مسح الفلاتر" />
        @endif
    </div>
</x-app-layout>

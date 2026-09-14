<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="small text-success fw-bold mb-1">إدارة اللجان</p>
            <h1 class="h3 mb-0">إنشاء لجنة</h1>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <form method="POST" action="{{ route('committees.store') }}" class="card hafez-card border-0" novalidate data-hafez-submit>
                    @csrf<div class="card-body p-4 p-lg-5">
                        <x-ui.validation-errors />
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="competition_id">المسابقة</label>
                                <select name="competition_id" id="competition_id" class="form-select" required>
                                    <option value="">اختر المسابقة</option>
                                    @foreach($competitions as $competition)<option value="{{ $competition->id }}" @selected(old('competition_id')==$competition->id)>{{ $competition->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <x-ui.text-input name="name" label="اسم اللجنة" :required="true" />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="branch_id">المستوى</label>
                                <select name="branch_id" id="branch_id" class="form-select" required>
                                    <option value="">اختر المستوى</option>
                                    @foreach($branches as $branch)<option value="{{ $branch->id }}" data-competition="{{ $branch->competition_id }}" @selected(old('branch_id')==$branch->id)>{{ $branch->competition?->title }} - {{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="exam_date">موعد الاختبار</label>
                                <x-ui.date-input name="exam_date" label="موعد الاختبار" type="datetime" :required="true" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.text-input name="location" label="الموقع" :required="true" />
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 px-4 pb-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('committees.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                        <button class="btn btn-success" type="submit">حفظ اللجنة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        const c = document.getElementById('competition_id'),
            b = document.getElementById('branch_id');

        function f() {
            b.querySelectorAll('option[data-competition]').forEach(o => o.hidden = !!c.value && o.dataset.competition !== c.value);
            if (b.selectedOptions[0]?.hidden) b.value = '';
        }
        c.addEventListener('change', f);
        f();
    </script>
</x-app-layout>

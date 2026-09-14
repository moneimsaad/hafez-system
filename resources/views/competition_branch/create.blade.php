<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="small text-success fw-bold mb-1">مستويات مسابقاتي</p>
            <h1 class="h3 mb-0">إنشاء مستوى جديد</h1>
        </div>
    </x-slot>

    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="row justify-content-center"><div class="col-12 col-xl-9">
            <form method="POST" action="{{ route('competition-branches.store') }}" class="card hafez-card border-0">
                @csrf <input type="hidden" name="reusable_level" value="1">
                <div class="card-body p-4 p-lg-5">
                    <x-ui.validation-errors />
                    <section>
                        <h2 class="h5 text-success border-bottom pb-2 mb-3">بيانات المستوى</h2>
                        <div class="row g-3">
                            <div class="col-md-6"><x-ui.text-input name="name" label="اسم المستوى" :value="old('name')" :required="true" /></div>
                            <div class="col-md-6"><x-ui.text-input name="memorization_amount" label="مقدار الحفظ" :value="old('memorization_amount')" :required="true" /></div>
                            <div class="col-12"><x-ui.textarea-input name="description" label="الوصف" :value="old('description')" rows="3" /></div>
                        </div>
                    </section>
                    <section class="mt-4">
                        <h2 class="h5 text-success border-bottom pb-2 mb-2">الإعدادات الافتراضية للمسابقات</h2>
                        <p class="text-muted small mb-3">يمكن تعديل هذه القيم عند إضافة المستوى إلى أي مسابقة.</p>
                        <div class="row g-3">
                            <div class="col-md-6"><x-ui.text-input name="default_min_age" label="الحد الأدنى للعمر" type="number" min="0" :value="old('default_min_age')" :required="false" /></div>
                            <div class="col-md-6"><x-ui.text-input name="default_max_age" label="الحد الأقصى للعمر" type="number" min="0" :value="old('default_max_age')" :required="false" /></div>
                            <div class="col-md-6"><x-ui.text-input name="default_total_score" label="الدرجة الكلية" type="number" min="0" step="0.01" :value="old('default_total_score')" :required="false" /></div>
                            <div class="col-md-6"><x-ui.text-input name="default_passing_score" label="درجة النجاح" type="number" min="0" step="0.01" :value="old('default_passing_score')" :required="false" /></div>
                        </div>
                    </section>
                </div>
                <div class="card-footer bg-transparent border-0 px-4 pb-4"><button class="btn btn-success">إنشاء المستوى</button></div>
            </form>
        </div></div>
    </div>
</x-app-layout>

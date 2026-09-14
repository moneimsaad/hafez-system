<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="small text-success fw-bold mb-1">إدارة المستخدمين</p>
            <h1 class="h3 mb-0">إضافة مستخدم</h1>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <form method="POST" action="{{ route('users.store') }}" class="card hafez-card border-0" novalidate data-hafez-submit>
                    @csrf<div class="card-body p-4 p-lg-5">
                        <x-ui.validation-errors />
                        <div class="row g-3">
                            <div class="col-md-6">
                                <x-ui.text-input name="name" label="الاسم" :required="true" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.text-input name="phone" label="الهاتف" :required="true" />
                            </div>
                            <div class="col-12">
                                <x-ui.text-input name="email" label="البريد الإلكتروني" type="email" :required="true" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.text-input name="password" label="كلمة المرور" type="password" :required="true" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.text-input name="password_confirmation" label="تأكيد كلمة المرور" type="password" :required="true" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.select-input name="role" label="نوع المستخدم" :value="'User'" :options="['User'=>'مستخدم','Platform Admin'=>'مدير المنصة']" :required="true" />
                            </div>
                            <input type="hidden" name="status" value="active">
                            <div class="col-12">
                                <p class="hafez-form-help mb-0">سيتم إنشاء المستخدم بحالة نشطة تلقائيًا.</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 px-4 pb-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                        <button class="btn btn-success" type="submit">حفظ المستخدم</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="small text-success fw-bold mb-1">إدارة المستخدمين</p>
            <h1 class="h3 mb-0">تعديل المستخدم</h1>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <form method="POST" action="{{ route('users.update',$user) }}" class="card hafez-card border-0" novalidate data-hafez-submit>
                    @csrf @method('PUT')<div class="card-body p-4 p-lg-5">
                        <x-ui.validation-errors />
                        <div class="row g-3">
                            <div class="col-md-6">
                                <x-ui.text-input name="name" label="الاسم" :value="$user->name" :required="true" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.text-input name="phone" label="الهاتف" :value="$user->phone" :required="true" />
                            </div>
                            <div class="col-12">
                                <x-ui.text-input name="email" label="البريد الإلكتروني" type="email" :value="$user->email" :required="true" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.text-input name="password" label="كلمة مرور جديدة (اختياري)" type="password" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.text-input name="password_confirmation" label="تأكيد كلمة المرور" type="password" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.select-input name="role" label="الدور" :value="$user->role" :options="['User'=>'مستخدم','Platform Admin'=>'مدير المنصة']" :required="true" />
                            </div>
                            <div class="col-md-6">
                                <x-ui.select-input name="status" label="الحالة" :value="$user->status" :options="['active'=>'نشط','inactive'=>'غير نشط']" :required="true" />
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 px-4 pb-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('users.show',$user) }}" class="btn btn-outline-secondary">إلغاء</a>
                        <button class="btn btn-success" type="submit">حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

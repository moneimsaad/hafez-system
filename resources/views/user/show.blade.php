<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="small text-success fw-bold mb-1">ملف المستخدم</p>
                <h1 class="h3 mb-0">{{ $user->name }}</h1>
            </div>
            <x-ui.action-buttons>
                <a href="{{ route('users.edit',$user) }}" class="btn btn-success">تعديل</a>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">العودة</a>
            </x-ui.action-buttons>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <x-ui.alert />
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">
                <div class="card hafez-card border-0">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <span class="fw-bold text-success">{{ mb_substr($user->name, 0, 1) }}</span>
                            <div>
                                <h2 class="h4 mb-1">{{ $user->name }}</h2>
                                <p class="text-muted mb-0">معلومات الحساب الأساسية</p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted d-block mb-1">البريد الإلكتروني</small>
                                    <span>{{ $user->email }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted d-block mb-1">الهاتف</small>
                                    <span>{{ $user->phone }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted d-block mb-1">الدور</small>
                                    <span class="badge text-bg-{{ $user->role === 'Platform Admin' ? 'warning' : 'success' }}">{{ $user->role === 'Platform Admin' ? 'مدير المنصة' : 'مستخدم' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted d-block mb-1">الحالة</small>
                                    <x-ui.status-badge :status="$user->status" />
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <small class="text-muted d-block mb-1">تاريخ إنشاء الحساب</small>
                                    <span>{{ $user->created_at?->format('Y-m-d H:i') ?: 'غير متوفر' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

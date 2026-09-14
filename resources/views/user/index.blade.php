<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="small text-success fw-bold mb-1">إدارة المنصة</p>
                <h1 class="h3 mb-0">المستخدمون</h1>
            </div>
            <a href="{{ route('users.create') }}" class="btn btn-success">إضافة مستخدم</a>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="hafez-dashboard-intro mb-4">أدر حسابات مديري المنصة ومستخدمي إدارة المسابقات من مساحة واحدة.</div>
        <x-ui.alert />
        <x-ui.search-filter placeholder="البحث في المستخدمين">
            <div class="col-12 col-md-3">
                <label class="form-label" for="role">الدور</label>
                <select id="role" name="role" class="form-select">
                    <option value="">كل الأدوار</option>
                    <option value="Platform Admin">مدير المنصة</option>
                    <option value="User">مستخدم</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="status">الحالة</label>
                <select id="status" name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>
        </x-ui.search-filter>
        @if($users->count())<x-ui.data-table :headers="['الاسم','البريد الإلكتروني','الهاتف','الدور','الحالة','الإجراءات']">
            @foreach($users as $user)<tr>
                <td class="fw-semibold">{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->phone }}</td>
                <td>
                    <span class="badge text-bg-{{ $user->role === 'Platform Admin' ? 'warning' : 'success' }}">{{ $user->role === 'Platform Admin' ? 'مدير المنصة' : 'مستخدم' }}</span>
                </td>
                <td>
                    <x-ui.status-badge :status="$user->status" />
                </td>
                <td>
                    <x-ui.action-buttons>
                        <a href="{{ route('users.show',$user) }}" class="btn btn-sm btn-outline-success">عرض</a>
                        <a href="{{ route('users.edit',$user) }}" class="btn btn-sm btn-outline-secondary">تعديل</a>
                    </x-ui.action-buttons>
                </td>
            </tr>
            @endforeach</x-ui.data-table>
        <x-ui.pagination :paginator="$users" />
        @else<x-ui.empty-state message="لا يوجد مستخدمون" :action="route('users.create')" action-label="إضافة أول مستخدم" />
        @endif
    </div>
</x-app-layout>
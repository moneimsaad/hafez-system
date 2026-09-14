<section>
    <header class="mb-4"><h2 class="h5 mb-2">معلومات الحساب</h2><p class="text-muted small mb-0">حدّث بياناتك وبيانات الجهة المنظمة. اسم المستخدم يجب أن يكون بالإنجليزية وصالحاً للرابط.</p></header>
    <form method="post" action="{{ route('profile.update') }}" novalidate>@csrf @method('patch')
        <div class="row g-3">
            @foreach([['name','الاسم',$user->name],['organization_name','اسم الجهة',$user->organization_name],['username','اسم المستخدم',$user->username],['phone','رقم الهاتف',$user->phone],['email','البريد الإلكتروني',$user->email]] as [$field,$label,$value])
                <div class="col-12 {{ $field === 'email' ? '' : 'col-md-6' }}"><label for="{{ $field }}" class="form-label">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" {{ $field === 'email' ? 'type=email readonly' : '' }} {{ $field === 'username' ? 'dir=ltr placeholder=مثال: alfordose' : '' }} class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $value) }}" @if($field === 'name') required @endif @if($field === 'email') aria-describedby="email-help" @endif>@if($field === 'email')<div id="email-help" class="form-text">لا يمكن تغيير البريد الإلكتروني لأنه مرتبط بالحساب.</div>@endif @if($field === 'username')<div class="form-text">سيظهر في رابط التسجيل العام، ويجب أن يكون فريداً.</div>@endif @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            @endforeach
        </div><button class="btn btn-success mt-4">حفظ التغييرات</button>
    </form>
</section>

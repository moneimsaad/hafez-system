<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>تسجيل طالب | Hafez System</title>
@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="hafez-auth-shell">
<main class="container py-4 py-lg-5">
@php($registrationOpen = $registrationOpen ?? true)
@php($registrationClosed = isset($selectedCompetition) && ! $registrationOpen)
@php($registrationStatus = $registrationStatus ?? (isset($selectedCompetition) ? $selectedCompetition->display_status : null))
@php($registrationReason = match ($registrationStatus) {
    'انتهى التسجيل' => 'انتهاء الفترة المحددة للتسجيل.',
    'مغلق' => 'تم إغلاق التسجيل من إدارة المسابقة.',
    'موقوفة' => 'تم إيقاف التسجيل مؤقتاً.',
    'مؤرشفة' => 'المسابقة مؤرشفة حالياً.',
    'جاري الاختبارات' => 'انتهت فترة التسجيل وبدأت مرحلة التقييم.',
    'منتهية' => 'انتهت فترة التسجيل والاختبارات.',
    'قريباً' => 'لم تبدأ الفترة المحددة للتسجيل بعد.',
    default => 'التسجيل غير متاح حالياً.',
})
<div class="row justify-content-center">
<div class="col-12 col-xl-10">
<div class="hafez-auth-brand text-center mb-4">
<h1>{{ isset($selectedCompetition) ? 'التسجيل في '.$selectedCompetition->title : 'تسجيل طالب في المسابقة' }}</h1>
<p>{{ isset($selectedCompetition) ? 'أدخل البيانات الأساسية لإرسال طلب التسجيل في هذه المسابقة.' : 'اختر المسابقة والمستوى ثم أدخل البيانات الأساسية للتسجيل.' }}</p>
</div>
@if($registrationClosed)
<div class="alert alert-danger hafez-registration-closed-alert mb-4" role="alert">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-3">
        <div>
            <h2 class="h4 mb-2">تم إغلاق التسجيل</h2>
            <p class="mb-0">انتهت فترة التسجيل في هذه المسابقة ولا يمكن إرسال طلبات جديدة حالياً.</p>
        </div>
        <div class="text-sm-end small">
            <div class="mb-2"><span class="fw-bold">الحالة الحالية:</span> <span class="badge text-bg-danger">{{ $registrationStatus }}</span></div>
            <div><span class="fw-bold">السبب:</span> <span>{{ $registrationReason }}</span></div>
        </div>
    </div>
</div>
@endif
@if(isset($selectedCompetition))
<div class="card hafez-card border-0 mb-4">
<div class="card-body p-4 p-lg-5"><div class="row g-4 align-items-start">
<div class="col-lg-7"><p class="small text-success fw-bold mb-2">تفاصيل التسجيل</p><h2 class="h4 mb-3">{{ $selectedCompetition->title }}</h2>
<dl class="row mb-0"><dt class="col-sm-4 text-muted">الجهة المنظمة</dt><dd class="col-sm-8">{{ $selectedCompetition->creator?->organization_name ?: $selectedCompetition->creator?->name ?: 'الجهة المنظمة للمسابقة' }}</dd>
<dt class="col-sm-4 text-muted">نطاق المشاركة</dt><dd class="col-sm-8">{{ $selectedCompetition->publication_scope === 'nationwide' ? 'جميع محافظات مصر' : ($selectedCompetition->publication_scope === 'governorate' ? 'سكان محافظة '.$selectedCompetition->target_governorate : 'التسجيل متاح عبر رابط المسابقة') }}</dd>
<dt class="col-sm-4 text-muted">فترة التسجيل</dt><dd class="col-sm-8">يبدأ التسجيل في <span dir="ltr">{{ optional($selectedCompetition->registration_start_date)->format('Y-m-d') }}</span> وينتهي في <span dir="ltr">{{ optional($selectedCompetition->registration_end_date)->format('Y-m-d') }}</span>.</dd></dl></div>
<div class="col-lg-5"><p class="small text-muted mb-2">مستويات المسابقة المتاحة</p>
@if($selectedCompetition->competitionBranches->isNotEmpty())<div class="d-flex flex-wrap gap-2">@foreach($selectedCompetition->competitionBranches as $level)<span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $level->name }} <span class="text-muted">({{ $level->memorization_amount }})</span></span>@endforeach</div>@else<p class="text-muted mb-0">لا توجد مستويات متاحة حالياً.</p>@endif</div>
</div></div></div>
<div class="card hafez-card border-0 mb-4"><div class="card-body p-4 p-lg-5"><x-competition.scoring-criteria :rules="$selectedCompetition->rules" /></div></div>
@if(filled($selectedCompetition->additional_terms))
<div class="card hafez-card border-0 mb-4"><div class="card-body p-4 p-lg-5">
<h2 class="h5 text-success mb-3">شروط وتعليمات المسابقة</h2>
<div class="hafez-registration-terms-content mb-0" role="note">{{ $selectedCompetition->additional_terms }}</div>
</div></div>
@endif
@endif
@if(!isset($selectedCompetition))
<section class="mb-4" aria-labelledby="available-competitions-title">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><p class="small text-success fw-bold mb-1">اكتشف وشارك</p><h2 id="available-competitions-title" class="h4 mb-0">المسابقات المتاحة للتسجيل</h2></div><span class="text-muted small">اختر المسابقة لعرض نموذج التسجيل</span></div>
    @if($competitions->isNotEmpty())
    <div class="row g-3">
        @foreach($competitions as $availableCompetition)
        <div class="col-12 col-md-6 col-xl-4"><article class="card h-100 border-0 shadow-sm"><div class="card-body p-4 d-flex flex-column">
            <h3 class="h5 mb-2">{{ $availableCompetition->title }}</h3>
            <p class="small text-muted mb-3">الجهة المنظمة: {{ $availableCompetition->creator?->organization_name ?: $availableCompetition->creator?->name ?: 'الجهة المنظمة' }}</p>
            <div class="small mb-3"><div class="mb-1"><strong>فترة التسجيل:</strong> يبدأ في <span dir="ltr">{{ optional($availableCompetition->registration_start_date)->format('Y-m-d') }}</span> وينتهي في <span dir="ltr">{{ optional($availableCompetition->registration_end_date)->format('Y-m-d') }}</span></div><div><strong>المستويات:</strong> {{ $availableCompetition->competitionBranches->pluck('name')->join('، ') }}</div></div>
            <a class="btn btn-success mt-auto" href="{{ route('competitions.public-register', $availableCompetition) }}">التسجيل في المسابقة</a>
        </div></article></div>
        @endforeach
    </div>
    @else
        <div class="card hafez-card border-0"><div class="card-body p-4 text-center"><p class="mb-1 text-muted">لا توجد مسابقات متاحة للتسجيل حالياً.</p><p class="small text-muted mb-0">يمكنك العودة والتحقق مرة أخرى لاحقاً.</p></div></div>
    @endif
</section>
@endif
<form method="POST" action="{{ route('registrations.store') }}" class="card hafez-card border-0 {{ $registrationClosed ? 'registration-form-closed' : '' }}" novalidate data-hafez-submit @if($registrationClosed) aria-disabled="true" @endif>
@csrf<div class="card-body p-4 p-lg-5">
@if(! $registrationClosed)<x-ui.validation-errors />@endif
<div class="row g-3">
@if(isset($selectedCompetition))
<input type="hidden" name="competition_id" value="{{ $selectedCompetition->id }}">
<div class="col-12"><div class="alert alert-info mb-0"><strong>المسابقة:</strong> {{ $selectedCompetition->title }}</div></div>
@else
<div class="col-md-6">
<x-ui.select-input name="competition_id" label="المسابقة" :options="$competitions->pluck('title','id')->all()" :required="true" />
</div>
@endif
@if(isset($selectedCompetition))
<div class="col-12">
<fieldset class="hafez-registration-levels" aria-describedby="branch-help" @if($registrationClosed) aria-disabled="true" @endif>
<legend class="form-label mb-2">{{ $registrationClosed ? 'مستويات المسابقة (للاطلاع فقط)' : 'اختر مستوى المسابقة' }} @if(! $registrationClosed)<span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">مطلوب</span>@endif</legend>
<div class="row g-3">
@foreach($selectedCompetition->competitionBranches as $branch)
@php($ageCondition = $branch->min_age !== null && $branch->max_age !== null ? "من {$branch->min_age} إلى {$branch->max_age} سنة" : ($branch->min_age !== null ? "من {$branch->min_age} سنوات فأكثر" : ($branch->max_age !== null ? "حتى {$branch->max_age} سنة" : 'لا يوجد شرط عمر محدد')))
<div class="col-12 col-lg-6"><label class="hafez-registration-level-card d-block h-100 {{ old('branch_id') == $branch->id ? 'is-selected' : '' }}" for="branch_{{ $branch->id }}"><span class="d-flex align-items-start gap-2"><input class="form-check-input mt-1" type="radio" id="branch_{{ $branch->id }}" name="branch_id" value="{{ $branch->id }}" required data-level-option data-level-name="{{ $branch->name }}" data-memorization="{{ $branch->memorization_amount }}" data-min-age="{{ $branch->min_age }}" data-max-age="{{ $branch->max_age }}" @checked(old('branch_id') == $branch->id) @disabled($registrationClosed)><span><strong class="d-block">{{ $branch->name }}</strong><span class="d-block small mt-2"><span class="text-muted">مقدار الحفظ:</span> {{ $branch->memorization_amount }}</span><span class="d-block small mt-1"><span class="text-muted">الفئة العمرية:</span> {{ $ageCondition }}</span>@if(filled($branch->description))<span class="d-block small text-muted mt-2">{{ $branch->description }}</span>@endif</span></span></label></div>
@endforeach
</div>
<div id="branch-help" class="form-text mt-2">راجع شروط المستوى قبل اختيار المستوى المناسب للطالب.</div>
@error('branch_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
</fieldset>
<div id="level-conditions" class="alert alert-light border mt-3 d-none" role="status"><strong>المستوى المختار</strong><div id="level-conditions-text" class="small mt-1"></div></div>
</div>
@else
<div class="col-md-6">
<label class="form-label" for="branch_id">اختر مستوى المسابقة <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">مطلوب</span></label>
<select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id" required aria-describedby="branch-help">
<option value="">اختر المستوى المناسب للطالب</option>
@foreach($competitions as $competition)@foreach($competition->competitionBranches as $branch)<option value="{{ $branch->id }}" data-competition="{{ $competition->id }}" data-min-age="{{ $branch->min_age }}" data-max-age="{{ $branch->max_age }}" data-level-name="{{ $branch->name }}" data-memorization="{{ $branch->memorization_amount }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
@endforeach @endforeach</select>
<div id="branch-help" class="form-text">اختر المستوى الذي يناسب مقدار الحفظ المطلوب في المسابقة.</div>
@error('branch_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
<div id="level-conditions" class="alert alert-light border mt-3 d-none" role="status"><strong>شروط المستوى</strong><div id="level-conditions-text" class="small mt-1"></div></div>
</div>
@endif
<div class="col-12">
<h2 class="h6 text-success border-bottom pb-2 mt-2">بيانات الطالب</h2>
</div>
<div class="col-md-6">
<x-ui.text-input name="full_name" label="الاسم بالكامل" :required="true" :disabled="$registrationClosed" aria-describedby="full-name-help" />
<div id="full-name-help" class="form-text">اكتب الاسم كما تريد أن يظهر في شهادة التقدير.</div>
</div>
<div class="col-md-6">
<x-ui.text-input name="national_id" label="الرقم القومي (اختياري)" :disabled="$registrationClosed" inputmode="numeric" maxlength="14" dir="ltr" aria-describedby="national-id-help" />
<div id="national-id-help" class="form-text">أدخل الرقم القومي المكون من 14 رقماً، وسيتم تعبئة تاريخ الميلاد والنوع والمحافظة تلقائياً.</div>
<div id="national-id-success" class="form-text text-success d-none" role="status">تم التحقق من بيانات الرقم القومي وتعبئة البيانات تلقائياً.</div>
</div>
<div class="col-md-6"><x-ui.select-input name="governorate" label="المحافظة (للمسابقات المحددة جغرافياً)" :disabled="$registrationClosed" :options="array_combine(config('governorates'), config('governorates'))" /></div>
<div class="col-md-6">
<x-ui.date-input name="birth_date" label="تاريخ الميلاد" type="date" :required="true" :disabled="$registrationClosed" />
</div>
<div class="col-md-6">
<x-ui.select-input name="gender" label="النوع" :options="['Male'=>'ذكر','Female'=>'أنثى']" :required="true" :disabled="$registrationClosed" />
</div>
<div class="col-md-6">
<x-ui.text-input name="phone" label="هاتف الطالب" type="tel" inputmode="tel" maxlength="11" :disabled="$registrationClosed" aria-describedby="phone-help" :required="true" />
<div id="phone-help" class="form-text">مثال: 01012345678</div>
</div>
<div class="col-md-6">
<x-ui.text-input name="parent_phone" label="هاتف ولي الأمر" type="tel" inputmode="tel" maxlength="11" :disabled="$registrationClosed" aria-describedby="parent-phone-help" :required="true" />
<div id="parent-phone-help" class="form-text">مثال: 01012345678</div>
</div>
<div class="col-12">
<details class="hafez-form-optional">
<summary>بيانات إضافية (اختياري)</summary>
<div class="row g-3 mt-1">
<div class="col-md-6">
<x-ui.text-input name="email" label="البريد الإلكتروني (اختياري)" type="email" :disabled="$registrationClosed" />
</div>
<div class="col-md-6">
<x-ui.text-input name="center_name" label="المركز / المسجد / المدرسة (اختياري)" :disabled="$registrationClosed" />
</div>
<div class="col-md-8">
<x-ui.text-input name="city" label="المدينة (اختياري)" :disabled="$registrationClosed" />
</div>
<div class="col-12">
<x-ui.textarea-input name="address" label="العنوان (اختياري)" rows="2" :disabled="$registrationClosed" />
</div>
<div class="col-12">
<x-ui.textarea-input name="notes" label="ملاحظات (اختياري)" rows="2" :disabled="$registrationClosed" />
</div>
</div>
</details>
</div>
</div>
</div>
<div class="card-footer bg-transparent border-0 px-4 pb-4 d-flex justify-content-end">
@if($registrationClosed)
<div class="hafez-registration-closed-submit w-100" role="status" aria-live="polite">
    <span class="hafez-registration-closed-lock" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
    </span>
    <div>
        <strong class="d-block">التسجيل مغلق</strong>
        <span>التسجيل غير متاح حالياً. لا يمكن إرسال طلبات تسجيل جديدة في الوقت الحالي.</span>
    </div>
</div>
@else
<button class="btn btn-success px-4" type="submit">إرسال طلب التسجيل</button>
@endif
</div>
</form>
</div>
</div>
</main>
<script>
const registrationClosed=@json($registrationClosed);
const competition=document.getElementById('competition_id'),branch=document.getElementById('branch_id');
const levelConditions=document.getElementById('level-conditions'), levelConditionsText=document.getElementById('level-conditions-text');
const ageText=(min,max)=>min&&max?`من ${min} إلى ${max} سنة`:min?`من ${min} سنوات فأكثر`:max?`حتى ${max} سنة`:'لا يوجد شرط عمر محدد';
function updateLevelConditions(){
    if(!branch||!levelConditions||!levelConditionsText) return;
    const option=branch.options[branch.selectedIndex];
    if(!option||!option.value){levelConditions.classList.add('d-none'); return;}
    const min=option.dataset.minAge, max=option.dataset.maxAge;
    levelConditionsText.textContent=`مقدار الحفظ: ${option.dataset.memorization}. شرط العمر: ${ageText(min,max)}.`;
    levelConditions.classList.remove('d-none');
}
branch?.addEventListener('change',updateLevelConditions); updateLevelConditions();
document.querySelectorAll('[data-level-option]').forEach((option) => option.addEventListener('change', () => {
    document.querySelectorAll('.hafez-registration-level-card').forEach((card) => card.classList.toggle('is-selected', card.htmlFor === option.id));
    if (!levelConditions || !levelConditionsText) return;
    levelConditionsText.textContent=`${option.dataset.levelName} — مقدار الحفظ: ${option.dataset.memorization}. شرط العمر: ${ageText(option.dataset.minAge, option.dataset.maxAge)}.`;
    levelConditions.classList.remove('d-none');
}));
if(competition&&branch){function filterBranches(){const id=competition.value;branch.querySelectorAll('option[data-competition]').forEach(o=>o.hidden=!!id&&o.dataset.competition!==id)}competition.addEventListener('change',filterBranches);filterBranches();}
const nationalId=document.getElementById('national_id'), birthDate=document.getElementById('birth_date'), gender=document.getElementById('gender'), governorate=document.getElementById('governorate'), idSuccess=document.getElementById('national-id-success');
const governorates=@json(config('egyptian_national_id.governorates'));
let lastDerivedId='';
function setBirthDateValue(value){
    if(!birthDate) return;
    if(birthDate._flatpickr){birthDate._flatpickr.setDate(value||null,false);}else{birthDate.value=value||'';}
}
function setBirthDateLocked(locked){
    if(!birthDate) return;
    birthDate.readOnly=locked;
    birthDate.setAttribute('aria-readonly',locked?'true':'false');
    const visible=birthDate._flatpickr?.altInput;
    if(visible){visible.readOnly=locked;visible.disabled=locked;visible.tabIndex=locked?-1:0;visible.setAttribute('aria-readonly',locked?'true':'false');}
    if(locked) birthDate._flatpickr?.close();
}
function normalizeDigits(value){return value.replace(/[٠-٩]/g, digit => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)));}
function updateNationalIdFields(){
    if(!nationalId) return;
    nationalId.value=normalizeDigits(nationalId.value).replace(/[^0-9]/g,'').slice(0,14);
    const value=nationalId.value;
    const valid=/^[23][0-9]{13}$/.test(value);
    let parsed=null;
    if(valid){
        const year=(value[0]==='2'?1900:2000)+Number(value.slice(1,3));
        const month=Number(value.slice(3,5)), day=Number(value.slice(5,7));
        const date=new Date(Date.UTC(year,month-1,day));
        const code=value.slice(7,9);
        if(date.getUTCFullYear()===year&&date.getUTCMonth()===month-1&&date.getUTCDate()===day&&date<=new Date()&&governorates[code]) parsed={date:`${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`,gender:Number(value[12])%2?'Male':'Female',governorate:governorates[code]};
    }
    if(parsed){setBirthDateValue(parsed.date);setBirthDateLocked(true);gender.value=parsed.gender;gender.disabled=true;gender.setAttribute('aria-disabled','true');governorate.value=parsed.governorate;idSuccess.classList.remove('d-none');lastDerivedId=value;}
    else {if(lastDerivedId&&value!==lastDerivedId){setBirthDateValue('');gender.value='';governorate.value='';}setBirthDateLocked(registrationClosed);if(!registrationClosed){gender.disabled=false;gender.removeAttribute('aria-disabled');}idSuccess.classList.add('d-none');lastDerivedId='';}
}
if(nationalId){nationalId.addEventListener('input',updateNationalIdFields);updateNationalIdFields();}
</script>
</body>
</html>

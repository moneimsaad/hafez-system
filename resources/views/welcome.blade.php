@php
$platformName = app(\App\Services\PlatformSettingsService::class)->get('platform.name', 'Hafez System');
@endphp
<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="منصة متكاملة لإدارة مسابقات القرآن الكريم من التسجيل حتى إصدار الشهادات والتحقق منها">
    <title>{{ $platformName }} | إدارة مسابقات حفظ القرآن الكريم</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="home-page">
    <header class="home-header">
        <div class="container home-header__inner">
            <a class="home-brand" href="{{ url('/') }}"><span><strong>{{ $platformName }}</strong><small>إدارة مسابقات حفظ القرآن</small></span></a>
            <nav class="home-header__nav" aria-label="التنقل الرئيسي">
                <a href="#competitions">تصفح المسابقات</a>
                <a href="{{ route('registrations.status') }}">متابعة التسجيل</a>
                <a href="{{ route('certificates.verify.form') }}">التحقق من شهادة</a>
            </nav>
            <div class="home-header__actions"><a class="btn btn-outline-success" href="{{ route('login') }}">بوابة منظمي المسابقات</a></div>
        </div>
    </header>

    <main>
        <section class="home-hero">
            <div class="container home-hero__grid">
                <div class="home-hero__content">
                    <span class="home-eyebrow">منصة إدارة مسابقات القرآن الكريم</span>
                    <h1>أدِر مسابقتك من التسجيل<br class="home-heading-break">حتى إصدار الشهادات — بدون ورقة واحدة</h1>
                    <p class="home-hero__description">بدل الاستمارات الورقية وملفات Excel والمتابعة اليدوية، نظّم التسجيل، وزّع اللجان، اجمع الدرجات، وأصدر شهادات موثقة بـ QR — كل ذلك من مكان واحد.</p>
                    <div class="home-actions">
                        <a class="btn btn-success btn-lg" href="{{ route('organizer.register') }}">أنشئ حساب منظم — مجاناً</a>
                        <a class="btn btn-outline-secondary btn-lg" href="#competitions">تصفح المسابقات المتاحة</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-section home-section--sand">
            <div class="container">
                <div class="home-section-heading"><span class="home-eyebrow">الفئات المستفيدة</span>
                    <h2>من يحتاج هذه المنصة؟</h2>
                    <p>أي جهة تنظم مسابقات قرآنية وتريد التخلص من العمل اليدوي.</p>
                </div>
                <div class="home-audience-grid">
                    @foreach([
                    ['المساجد', 'نظّم مسابقات الحفظ والتكريم السنوية وتابع المشاركين والنتائج من لوحة تحكم واحدة.', '
                    <path d="M3 21h18M5 21V9l7-6 7 6v12M9 21v-6a3 3 0 0 1 6 0v6" />'],
                    ['جمعيات ومراكز تحفيظ القرآن', 'أنشئ مسابقات بمستويات وشروط مختلفة، واستقبل التسجيلات إلكترونياً بدل الأوراق.', '
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z" />'],
                    ['المدارس والمؤسسات التعليمية', 'أدِر الأنشطة القرآنية وتتبع تقييمات الطلاب وأصدر شهادات موثقة بـ QR.', '
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                    <path d="M6 12v5c3 3 9 3 12 0v-5" />'],
                    ['منظمو المسابقات المستقلون', 'أنشئ مسابقتك وشارك رابط التسجيل العام — المنصة تتولى الباقي من اللجان حتى الشهادات.', '
                    <circle cx="12" cy="12" r="10" />
                    <polygon points="12 6 12 12 16 14" />'],
                    ] as [$title, $description, $icon])
                    <article class="home-audience-card">
                        <div class="home-audience-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg></div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $description }}</p>
                    </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="home-section home-section--white home-comparison-section">
            <div class="container">
                <div class="home-section-heading">
                    <span class="home-eyebrow">قبل المنصة وبعدها</span>
                    <h2>من آلاف الأوراق والكشوف... إلى إدارة رقمية كاملة للمسابقة</h2>
                    <p>تسجيلات ورقية، ملفات Excel، ولجان ودرجات تحتاج متابعة مستمرة... حتى تصبح المسابقة عبئًا يوميًا.</p>
                </div>

                <div class="home-comparison-transform" aria-hidden="true">
                    <span>قبل</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"></path>
                        <path d="m13 6 6 6-6 6"></path>
                    </svg>
                    <strong>{{ $platformName }}</strong>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"></path>
                        <path d="m13 6 6 6-6 6"></path>
                    </svg>
                    <span>بعد</span>
                </div>

                <div class="home-comparison-grid">
                    <!-- Right Card: Before -->
                    <div class="home-comparison-card home-comparison-card--before">
                        <div class="home-comparison-card__header">
                            <span class="home-comparison-badge home-comparison-badge--before"><span aria-hidden="true">×</span> قبل {{ $platformName }}</span>
                            <h3>الإدارة اليدوية ترهق فريق التنظيم</h3>
                        </div>
                        <ul class="home-comparison-list">
                            <li>
                                <span class="home-comparison-icon home-comparison-icon--cross" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </span>
                                <span><strong>التسجيل يبدأ بالأوراق</strong><small>استمارات ورقية وملفات Excel متعددة</small></span>
                            </li>
                            <li>
                                <span class="home-comparison-icon home-comparison-icon--cross" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </span>
                                <span><strong>كل تعديل يربك التنظيم</strong><small>قوائم ومستويات ولجان تحتاج إعادة ترتيب يدوي</small></span>
                            </li>
                            <li>
                                <span class="home-comparison-icon home-comparison-icon--cross" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </span>
                                <span><strong>درجات الحكام تتشتت</strong><small>جمع الدرجات ومراجعة الحسابات يدويًا</small></span>
                            </li>
                            <li>
                                <span class="home-comparison-icon home-comparison-icon--cross" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </span>
                                <span><strong>النتائج تتأخر</strong><small>ترتيب الفائزين وإصدار الشهادات يستغرق وقتًا</small></span>
                            </li>
                        </ul>
                        <div class="home-comparison-card__footer home-comparison-card__footer--before">
                            <span><b>مثال واقعي</b><span class="home-comparison-example-context">3 جهات × 1000 طالب</span><strong class="home-comparison-example-total">3000 <small>مشارك</small></strong><small>آلاف العمليات اليدوية</small></span>
                        </div>
                    </div>

                    <!-- Left Card: After -->
                    <div class="home-comparison-card home-comparison-card--after">
                        <div class="home-comparison-card__header">
                            <span class="home-comparison-badge home-comparison-badge--after"><span aria-hidden="true">✓</span> بعد {{ $platformName }}</span>
                            <h3>كل مراحل المسابقة في نظام واحد</h3>
                        </div>
                        <ul class="home-comparison-list">
                            <li>
                                <span class="home-comparison-icon home-comparison-icon--check" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </span>
                                <span><strong>رابط واحد للتسجيل</strong><small>استقبل بيانات الطلاب مباشرة دون أوراق</small></span>
                            </li>
                            <li>
                                <span class="home-comparison-icon home-comparison-icon--check" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </span>
                                <span><strong>تنظيم واضح للمشاركين</strong><small>المستويات واللجان مرتبة في مكان واحد</small></span>
                            </li>
                            <li>
                                <span class="home-comparison-icon home-comparison-icon--check" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </span>
                                <span><strong>تقييم رقمي في مكان واحد</strong><small>اجمع درجات الحكام وتابعها بسهولة</small></span>
                            </li>
                            <li>
                                <span class="home-comparison-icon home-comparison-icon--check" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </span>
                                <span><strong>نتائج فورية وشهادات موثقة</strong><small>أصدرها بسرعة وتحقق منها عبر QR</small></span>
                            </li>
                        </ul>
                        <div class="home-comparison-card__footer home-comparison-card__footer--after">
                            <span>من التسجيل حتى الشهادة... كل شيء في مكان واحد</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-section home-section--white home-workflow-section">
            <div class="container">
                <div class="home-section-heading"><span class="home-eyebrow">الحل: دورة عمل متكاملة</span>
                    <h2>كل مراحل المسابقة مترابطة في نظام واحد</h2>
                    <p>بدل التنقل بين الأوراق والملفات، كل خطوة تنتقل تلقائياً إلى التي بعدها — من الإنشاء حتى التحقق.</p>
                </div>
                <div class="home-workflow-stepper">
                    <div class="home-stepper-line" aria-hidden="true"></div>
                    <div class="home-stepper-track">
                        @foreach([
                        ['إنشاء المسابقة', 'حدد المعلومات والتواريخ وانشر'], ['المستويات والشروط', 'ضع شروط الحفظ والعمر والدرجات'], ['استقبال التسجيلات', 'رابط عام للطلاب ومراجعة فورية'], ['تنظيم اللجان', 'وزّع المشاركين على لجان الاختبار'], ['التقييم الإلكتروني', 'أدخل الدرجات وراجعها مباشرة'], ['إعلان النتائج', 'الترتيب والفائزون يظهرون آلياً'], ['الشهادات والتحقق', 'إصدار فوري وتحقق بـ QR'],
                        ] as $index => [$title, $description])
                        <div class="home-stepper-item"><span class="home-stepper-badge">{{ $index + 1 }}</span>
                            <div class="home-stepper-content">
                                <h3>{{ $title }}</h3>
                                <p>{{ $description }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="home-section home-section--tinted">
            <div class="container">
                <div class="home-section-heading"><span class="home-eyebrow">ماذا يمكنك فعله</span>
                    <h2>كل ما تحتاجه لإدارة المسابقة</h2>
                    <p>أدوات عملية تغطي كل مرحلة — بدون تطبيقات إضافية أو ملفات خارجية.</p>
                </div>
                <div class="home-features-grid">
                    @foreach([
                    ['أنشئ مسابقتك بدقائق', 'حدد التفاصيل والمستويات والشروط وانشر رابط التسجيل فوراً.', '
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />'],
                    ['استقبل التسجيلات إلكترونياً', 'رابط عام يسجل منه الطلاب مباشرة — بدون استمارات ورقية أو إدخال يدوي.', '
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <line x1="19" y1="8" x2="19" y2="14" />
                    <line x1="22" y1="11" x2="16" y2="11" />'],
                    ['نظّم اللجان ووزّع المشاركين', 'أنشئ لجان التقييم ووزّع الطلاب عليها بسهولة.', '
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <rect x="15" y="4" width="7" height="6" rx="1" />'],
                    ['تقييم إلكتروني ومتابعة الدرجات', 'أدخل درجات الحكام وراجعها — بدون كشوف ورقية أو حسابات يدوية.', '
                    <path d="M4 19h16" />
                    <path d="M4 15h16" />
                    <path d="M4 11h16" />
                    <path d="M4 7h16" />
                    <path d="M8 3v18" />'],
                    ['إعلان النتائج وترتيب الفائزين', 'النظام يحسب الدرجات ويرتب المتسابقين آلياً — اعتمد النتائج بضغطة.', '
                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                    <path d="M4 22h16" />
                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2z" />'],
                    ['شهادات موثقة بـ QR', 'أصدر شهادات لجميع المشاركين وتحقق من صحتها بكود QR فريد.', '
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    <polyline points="9 12 11 14 15 10" />'],
                    ] as [$title, $description, $icon])
                    <article class="home-feature-card">
                        <div class="home-feature-card__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg></div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $description }}</p>
                    </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="competitions" class="home-competitions home-section--white">
            <div class="container">
                <div class="home-section-heading"><span class="home-eyebrow">المسابقات المفتوحة</span>
                    <h2>مسابقات القرآن الكريم المتاحة</h2>
                    <p>استعرض المسابقات المفتوحة وسجل بسهولة.</p>
                </div>
                <div class="home-competition-types" aria-label="أنواع المسابقات"><strong>أنواع المسابقات:</strong><span>حفظ القرآن</span><span>التلاوة</span><span>التجويد</span><span>المدارس</span><span>مراكز التحفيظ</span></div>
                @if(($competitions ?? collect())->isNotEmpty())
                <div class="home-competition-grid">
                    @foreach($competitions as $competition)
                    <article class="home-competition-card">
                        <div class="home-competition-card__details">
                            <div class="home-card-heading">
                                <h3>{{ $competition->title }}</h3>
                            </div>
                            <p class="home-card-organizer">الجهة المنظمة: {{ $competition->creator?->organization_name ?: $competition->creator?->name }}</p>
                            <p class="home-card-period">فترة التسجيل: {{ optional($competition->registration_start_date)->translatedFormat('j F Y') }} — {{ optional($competition->registration_end_date)->translatedFormat('j F Y') }}</p>
                            <span class="visually-hidden">{{ $competition->publication_scope === 'nationwide' ? 'متاحة لجميع محافظات مصر' : 'متاحة لسكان محافظة '.$competition->target_governorate }}</span>
                        </div>
                        <a class="btn btn-success btn-sm" href="{{ $competition->creator?->username && $competition->competition_number ? route('competitions.public-register-canonical', [$competition->creator->username, $competition->competition_number]) : route('competitions.public-register', $competition) }}">عرض التفاصيل والتسجيل</a>
                    </article>
                    @endforeach
                </div>
                @else
                <div class="home-empty">
                    <div class="home-empty-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg></div>
                    <h3>لا توجد مسابقات متاحة للتسجيل حالياً</h3>
                    <p>يمكنك العودة لاحقاً عند فتح التسجيل في مسابقات جديدة.</p>
                </div>
                @endif
            </div>
        </section>


        <section class="home-final-cta">
            <div class="container">
                <div class="home-final-cta__content">
                    <h2>مسابقتك القادمة تستحق نظاماً يديرها</h2>
                    <p>أنشئ حساب منظم وابدأ إعداد مسابقتك — من التسجيل حتى الشهادات.</p>
                    <div class="home-actions"><a class="btn btn-success btn-lg" href="{{ route('organizer.register') }}">أنشئ حساب منظم — مجاناً</a><a class="btn btn-outline-light btn-lg" href="#competitions">تصفح المسابقات المتاحة</a></div>
                </div>
            </div>
        </section>
    </main>

    <footer class="home-footer">
        <div class="container">
            <div class="home-footer__top">
                <div class="home-footer__brand"><a class="home-brand" href="{{ url('/') }}"><span><strong>{{ $platformName }}</strong><small>منصة إدارة مسابقات القرآن الكريم</small></span></a></div>
                <div class="home-footer__links-group">
                    <h4>روابط مهمة</h4>
                    <nav class="home-footer__links" aria-label="روابط مهمة"><a href="{{ url('/') }}">الرئيسية</a><a href="#competitions">المسابقات</a><a href="{{ route('registrations.status') }}">متابعة التسجيل</a><a href="{{ route('certificates.verify.form') }}">التحقق من الشهادة</a></nav>
                </div>
                <div class="home-footer__links-group">
                    <h4>للجهات المنظمة</h4>
                    <nav class="home-footer__links" aria-label="روابط الجهات المنظمة"><a href="{{ route('organizer.register') }}">إنشاء حساب</a><a href="{{ route('login') }}">تسجيل الدخول</a></nav>
                </div>
                <nav class="home-footer__social-links" aria-label="روابط التواصل الاجتماعي"><a href="https://wa.me/201064569256" target="_blank" rel="noopener noreferrer" aria-label="واتساب"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .4 5.2.4 11.7c0 2.1.6 4.1 1.6 5.9L.3 24l6.6-1.7a11.7 11.7 0 0 0 5.2 1.2h.1c6.4 0 11.6-5.2 11.6-11.7 0-3.1-1.2-6.1-3.3-8.3ZM12.1 21.5h-.1c-1.7 0-3.4-.5-4.8-1.4l-.3-.2-3.9 1 1-3.8-.2-.3a9.7 9.7 0 0 1-1.5-5.2C2.3 6.3 6.7 2 12.1 2c2.6 0 5 1 6.8 2.8a9.6 9.6 0 0 1 2.8 6.9c0 5.4-4.3 9.8-9.6 9.8Zm5.4-7.3c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-1.6-.8-2.7-1.4-3.8-3.2-.3-.5.3-.5.8-1.7.1-.2 0-.4-.1-.6-.1-.2-.7-1.7-1-2.3-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.2-.9.4-.3.3-1.1 1.1-1.1 2.6s1.1 3 1.2 3.2c.2.2 2.1 3.2 5.1 4.5 1.9.8 2.6.9 3.6.8.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.1-1.4-.1-.2-.3-.3-.6-.4Z" />
                        </svg></a><a href="https://www.linkedin.com/in/moneimbadr/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M5.2 3.5a2.2 2.2 0 1 1-4.4 0 2.2 2.2 0 0 1 4.4 0ZM1.1 8h3.9v12.5H1.1V8Zm6.3 0h3.7v1.7h.1c.5-1 1.8-2 3.7-2 3.9 0 4.6 2.5 4.6 5.8v7h-3.9v-6.2c0-1.5 0-3.4-2.1-3.4s-2.4 1.6-2.4 3.3v6.3H7.4V8Z" />
                        </svg></a></nav>
            </div>
            <div class="home-footer__bottom"><span class="home-footer__tagline">منصة إدارة مسابقات القرآن الكريم بسهولة</span><span>© {{ $platformName }} {{ now()->year }}</span></div>
        </div>
    </footer>
</body>

</html>

@include('errors.layout', [
    'code' => '429',
    'title' => 'محاولات كثيرة',
    'description' => 'تم تجاوز عدد المحاولات المسموح به. يرجى الانتظار ثم المحاولة لاحقاً.',
    'actions' => [
        ['label' => 'إعادة المحاولة', 'href' => url()->current(), 'class' => 'btn-success'],
        ['label' => 'العودة للرئيسية', 'href' => url('/'), 'class' => 'btn-outline-secondary'],
    ],
])

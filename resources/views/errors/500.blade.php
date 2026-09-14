@include('errors.layout', [
    'code' => '500',
    'title' => 'حدث خطأ غير متوقع',
    'description' => 'تعذر تنفيذ الطلب. يرجى المحاولة مرة أخرى.',
    'actions' => [
        ['label' => 'العودة للرئيسية', 'href' => url('/'), 'class' => 'btn-success'],
        ['label' => 'المحاولة مرة أخرى', 'href' => url()->current(), 'class' => 'btn-outline-secondary'],
    ],
])

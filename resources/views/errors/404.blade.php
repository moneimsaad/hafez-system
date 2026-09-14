@include('errors.layout', [
    'code' => '404',
    'title' => 'الصفحة غير موجودة',
    'description' => 'الرابط المطلوب غير متاح أو ربما تم تغييره.',
    'actions' => [
        ['label' => 'العودة للرئيسية', 'href' => url('/'), 'class' => 'btn-success'],
        ['label' => 'الرجوع للخلف', 'href' => url()->previous() ?: url('/'), 'class' => 'btn-outline-secondary'],
    ],
])

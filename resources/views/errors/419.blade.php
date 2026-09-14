@include('errors.layout', [
    'code' => '419',
    'title' => 'انتهت الجلسة',
    'description' => 'انتهت صلاحية الصفحة. حدّث الصفحة ثم أرسل الطلب مرة أخرى.',
    'actions' => [
        ['label' => 'تحديث الصفحة', 'href' => url()->current(), 'class' => 'btn-success'],
        ['label' => 'العودة', 'href' => url()->previous() ?: url('/'), 'class' => 'btn-outline-secondary'],
    ],
])

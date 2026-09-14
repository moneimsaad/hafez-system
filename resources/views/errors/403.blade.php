@include('errors.layout', [
    'code' => '403',
    'title' => 'لا تملك صلاحية الوصول',
    'description' => 'الحساب الحالي غير مصرح له بتنفيذ هذا الإجراء.',
    'actions' => [
        ['label' => 'العودة للوحة التحكم', 'href' => route('dashboard'), 'class' => 'btn-success'],
        ['label' => 'العودة للرئيسية', 'href' => url('/'), 'class' => 'btn-outline-secondary'],
    ],
])

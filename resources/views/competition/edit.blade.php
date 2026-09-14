<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="small text-success fw-bold mb-1">إدارة المسابقات</p>
            <h1 class="h3 mb-0">تعديل المسابقة</h1>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">
                @include('competition.partials.form', ['competition' => $competition, 'formAction' => route('competitions.update', $competition), 'formMethod' => 'PUT', 'submitLabel' => 'حفظ التعديلات'])</div>
        </div>
    </div>
</x-app-layout>
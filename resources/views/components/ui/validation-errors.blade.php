@if($errors->any())
    <div class="alert alert-danger" role="alert" aria-live="polite">
        <p class="fw-semibold mb-2">يرجى مراجعة البيانات التالية:</p>
        <ul class="mb-0">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif

@props(['paginator' => null])
@if($paginator && $paginator->hasPages())<div class="mt-4 d-flex justify-content-center">{{ $paginator->onEachSide(1)->links() }}</div>@endif

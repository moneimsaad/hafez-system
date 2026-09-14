@props(['headers' => [], 'emptyMessage' => 'لا توجد بيانات'])
<div class="table-responsive hafez-table-wrap" role="region" aria-label="جدول البيانات" tabindex="0"><table {{ $attributes->merge(['class' => 'table table-hover align-middle mb-0']) }}><thead><tr>@foreach($headers as $header)<th scope="col">{{ $header }}</th>@endforeach</tr></thead><tbody>{{ $slot }}</tbody></table></div>

@props(['rules' => null, 'heading' => 'معايير التقييم'])

@php($scoringRules = app(\App\Services\CompetitionScoringRulesService::class))
@php($criteria = $scoringRules->effectiveCriteria($rules))

@if($criteria)
    <section {{ $attributes->merge(['class' => 'hafez-scoring-criteria']) }} aria-label="{{ $heading }}">
        <h2 class="h6 text-success mb-3">{{ $heading }}</h2>
        <div class="hafez-scoring-criteria__list">
            @foreach($criteria as $criterion)
                <div><span>{{ $criterion['name'] }}</span><strong><span dir="ltr">{{ rtrim(rtrim(number_format($criterion['max_score'], 2, '.', ''), '0'), '.') }}</span> درجة</strong></div>
            @endforeach
        </div>
        <p class="hafez-scoring-criteria__total mb-0">إجمالي الدرجات: <strong dir="ltr">{{ rtrim(rtrim(number_format($scoringRules->effectiveMaximumScore($rules), 2, '.', ''), '0'), '.') }}</strong></p>
    </section>
@endif

<div class="hafez-quick-access-grid" role="list">
    @if(auth()->user()->role === 'User')
        <a href="{{ route('competitions.create') }}" class="hafez-action-tile hafez-action-tile--primary" role="listitem"><x-ui.sidebar-icon name="competition" class="hafez-action-tile__icon" /><span>إنشاء مسابقة</span></a>
    @endif
    <a href="{{ route('competitions.index') }}" class="hafez-action-tile" role="listitem"><x-ui.sidebar-icon name="competition" class="hafez-action-tile__icon" /><span>{{ auth()->user()->role === 'User' ? 'مسابقاتي' : 'عرض المسابقات' }}</span></a>
    <a href="{{ route('registrations.index') }}" class="hafez-action-tile" role="listitem"><x-ui.sidebar-icon name="registrations" class="hafez-action-tile__icon" /><span>متابعة التسجيلات</span></a>
    <a href="{{ route('committees.index') }}" class="hafez-action-tile" role="listitem"><x-ui.sidebar-icon name="committees" class="hafez-action-tile__icon" /><span>اللجان</span></a>
    <a href="{{ route('evaluations.index') }}" class="hafez-action-tile" role="listitem"><x-ui.sidebar-icon name="evaluation" class="hafez-action-tile__icon" /><span>التقييمات</span></a>
    <a href="{{ route('results.index') }}" class="hafez-action-tile" role="listitem"><x-ui.sidebar-icon name="results" class="hafez-action-tile__icon" /><span>مراجعة النتائج</span></a>
</div>

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="small text-success fw-bold mb-1">إدخال الدرجات</p>
            <h1 class="h3 mb-0">تقييم جديد</h1>
        </div>
    </x-slot>
    <div class="container-fluid py-4 px-3 px-lg-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">
                @if($assignments->isEmpty())<x-ui.empty-state message="لا يوجد طلاب جدد مكلّفون بالتقييم حاليًا، أو تم تقييم جميع الطلاب المسندين بالفعل." />
                @else<form method="POST" action="{{ route('evaluations.store') }}" class="card hafez-card border-0" novalidate data-hafez-submit>
                    @csrf<input type="hidden" name="judge_id" value="{{ $evaluator->id }}">
                    <input type="hidden" name="competition_id" id="competition_id">
                    <input type="hidden" name="branch_id" id="branch_id">
                    <input type="hidden" name="student_id" id="student_id">
                    <div class="card-body p-4 p-lg-5">
                        <x-ui.validation-errors />
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h2 class="h6 text-success">بيانات الطالب والتكليف</h2>
                                <select name="registration_id" id="registration_id" class="form-select mb-3" required>
                                    <option value="">اختر الطالب</option>
                                    @foreach($assignments as $assignment)<option value="{{ $assignment->registration_id }}" data-competition="{{ $assignment->committee->competition_id }}" data-branch="{{ $assignment->committee->branch_id }}" data-student="{{ $assignment->student_id }}" data-name="{{ $assignment->student?->full_name }}" data-competition-name="{{ $assignment->committee->competition?->title }}" data-branch-name="{{ $assignment->committee->competitionBranch?->name }}" data-criteria='@json(app(\App\Services\CompetitionScoringRulesService::class)->criteriaForBranch($assignment->committee->competitionBranch))'>{{ $assignment->student?->full_name }} - {{ $assignment->committee->name }}</option>
                                    @endforeach
                                </select>
                                <div id="assignmentSummary" class="row g-2 small text-muted">
                                    <div class="col-md-4">الطالب: <strong id="studentName">-</strong>
                                    </div>
                                    <div class="col-md-4">المسابقة: <strong id="competitionName">-</strong>
                                    </div>
                                    <div class="col-md-4">المستوى: <strong id="branchName">-</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h2 class="h6 text-success border-bottom pb-2">درجات التقييم</h2>
                        <p id="scoreHint" class="small text-muted">اختر الطالب لإظهار معايير التقييم الخاصة بمسابقته.</p>
                        <div id="scoreFields" class="row g-3"></div>
                        <div id="scoreTotal" class="alert alert-success mt-3 d-none mb-0">المجموع: <strong dir="ltr" data-total>0</strong> من <strong dir="ltr" data-maximum>0</strong></div>
                        <div class="row g-3 mt-0">
                            <div class="col-12">
                                <x-ui.textarea-input name="notes" label="ملاحظات" rows="3" />
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 px-4 pb-4 d-flex justify-content-end">
                        <button class="btn btn-success px-4" type="submit">حفظ التقييم</button>
                    </div>
                </form>
                <script>
                    const s = document.getElementById('registration_id');
                    const scoreFields = document.getElementById('scoreFields');
                    const scoreHint = document.getElementById('scoreHint');
                    const scoreTotal = document.getElementById('scoreTotal');
                    const formatScore = (value) => Number.isInteger(value) ? value : value.toFixed(2);
                    const refreshTotal = () => {
                        const inputs = [...scoreFields.querySelectorAll('input[type="number"]')];
                        const total = inputs.reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
                        const maximum = inputs.reduce((sum, input) => sum + (parseFloat(input.max) || 0), 0);
                        scoreTotal.querySelector('[data-total]').textContent = formatScore(total);
                        scoreTotal.querySelector('[data-maximum]').textContent = formatScore(maximum);
                    };
                    const renderScores = (criteria) => {
                        scoreFields.replaceChildren();
                        criteria.forEach((criterion, index) => {
                            const column = document.createElement('div');
                            column.className = 'col-12 col-md-6';
                            const label = document.createElement('label');
                            label.className = 'form-label';
                            label.htmlFor = `score_${index}`;
                            label.textContent = `${criterion.name} (من ${criterion.max_score})`;
                            const input = document.createElement('input');
                            input.className = 'form-control';
                            input.id = `score_${index}`;
                            input.name = `scores[${index}][score]`;
                            input.type = 'number';
                            input.min = '0';
                            input.max = criterion.max_score;
                            input.step = '0.01';
                            input.required = true;
                            input.inputMode = 'decimal';
                            input.dir = 'ltr';
                            input.addEventListener('input', refreshTotal);
                            column.append(label, input);
                            scoreFields.append(column);
                        });
                        scoreHint.textContent = 'أدخل درجة كل معيار ضمن درجته القصوى.';
                        scoreTotal.classList.toggle('d-none', criteria.length === 0);
                        refreshTotal();
                    };
                    s.addEventListener('change', function() {
                        const o = this.options[this.selectedIndex];
                        document.getElementById('competition_id').value = o.dataset.competition || '';
                        document.getElementById('branch_id').value = o.dataset.branch || '';
                        document.getElementById('student_id').value = o.dataset.student || '';
                        document.getElementById('studentName').textContent = o.dataset.name || '-';
                        document.getElementById('competitionName').textContent = o.dataset.competitionName || '-';
                        document.getElementById('branchName').textContent = o.dataset.branchName || '-';
                        try {
                            renderScores(o.dataset.criteria ? JSON.parse(o.dataset.criteria) : []);
                        } catch (error) {
                            renderScores([]);
                        }
                    });
                </script>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

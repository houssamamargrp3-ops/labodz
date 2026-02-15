@extends('Adminstration.layout')

@section('title', 'تقييم الأهلية الطبية')

@section('content')
<div class="section-header">
    <h2><i class="fas fa-stethoscope"></i> تقييم الأهلية الطبية الموحد</h2>
    <p>المريض: <strong>{{ $patient->name }}</strong> | حجز موعد: <strong>{{ $reservation->analysis_date }} {{ $reservation->time }}</strong></p>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        @php
            $hasQuestions = false;
            foreach($reservation->reservationAnalyses as $resAnalysis) {
                if($resAnalysis->analyse->questions->count() > 0) {
                    $hasQuestions = true;
                    break;
                }
            }
        @endphp

        @if($hasQuestions)
            <form id="eligibility-form" action="{{ route('admin.bookings.full-eligibility.submit', $reservation->id) }}" method="POST">
                @csrf
                
                <div class="analyses-questions">
                    @foreach($reservation->reservationAnalyses as $resAnalysis)
                        @if($resAnalysis->analyse->questions->count() > 0)
                            <div class="analysis-group mb-5 p-3 border rounded bg-light">
                                <h4 class="mb-4 text-dark border-bottom pb-2">
                                    <i class="fas fa-microscope text-info me-2"></i>
                                    {{ $resAnalysis->analyse->name }}
                                </h4>
                                
                                @foreach($resAnalysis->analyse->questions as $question)
                                    <div class="question-item mb-4 pb-3 border-bottom">
                                        <h5 class="mb-3 text-primary">{{ $question->question }}</h5>
                                        <div class="options-container d-flex flex-wrap gap-4">
                                            @foreach($question->options as $option)
                                                <div class="form-check custom-radio">
                                                    <input class="form-check-input" type="radio" 
                                                           name="answers[{{ $question->id }}]" 
                                                           id="option_{{ $option->id }}" 
                                                           value="{{ $option->id }}" 
                                                           required>
                                                    <label class="form-check-label px-2" for="option_{{ $option->id }}">
                                                        {{ $option->text }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-2">
                    <a href="{{ route('reservations') }}" class="btn btn-outline-secondary px-4">
                        <i class="fas fa-arrow-right me-2"></i> إلغاء العودة
                    </a>
                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" id="submit-check" class="btn btn-primary btn-lg px-5 shadow">
                            <i class="fas fa-check-circle me-2"></i> تأكيد التقييم لجميع التحاليل
                        </button>
                        <div id="loading-spinner" class="d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Results Container -->
            <div id="results-container" class="mt-5 d-none">
                <hr class="my-5">
                <h3 class="mb-4 text-center"><i class="fas fa-poll-h me-2"></i> نتائج التقييم النهائي</h3>
                <div id="results-list" class="row g-4">
                    <!-- Results will be injected here -->
                </div>
                <div class="text-center mt-5">
                    <a href="{{ route('reservations') }}" class="btn btn-success btn-lg px-5">
                        <i class="fas fa-tasks me-2"></i> العودة لإدارة الحجوزات
                    </a>
                </div>
            </div>
        @else
            <div class="no-questions text-center py-5">
                <i class="fas fa-info-circle fa-4x text-muted mb-3"></i>
                <h4 class="text-secondary">لا توجد أسئلة بروتوكولية للتحاليل في هذا الحجز</h4>
                <p class="text-muted">هذه التحاليل لا تتطلب فحص أهلية خاص. يمكنك العودة لإدارة الحجوزات.</p>
                <a href="{{ route('reservations') }}" class="btn btn-primary mt-3 px-4">العودة للحجوزات</a>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('eligibility-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submit-check');
        const spinner = document.getElementById('loading-spinner');
        const resultsContainer = document.getElementById('results-container');
        const resultsList = document.getElementById('results-list');
        
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            spinner.classList.add('d-none');
            resultsContainer.classList.remove('d-none');
            resultsList.innerHTML = '';
            
            data.results.forEach(result => {
                const col = document.createElement('div');
                col.className = 'col-md-6';
                
                let cardClass = 'bg-light';
                let iconClass = 'fa-check-circle text-success';
                let statusText = 'جاهز للتنفيذ';
                
                if (result.status === 'blocked') {
                    cardClass = 'border-danger bg-danger-subtle';
                    iconClass = 'fa-times-circle text-danger';
                    statusText = 'محظور';
                } else if (result.status === 'warning') {
                    cardClass = 'border-warning bg-warning-subtle';
                    iconClass = 'fa-exclamation-triangle text-warning';
                    statusText = 'تنبيه / مراجعة';
                } else if (result.status === 'pending_approval') {
                    cardClass = 'border-info bg-info-subtle';
                    iconClass = 'fa-clock text-info';
                    statusText = 'بانتظار الموافقة';
                }
                
                col.innerHTML = `
                    <div class="card h-100 border-2 shadow-sm ${cardClass}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">${result.name}</h5>
                                <i class="fas ${iconClass} fa-2x"></i>
                            </div>
                            <p class="card-text">
                                <strong>الحالة الدراسية:</strong> ${statusText}
                            </p>
                            ${result.reason ? `<p class="card-text text-muted small"><i class="fas fa-info-circle me-1"></i> ${result.reason}</p>` : ''}
                        </div>
                    </div>
                `;
                resultsList.appendChild(col);
            });
            
            // Scroll to results
            resultsContainer.scrollIntoView({ behavior: 'smooth' });
        })
        .catch(error => {
            console.error('Error:', error);
            spinner.classList.add('d-none');
            submitBtn.disabled = false;
            alert('حدث خطأ أثناء معالجة الطلب. يرجى المحاولة مرة أخرى.');
        });
    });
});
</script>

<style>
    .bg-danger-subtle { background-color: #fff5f5; border-color: #feb2b2; }
    .bg-warning-subtle { background-color: #fffaf0; border-color: #fbd38d; }
    .bg-info-subtle { background-color: #ebf8ff; border-color: #90cdf4; }
    
    .section-header h2 {
        color: #2d3748;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    .question-item h5 {
        font-weight: 600;
        line-height: 1.5;
    }
    .custom-radio .form-check-input:checked {
        background-color: #3182ce;
        border-color: #3182ce;
    }
    .custom-radio .form-check-label {
        font-size: 1.1rem;
        cursor: pointer;
        transition: color 0.2s;
    }
    .custom-radio:hover .form-check-label {
        color: #3182ce;
    }
    .btn-primary {
        background-color: #3182ce;
        border-color: #3182ce;
    }
    .btn-primary:hover {
        background-color: #2b6cb0;
        border-color: #2b6cb0;
    }
    
    #results-container {
        animation: fadeInUp 0.5s ease-out;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection

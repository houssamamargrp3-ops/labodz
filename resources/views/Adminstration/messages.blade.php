@extends('Adminstration.layout')

@section('title', 'إرسال الرسائل')

@section('content')
<div class="section-header">
    <h2><i class="fas fa-envelope"></i> إرسال الرسائل والنتائج</h2>
</div>

<div class="messages-container">
    <!-- Statistics Cards -->
    <div class="messages-stats-cards">
        <div class="messages-stat-card">
            <div class="messages-stat-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="messages-stat-info">
                <h3>{{ App\Models\Message::count() }}</h3>
                <p>إجمالي الرسائل</p>
            </div>
        </div>
        <div class="messages-stat-card">
            <div class="messages-stat-icon unread">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div class="messages-stat-info">
                <h3>{{ App\Models\Message::where('is_read', false)->count() }}</h3>
                <p>الرسائل غير المقروءة</p>
            </div>
        </div>
        <div class="messages-stat-card">
            <div class="messages-stat-icon patients">
                <i class="fas fa-users"></i>
            </div>
            <div class="messages-stat-info">
                <h3>{{ App\Models\Patient::count() }}</h3>
                <p>إجمالي المرضى</p>
            </div>
        </div>
    </div>

    <div class="messages-layout">
        <!-- Recent Messages Section -->
        <div class="messages-section">
            <h3><i class="fas fa-inbox"></i> الرسائل الواردة</h3>
            
            @if($messages->count() > 0)
                <div class="messages-list">
                    @foreach($messages as $message)
                    <div class="message-item {{ !$message->is_read ? 'unread' : '' }}" 
                         onclick="markAsRead({{ $message->id }})">
                        <div class="message-header">
                            <div class="message-sender">
                                <strong>{{ $message->patient_name }}</strong>
                                <span class="message-email">{{ $message->patient_email }}</span>
                            </div>
                            <div class="message-actions">
                                <span class="message-time">{{ $message->created_at->diffForHumans() }}</span>
                                <form action="{{ route('messages.delete', $message->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذه الرسالة؟')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @if(!$message->is_read)
                                    <form action="{{ route('messages.markAsRead', $message->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-success btn-sm" title="تعيين كمقروء">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <div class="message-body">
                            <p>{{ Str::limit($message->message, 150) }}</p>
                        </div>
                        @if(!$message->is_read)
                            <div class="unread-badge">جديد</div>
                        @endif
                    </div>
                    @endforeach
                </div>
                
                <div class="pagination-container">
                    {{ $messages->links() }}
                </div>
            @else
                <div class="no-data">
                    <i class="fas fa-envelope-open"></i>
                    <p>لا توجد رسائل واردة</p>
                </div>
            @endif
        </div>

        <!-- Send Messages Section -->
        <div class="send-messages-section">
            <!-- Send General Message -->
            <div class="message-form-card">
                <h4><i class="fas fa-paper-plane"></i> إرسال رسالة عامة</h4>
                <form action="{{ route('messages.send') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>اختر المريض *</label>
                        <select name="patient_id" class="form-control" required>
                            <option value="">اختر المريض</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }} - {{ $patient->phone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>عنوان الرسالة *</label>
                        <input type="text" name="subject" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>نص الرسالة *</label>
                        <textarea name="message" class="form-control" rows="5" required placeholder="اكتب نص الرسالة هنا..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>مرفق (اختياري)</label>
                        <input type="file" name="attachment" class="form-control">
                        <small class="text-muted">يمكن رفع ملفات PDF, Word, أو صور</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i> إرسال الرسالة
                    </button>
                </form>
            </div>

            <!-- Send Test Results -->
            <div class="message-form-card">
                <h4><i class="fas fa-file-medical-alt"></i> إرسال نتائج التحاليل</h4>
                <form action="{{ route('messages.send-result') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>اختر حجز المريض *</label>
                        <select name="reservation_id" class="form-control" required>
                            <option value="">اختر حجز المريض (Visit)</option>
                            @foreach($patients as $patient)
                                @foreach($patient->reservations as $reservation)
                                    @if($reservation->status != 'completed')
                                        <option value="{{ $reservation->id }}">
                                            {{ $patient->name }} - [{{ $reservation->reservationAnalyses->map(fn($ra) => $ra->analyse->name)->implode(', ') }}] - {{ $reservation->analysis_date }}
                                        </option>
                                    @endif
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>ملاحظات إضافية</label>
                        <textarea name="additional_notes" class="form-control" rows="3" placeholder="ملاحظات حول نتيجة التحليل..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>رفع نتيجة التحليل (PDF أو صورة)</label>
                        <input type="file" name="result_file" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-file-medical-alt"></i> إرسال النتيجة
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection
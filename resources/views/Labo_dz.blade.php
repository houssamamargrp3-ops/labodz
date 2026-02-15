<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>مخبر المنيعة - Labo_dz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>
<script src="{{ asset('js/app.js') }}"></script>

<body>
    <!-- Global Dynamic Background -->
    <div class="global-bg">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Navigation -->
    <nav>
        <ul class="nav-links">
            <li><a href="#home"><i class="fas fa-home"></i> الرئيسية</a></li>
            <li><a href="#features"><i class="fas fa-star"></i> المميزات</a></li>
            <li><a href="#analysis"><i class="fas fa-flask"></i> التحاليل</a></li>
            <li><a href="#tips"><i class="fas fa-lightbulb"></i> نصائح</a></li>
            <li><a href="#booking"><i class="fas fa-calendar-check"></i> حجز موعد</a></li>
            <li><a href="#contact"><i class="fas fa-envelope"></i> اتصل بنا</a></li>
        </ul>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <h1>مرحباً بكم في مخبر المنيعة</h1>
        <p>نقدم خدمات تحليلية دقيقة باستخدام أحدث التقنيات الطبية والكوادر المؤهلة</p>
        <a href="#booking" class="cta-button">احجز موعدك الآن <i class="fas fa-arrow-left"></i></a>
    </section>

    <!-- Features Section -->
    <section id="features" class="section">
        <div class="container">
            <h2><i class="fas fa-star"></i> مميزات مخبرنا</h2>
            <p>يتميز مخبر المنيعة بتقديم خدمات عالية الجودة باستخدام أحدث التقنيات والتحليلات المتقدمة لضمان تشخيص دقيق وسريع.</p>

            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-microscope"></i>
                    <h3>أحدث الأجهزة</h3>
                    <p>نستخدم أحدث الأجهزة الطبية والتقنيات المتطورة لضمان دقة النتائج</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-user-md"></i>
                    <h3>كفاءات طبية</h3>
                    <p>فريق طبي مؤهل وذو خبرة عالية في مجال التحاليل الطبية</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-bolt"></i>
                    <h3>نتائج سريعة</h3>
                    <p>تقديم النتائج في أسرع وقت ممكن مع الحفاظ على الدقة التامة</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-headset"></i>
                    <h3>دعم مستمر</h3>
                    <p>خدمة عملاء على مدار الساعة للرد على استفساراتكم وتقديم الدعم</p>
                </div>
            </div>
        </div>
    </section>


    <!-- Analysis Section -->
    <section id="analysis" class="section">
        <div class="container">
            <h2><i class="fas fa-flask"></i> قائمة التحاليل المتاحة</h2>
            <p>نقدم مجموعة واسعة من التحاليل الطبية الدقيقة باستخدام أحدث التقنيات</p>
            <div class="analysis-list">
                @foreach($analyses as $analysis)
                <div class="analysis-item">
                    <span>{{ $analysis->name }}</span>
                    @if($analysis->availability == 1)
                    <button class='status-btn available'>متوفر</button>
                    @else
                    <button class='status-btn unavailable'>غير متوفر</button>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Analysis Info Button Section -->
    <section id="tips" class="section">
        <div class="container">
            <h2><i class="fas fa-lightbulb"></i> نصائح قبل إجراء التحاليل</h2>
            <p>للحصول على جميع المعلومات حول التحاليل ونصائح الإعداد، يرجى الضغط على الزر أدناه</p>
            <a href="{{ route('analysis.info') }}" class="cta-button">عرض معلومات التحاليل <i class="fas fa-arrow-left"></i></a>
        </div>
    </section>

    <!-- Booking Section -->
    <section id="booking" class="section">
        <div class="container">
            <h2><i class="fas fa-calendar-check"></i> حجز موعد</h2>
            <p>يرجى ملء النموذج التالي لحجز موعد في مخبرنا وسنتصل بك لتأكيد الحجز</p>
            <form id="bookingForm" action="{{ route('booking') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="name">الاسم الكامل</label>
                    <input type="text" id="name" name="name" placeholder="أدخل اسمك الكامل" required>
                </div>
                <div class="form-group">
                    <label for="phone">رقم الهاتف</label>
                    <input type="tel" id="phone" name="phone" placeholder="أدخل رقم هاتفك" required>
                </div>
                <div class="form-group">
                    <label for="email">البريد الإلكتروني</label>
                    <input type="email" id="email" name="email" placeholder="أدخل بريدك الإلكتروني">
                </div>
                <div class="form-group">
                    <label for="gender">الجنس</label>
                    <select id="gender" name="gender" required>
                        <option value="">اختر الجنس</option>
                        <option value="male">ذكر</option>
                        <option value="female">أنثى</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="birth_date">تاريخ الميلاد</label>
                    <input type="date" id="birth_date" name="birth_date" required>
                </div>
                <div class="form-group">
                    <label>أنواع التحاليل <span class="required">*</span></label>

                    <div class="checkbox-grid">
                      @foreach ($analyses as $analysis)
                        @if($analysis->availability == 1)
                       <div class="checkbox-item">
                  <input type="checkbox" name="analysisTypes[]" value="{{ $analysis->id }}" id="analysis_{{ $analysis->id }}">
                 <label for="analysis_{{ $analysis->id }}">{{ $analysis->name }}</label>
                  </div>
                       @endif
                           @endforeach
                           </div>
                            <small class="form-text text-muted">يمكنك اختيار تحليل واحد أو أكثر من القائمة أعلاه</small>
                </div>
                <div class="form-group">
                    <label for="date">التاريخ</label>
                    <input type="date" id="date" name="date" required>
                </div>
                <div class="form-group">
                    <label for="time">الوقت</label>
                    <input type="time" id="time" name="time" required>
                </div>
                <button type="submit"><i class="fas fa-paper-plane"></i> تأكيد الحجز</button>
            </form>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section">
        <div class="container">
            <h2><i class="fas fa-envelope"></i> اتصل بنا</h2>
            <p>للاستفسارات أو الشكاوى، يرجى تعبئة النموذج التالي وسنرد عليكم في أقرب وقت</p>
            <form id="contactForm" action={{route('message')}} method="POST">
                @csrf
                <div class="form-group">
                    <label for="contact_name">الاسم</label>
                    <input type="text" name="name" id="contact_name" placeholder="أدخل اسمك" required>
                </div>
                <div class="form-group">
                    <label for="contact_email">البريد الإلكتروني</label>
                    <input type="email" id="contact_email" name="email" placeholder="أدخل بريدك الإلكتروني" required>
                </div>
                <div class="form-group">
                    <label for="message">الرسالة</label>
                    <textarea id="message" name="message" rows="5" placeholder="اكتب رسالتك هنا..." required></textarea>
                </div>
                <button type="submit"><i class="fas fa-paper-plane"></i> إرسال الرسالة</button>
            </form>
        </div>
    </section>

    <!-- Map Section -->
    <section class="section">
        <div class="container">
            <h2><i class="fas fa-map-marker-alt"></i> موقعنا</h2>
            <p>يمكنكم زيارة مخبرنا في العنوان التالي أو الاتصال بنا للحصول على التوجيهات</p>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5693.052425601013!2d5.262623025838582!3d31.957933404038677!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x125d69d1688915f9%3A0xc65def288f0e9a57!2sLaboratoire%20Bela%C3%AFd%20d&#39;analyse%20m%C3%A9dical!5e0!3m2!1sen!2sdz!4v1761573361428!5m2!1sen!2sdz" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>مخبر المنيعة</h3>
                <p>نقدم خدمات تحليلية دقيقة باستخدام أحدث التقنيات الطبية والكوادر المؤهلة لتقديم أفضل خدمة للمرضى.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>روابط سريعة</h3>
                <a href="#home">الرئيسية</a>
                <a href="#features">المميزات</a>
                <a href="#analysis">التحاليل</a>
                <a href="#booking">حجز موعد</a>
                <a href="#contact">اتصل بنا</a>
            </div>
            <div class="footer-section">
                <h3>معلومات الاتصال</h3>
                <p><i class="fas fa-map-marker-alt"></i> العنوان: شارع الاستقلال، المنيعة</p>
                <p><i class="fas fa-phone"></i> الهاتف: 0550123456</p>
                <p><i class="fas fa-envelope"></i> البريد: info@labo-dz.com</p>
                <p><i class="fas fa-clock"></i> أوقات العمل: 8:00 - 18:00</p>
            </div>
        </div>
        <div class="copyright">
            <p>جميع الحقوق محفوظة &copy; 2023 مخبر المنيعة - Labo_dz</p>
        </div>
    </footer>

    {{-- Auto-trigger PDF download and form validation --}}
    <script>
        window.addEventListener('load', function() {
            // 1. Auto-download PDF if session exists
            @if(session('download_pdf'))
                var reservationId = {{ session('download_pdf') }};
                var downloadUrl = '{{ url("/reservation") }}/' + reservationId + '/pdf';
                
                var link = document.createElement('a');
                link.href = downloadUrl;
                link.download = 'reservation_confirmation.pdf';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            @endif

            // 2. Client-side validation for analysis selection
            const bookingForm = document.getElementById('bookingForm');
            if (bookingForm) {
                bookingForm.addEventListener('submit', function(e) {
                    const checkboxes = this.querySelectorAll('input[name="analysisTypes[]"]:checked');
                    if (checkboxes.length === 0) {
                        e.preventDefault();
                        alert('يرجى اختيار تحليل واحد على الأقل');
                    }
                });
            }
        });
    </script>
</body>

</html>
<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\Patient;
use App\Models\PatientAnswer;
use App\Models\Question;
use App\Models\Reminder;
use App\Models\Request_reservation;
use App\Models\Reservation;
use App\Models\ReservationAnalysis;
use App\Services\AnalysisEligibilityService;
use Illuminate\Http\Request;

class reservationsController extends Controller
{
    public function reservations(Request $request)
    {
        // Start the query, eager loading patient and analyses
        $query = Reservation::with(['patient', 'reservationAnalyses.analyse']);

        // Apply date filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('analysis_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('analysis_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('analysis_date', '<=', $request->end_date);
        }

        // Filter by booking status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search for patient by name or phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }

        // Fetch booked reservations, newest first
        $bookings = $query->orderByDesc('analysis_date')
            ->orderByDesc('time')
            ->paginate(10);

        return view('Adminstration.reservations', [
            'bookings' => $bookings,
        ]);
    }

    public function filterReservations(Request $request)
    {
        // This method handles the filter form submission
        // It redirects back to reservations with query parameters
        return $this->reservations($request);
    }

    // Add method to update booking status
    public function updateBookingStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:booked,ready,blocked,warning,pending_approval,completed',
        ]);

        $reservation = Reservation::findOrFail($id);
        $reservation->update(['status' => $request->status]);

        return redirect()->route('reservations')->with('success', 'تم تحديث حالة الحجز بنجاح');
    }

    // Show reservation requests
    public function reservationRequests(Request $request)
    {
        $query = Request_reservation::with(['analyse']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }

        $requests = $query->orderByDesc('created_at')->paginate(10);

        return view('Adminstration.reservation-requests', [
            'requests' => $requests,
        ]);
    }

    // Confirm reservation request
    public function confirmRequest(Request $request, $id)
    {
        $reservationRequest = Request_reservation::with('analyses')->findOrFail($id);

        if ($reservationRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'هذا الطلب تمت معالجته بالفعل');
        }

        $request->validate([
            'analysis_date' => 'required|date',
            'time' => 'required',
            'admin_notes' => 'nullable|string',
        ]);

        try {
            // Create patient record
            $patient = Patient::create([
                'name' => $reservationRequest->name,
                'email' => $reservationRequest->email,
                'phone' => $reservationRequest->phone,
                'gender' => $reservationRequest->gender,
                'birth_date' => $reservationRequest->birth_date,
            ]);

            // Create ONE parent reservation
            $reservation = Reservation::create([
                'patient_id' => $patient->id,
                'analysis_date' => $request->analysis_date,
                'time' => $request->time,
                'status' => 'booked',
            ]);

            // Create linked reservation analyses
            foreach ($reservationRequest->analyses as $analyse) {
                $resAnalysis = ReservationAnalysis::create([
                    'reservation_id' => $reservation->id,
                    'analysis_id' => $analyse->id,
                    'status' => 'booked',
                ]);

                // Also create a history record as it might be used for medical records/results
                $history = History::create([
                    'patient_id' => $patient->id,
                    'analyse_id' => $analyse->id,
                    'analysis_date' => $request->analysis_date,
                    'time' => $request->time,
                    'status' => 'booked',
                    'result' => null,
                ]);

                // Create reminder for the reservation analysis
                Reminder::create([
                    'history_id' => $history->id,
                    'reservation_id' => $reservation->id,
                    'patient_id' => $patient->id,
                    'analyse_id' => $analyse->id,
                    'scheduled_for' => \Carbon\Carbon::parse($request->analysis_date)->subDay(),
                    'is_sent' => false,
                ]);
            }

            // Update reservation request
            $reservationRequest->update([
                'status' => 'confirmed',
                'patient_id' => $patient->id,
                'reservation_id' => $reservation->id,
                'admin_notes' => $request->admin_notes,
            ]);

            return redirect()->route('reservation.requests')->with('success', 'تم تأكيد الطلب وإنشاء الحجز بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء تأكيد الطلب: '.$e->getMessage());
        }
    }

    // Reject reservation request
    public function rejectRequest(Request $request, $id)
    {
        $reservationRequest = Request_reservation::findOrFail($id);

        if ($reservationRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'هذا الطلب تمت معالجته بالفعل');
        }

        $request->validate([
            'admin_notes' => 'required|string',
        ]);

        $reservationRequest->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
        ]);

        return redirect()->route('reservation.requests')->with('success', 'تم رفض الطلب بنجاح');
    }

    /**
     * Check eligibility during the execution phase.
     */
    public function checkExecutionEligibility(AnalysisEligibilityService $eligibilityService, $id)
    {
        $resAnalysis = ReservationAnalysis::findOrFail($id);

        // Call the eligibility service
        $result = $eligibilityService->checkEligibility($resAnalysis->reservation->patient_id, $resAnalysis->analysis_id);

        $statusMap = [
            'block' => 'blocked',
            'warning' => 'warning',
            'approval' => 'pending_approval',
            'eligible' => 'ready',
        ];

        $newStatus = $statusMap[$result['status']] ?? 'ready';

        $resAnalysis->update(['status' => $newStatus]);

        return response()->json([
            'status' => $newStatus,
            'reason' => $result['reason'] ?? null,
            'original_action' => $result['status'],
        ]);
    }

    /**
     * Show the eligibility check form for a specific reservation analysis.
     */
    public function showEligibilityCheck($id)
    {
        $resAnalysis = ReservationAnalysis::with(['reservation.patient', 'analyse.questions.options'])->findOrFail($id);

        $questions = Question::where('analyse_id', $resAnalysis->analysis_id)->with('options')->get();

        return view('Adminstration.eligibility-check', [
            'booking' => $resAnalysis,
            'questions' => $questions,
        ]);
    }

    /**
     * Process the eligibility check answers.
     */
    public function submitEligibilityCheck(Request $request, AnalysisEligibilityService $eligibilityService, $id)
    {
        $resAnalysis = ReservationAnalysis::findOrFail($id);

        $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'exists:options,id',
        ]);

        // 1. Save answers
        foreach ($request->answers as $questionId => $optionId) {
            PatientAnswer::updateOrCreate(
                [
                    'patient_id' => $resAnalysis->reservation->patient_id,
                    'question_id' => $questionId,
                ],
                ['option_id' => $optionId]
            );
        }

        // 2. Call the eligibility service
        $result = $this->checkExecutionEligibility($eligibilityService, $id);
        $data = json_decode($result->getContent(), true);

        return redirect()->route('reservations')->with('success', 'تم تقييم الأهلية بنجاح. الحالة الحالية: '.$data['status']);
    }

    /**
     * Show the combined eligibility check form for all analyses in a reservation.
     */
    public function showFullEligibilityCheck($id)
    {
        $reservation = Reservation::with(['patient', 'reservationAnalyses.analyse.questions.options'])->findOrFail($id);

        return view('Adminstration.eligibility-check', [
            'reservation' => $reservation,
            'patient' => $reservation->patient,
        ]);
    }

    /**
     * Process the eligibility check answers for all analyses in a reservation.
     */
    public function submitFullEligibilityCheck(Request $request, AnalysisEligibilityService $eligibilityService, $id)
    {
        $reservation = Reservation::with('reservationAnalyses')->findOrFail($id);

        $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'exists:options,id',
        ]);

        // 1. Save answers for the patient
        foreach ($request->answers as $questionId => $optionId) {
            PatientAnswer::updateOrCreate(
                [
                    'patient_id' => $reservation->patient_id,
                    'question_id' => $questionId,
                ],
                [
                    'option_id' => $optionId,
                ]
            );
        }

        // 2. Run eligibility check for each analysis in the reservation
        $statusMap = [
            'block' => 'blocked',
            'warning' => 'warning',
            'approval' => 'pending_approval',
            'eligible' => 'ready',
        ];

        $results = [];
        foreach ($reservation->reservationAnalyses as $resAnalysis) {
            $checkResult = $eligibilityService->checkEligibility($reservation->patient_id, $resAnalysis->analysis_id);
            $newStatus = $statusMap[$checkResult['status']] ?? 'ready';
            $resAnalysis->update(['status' => $newStatus]);
            
            $results[] = [
                'analysis_id' => $resAnalysis->id,
                'name' => $resAnalysis->analyse->name,
                'status' => $newStatus,
                'reason' => $checkResult['reason'] ?? null,
                'action' => $checkResult['status']
            ];
        }

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة أهلية جميع التحاليل بنجاح.',
                'results' => $results
            ]);
        }

        return redirect()->route('reservations')->with('success', 'تم تحديث حالة أهلية جميع التحاليل بنجاح.');
    }

    /**
     * Update the status of a specific reservation analysis.
     */
    public function updateAnalysisStatus(Request $request, $id)
    {
        $resAnalysis = ReservationAnalysis::findOrFail($id);

        $request->validate([
            'status' => 'required|string|in:booked,ready,blocked,warning,pending_approval,completed',
        ]);

        $resAnalysis->update(['status' => $request->status]);

        // If it's AJAX, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $resAnalysis->status,
                'message' => 'تم تحديث حالة التحليل بنجاح.'
            ]);
        }

        return back()->with('success', 'تم تحديث حالة التحليل بنجاح.');
    }
}

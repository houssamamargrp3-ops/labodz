<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Patient;
use App\Models\History;
use Illuminate\Support\Facades\Mail;


class messagesController extends Controller
{
    public function messages()
    {
        $messages = Message::orderBy('created_at', 'desc')->paginate(10);
        
        // Fetch patients with their reservations and related analyses
        $patients = Patient::with(['reservations' => function($query) {
            $query->where('status', '!=', 'completed')->with('reservationAnalyses.analyse');
        }])->get();

        return view('Adminstration.messages', compact('messages', 'patients'));
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:10240',
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        // Send email to patient
        try {
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('email-attachments', 'public');
            }

            Mail::send('emails.patient-message', [
                'patient' => $patient,
                'subject' => $validated['subject'],
                'content' => $validated['message']
            ], function ($message) use ($patient, $validated, $attachmentPath) {
                $message->to($patient->email)
                        ->subject($validated['subject']);

                if ($attachmentPath) {
                    $message->attach(storage_path('app/public/' . $attachmentPath));
                }
            });

            return redirect()->route('messages')->with('success', 'تم إرسال الرسالة بنجاح إلى ' . $patient->name);

        } catch (\Exception $e) {
            return redirect()->route('messages')->with('error', 'فشل في إرسال الرسالة: ' . $e->getMessage());
        }
    }

    public function sendResult(Request $request)
    {
        $validated = $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'additional_notes' => 'nullable|string',
            'result_file' => 'nullable|file|mimes:pdf,jpg,png|max:10240',
        ]);

        $reservation = \App\Models\Reservation::with(['patient', 'reservationAnalyses.analyse'])->findOrFail($validated['reservation_id']);

        try {
            $attachmentPath = null;
            if ($request->hasFile('result_file')) {
                $attachmentPath = $request->file('result_file')->store('results', 'public');
            }

            // Update reservation with result details and mark as completed
            $reservation->update([
                'result_notes' => $validated['additional_notes'],
                'result_file_path' => $attachmentPath,
                'status' => 'completed'
            ]);

            // Also mark all individual analyses in this reservation as completed
            $reservation->reservationAnalyses()->update(['status' => 'completed']);

            // Send grouped result email
            Mail::send('emails.test-result', [
                'patient' => $reservation->patient,
                'reservation' => $reservation,
                'additional_notes' => $validated['additional_notes']
            ], function ($message) use ($reservation, $attachmentPath) {
                $testNames = $reservation->reservationAnalyses->map(fn($ra) => $ra->analyse->name)->implode(' و ');
                
                $message->to($reservation->patient->email)
                        ->subject('نتائج التحاليل الطبية: ' . $testNames);

                if ($attachmentPath) {
                    $message->attach(storage_path('app/public/' . $attachmentPath));
                }
            });

            return redirect()->route('messages')->with('success', 'تم إرسال نتائج التحاليل بنجاح إلى ' . $reservation->patient->name);

        } catch (\Exception $e) {
            return redirect()->route('messages')->with('error', 'فشل في إرسال النتائج: ' . $e->getMessage());
        }
    }

    public function deleteMessage($id)
    {
        $message = Message::findOrFail($id);
        $message->delete();

        return redirect()->route('messages')->with('success', 'تم حذف الرسالة بنجاح');
    }

    public function markAsRead($id)
    {
        $message = Message::findOrFail($id);
        $message->update(['is_read' => true]);

        return redirect()->route('messages')->with('success', 'تم تعيين الرسالة كمقروءة بنجاح');
    }
}

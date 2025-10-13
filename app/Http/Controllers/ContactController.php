<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ContactFormSubmitted;

class ContactController extends Controller
{
    /**
     * Handle contact form submission
     */
    public function submitContactForm(Request $request)
    {
        // Validate the form data
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        try {
            // Get form data
            $formData = [
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'subject' => $request->subject,
                'message' => $request->message,
                'submitted_at' => now()->format('F j, Y \a\t g:i A'),
            ];

            // Send email to admin
            $adminEmail = config('mail.admin_email', config('mail.from.address'));
            Mail::to($adminEmail)->send(new ContactFormSubmitted($formData));

            // Log the contact form submission
            Log::info('Contact form submitted successfully', [
                'name' => $formData['full_name'],
                'email' => $formData['email'],
                'subject' => $formData['subject'],
                'submitted_at' => $formData['submitted_at']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your message has been sent successfully! We will get back to you soon.',
            ]);

        } catch (\Exception $e) {
            Log::error('Contact form submission failed: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'form_data' => $request->only(['full_name', 'email', 'subject'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sorry, there was an error sending your message. Please try again later.',
                'error' => 'Email delivery failed'
            ], 500);
        }
    }

    /**
     * Get contact information for display
     */
    public function getContactInfo()
    {
        return response()->json([
            'contact_info' => [
                'email' => config('mail.from.address'),
                'phone' => '+234 800 SOLAR-GO',
                'address' => 'Lagos, Nigeria',
                'business_hours' => [
                    'monday_friday' => '9:00 AM - 6:00 PM',
                    'saturday' => '10:00 AM - 4:00 PM',
                    'sunday' => 'Closed'
                ],
                'response_time' => 'We typically respond within 24 hours'
            ]
        ]);
    }
}

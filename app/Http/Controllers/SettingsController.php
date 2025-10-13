<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Setting;

class SettingsController extends Controller
{
    /**
     * Save settings information.
     */
    public function saveSettings(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'store_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:20',
            'payment_settings' => 'required|array',
            'shipping_settings' => 'required|array',
            'notification_preferences' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Save the settings to the database
        Setting::create([
            'store_name' => $request->store_name,
            'contact_email' => $request->contact_email,
            'phone_number' => $request->phone_number,
            'payment_settings' => $request->payment_settings,
            'shipping_settings' => $request->shipping_settings,
            'notification_preferences' => $request->notification_preferences,
        ]);

        return response()->json(['message' => 'Settings saved successfully'], 200);
    }

    /**
     * Get the latest saved settings.
     */
    public function getLatestSettings()
    {
        $settings = Setting::latest()->first();

        if (!$settings) {
            return response()->json(['message' => 'No settings found'], 404);
        }

        return response()->json($settings, 200);
    }
}

<?php

// In app/Http/Controllers/AuthController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailValidationService;
use App\Services\AdminNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Registered; // <-- Import the Registered event
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Access\AuthorizationException;

class AuthController extends Controller
{
    protected $emailValidator;
    protected $notificationService;

    public function __construct(EmailValidationService $emailValidator, AdminNotificationService $notificationService)
    {
        $this->emailValidator = $emailValidator;
        $this->notificationService = $notificationService;
    }

    public function register(Request $request)
    {
        // 1. Basic Validation - Accept both formats
        $request->validate([
            'first_name' => 'required_without:name|string|max:255',
            'last_name' => 'required_without:name|string|max:255',
            'name' => 'required_without_all:first_name,last_name|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Combine first_name and last_name if provided, otherwise use name
        $fullName = $request->has('first_name') 
            ? trim($request->first_name . ' ' . $request->last_name)
            : $request->name;

        // 2. Advanced Email Validation - Check if email actually exists
        // Skip validation in local development for easier testing
        if (config('app.env') !== 'local') {
            $emailValidation = $this->emailValidator->validateEmailExists($request->email);
            
            if (!$emailValidation['valid']) {
                \Log::warning('Registration blocked - Invalid email detected', [
                    'email' => $request->email,
                    'validation_code' => $emailValidation['code'],
                    'validation_message' => $emailValidation['message'],
                    'user_ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                return response()->json([
                    'message' => $emailValidation['message'],
                    'error' => 'email_validation_failed',
                    'details' => 'Please ensure you enter a valid, existing email address that can receive emails.'
                ], 422);
            }

            // 3. Check for disposable/temporary email domains
            $domainValidation = $this->emailValidator->validateEmailDomain($request->email);
            
            if (!$domainValidation['valid']) {
                \Log::warning('Registration blocked - Disposable email detected', [
                    'email' => $request->email,
                    'validation_code' => $domainValidation['code'],
                    'validation_message' => $domainValidation['message'],
                    'user_ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                return response()->json([
                    'message' => $domainValidation['message'],
                    'error' => 'disposable_email_not_allowed',
                    'details' => 'Please use a permanent email address for registration.'
                ], 422);
            }
        }

        try {
            // Start database transaction
            DB::beginTransaction();

            // 4. Create User (only after email validation passes)
            $user = User::create([
                'name' => $fullName,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Commit the user creation first - this ensures the user is saved even if email fails
            DB::commit();

            // 5. Create admin notification for new user registration
            $this->notificationService->notifyUserRegistration($user);

            // 6. Try to send verification email in a separate try-catch
            // This way, if email fails, the user is still created successfully
            try {
                event(new Registered($user));
                
                \Log::info('User registered successfully with email sent', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'environment' => config('app.env'),
                    'user_ip' => $request->ip(),
                    'email_sent' => true
                ]);

                return response()->json([
                    'message' => 'User registered successfully. Please check your email for the verification link.',
                    'user' => $user->only(['name', 'email', 'role']),
                    'email_sent' => true
                ], 201);

            } catch (\Exception $emailError) {
                // Log email failure but don't fail the registration
                \Log::warning('User registered but email verification failed to send', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'email_error' => $emailError->getMessage(),
                    'user_ip' => $request->ip(),
                    'email_sent' => false
                ]);

                return response()->json([
                    'message' => 'Registration successful! However, we couldn\'t send the verification email right now. You can request a new verification email later.',
                    'user' => $user->only(['name', 'email', 'role']),
                    'email_sent' => false,
                    'note' => 'Please try logging in. If you need email verification, contact support.'
                ], 201);
            }

        } catch (\Exception $e) {
            // If user creation fails, rollback the transaction
            DB::rollBack();
            
            \Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->email,
                'name' => $request->name,
                'user_ip' => $request->ip()
            ]);

            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'error' => 'registration_failed'
            ], 500);
        }
    }

    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $id, (string) $user->getKey())) {
            throw new AuthorizationException;
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException;
        }

        if ($user->hasVerifiedEmail()) {
            return view('email-verified', ['message' => 'Email already verified.']);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        // Create admin notification for email verification
        $this->notificationService->notifyUserEmailVerified($user);

        return view('email-verified', ['message' => 'Email verified successfully.']);
    }

    public function login(Request $request)
    {
        // 1. Validation
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // 2. Attempt to authenticate the user
        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // 3. Get the authenticated user
        $user = auth()->user();

        // 4. Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
            ], 403);
        }

        // 5. Create a token
        $token = $user->createToken('API Token')->plainTextToken;

        // 6. Return the token and user info
        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->only(['name', 'email', 'role']),
            'token' => $token,
        ], 200);
    }

    /**
     * Validate email address in real-time (for frontend use)
     */
    public function validateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $emailValidation = $this->emailValidator->validateEmailExists($request->email);
        $domainValidation = $this->emailValidator->validateEmailDomain($request->email);

        // Check if email is already registered
        $emailExists = User::where('email', $request->email)->exists();
        if ($emailExists) {
            return response()->json([
                'valid' => false,
                'message' => 'This email address is already registered',
                'code' => 'EMAIL_ALREADY_EXISTS'
            ]);
        }

        // Return combined validation result
        if (!$emailValidation['valid']) {
            return response()->json($emailValidation);
        }

        if (!$domainValidation['valid']) {
            return response()->json($domainValidation);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Email address is valid and available for registration',
            'code' => 'EMAIL_AVAILABLE'
        ]);
    }

    // You will add logout methods here later...
}

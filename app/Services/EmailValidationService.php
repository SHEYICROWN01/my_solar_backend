<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Swift_TransportException;

class EmailValidationService
{
    /**
     * Check if an email address actually exists by testing domain and MX records only
     * 
     * @param string $email
     * @return array ['valid' => bool, 'message' => string, 'code' => string|null]
     */
    public function validateEmailExists(string $email): array
    {
        // First, do basic email format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'message' => 'Invalid email format',
                'code' => 'INVALID_FORMAT'
            ];
        }

        // Check for obviously fake domains
        $domain = substr(strrchr($email, "@"), 1);
        $fakeDomains = [
            'example.com',
            'test.com',
            'fake.com',
            'dummy.com',
            'invalid.com',
            'notreal.com',
            'fake.email',
            'test.test'
        ];

        if (in_array(strtolower($domain), $fakeDomains)) {
            return [
                'valid' => false,
                'message' => 'Please use a real email address',
                'code' => 'FAKE_DOMAIN'
            ];
        }

        // Check if domain has MX record
        if (!$this->checkMXRecord($domain)) {
            return [
                'valid' => false,
                'message' => 'Email domain does not exist or cannot receive emails',
                'code' => 'NO_MX_RECORD'
            ];
        }

        // For now, just return valid if domain checks pass
        // We removed the actual email sending test to avoid sending unwanted emails
        return [
            'valid' => true,
            'message' => 'Email address appears valid',
            'code' => 'VALID'
        ];
    }

    /**
     * Check if domain has MX record (can receive emails)
     */
    private function checkMXRecord(string $domain): bool
    {
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }

    /**
     * Test email delivery by sending a validation test email
     */
    private function testEmailDelivery(string $email): array
    {
        try {
            // Create a test mail instance that we can catch responses from
            $testResult = $this->sendValidationTestEmail($email);
            
            return $testResult;
            
        } catch (\Swift_TransportException $e) {
            return $this->parseSmtpError($e->getMessage(), $email);
        } catch (\Exception $e) {
            Log::error('Email validation error', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // If there's an unexpected error, assume email is valid to avoid blocking users
            return [
                'valid' => true,
                'message' => 'Email validation completed',
                'code' => 'VALIDATION_ERROR'
            ];
        }
    }

    /**
     * Send a test email to validate the address exists
     */
    private function sendValidationTestEmail(string $email): array
    {
        try {
            // Use Laravel's Mail facade to test the connection
            Mail::raw('Email validation test - please ignore this message.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Email Validation Test - Gifamz Store')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            // If we reach here without exception, email was accepted by the server
            return [
                'valid' => true,
                'message' => 'Email address is valid and can receive emails',
                'code' => 'VALID'
            ];

        } catch (\Swift_TransportException $e) {
            return $this->parseSmtpError($e->getMessage(), $email);
        } catch (\Exception $e) {
            // Check if the error message contains SMTP response codes
            $errorMessage = $e->getMessage();
            
            if (str_contains($errorMessage, '550') || str_contains($errorMessage, '551')) {
                return [
                    'valid' => false,
                    'message' => 'This email address does not exist or cannot receive emails',
                    'code' => 'EMAIL_NOT_EXISTS'
                ];
            }
            
            if (str_contains($errorMessage, '552') || str_contains($errorMessage, '553')) {
                return [
                    'valid' => false,
                    'message' => 'Email address exists but mailbox is full or blocked',
                    'code' => 'MAILBOX_FULL'
                ];
            }

            // For other errors, assume valid to avoid false negatives
            return [
                'valid' => true,
                'message' => 'Email validation completed with warnings',
                'code' => 'VALIDATION_WARNING'
            ];
        }
    }

    /**
     * Parse SMTP error messages to determine email validity
     */
    private function parseSmtpError(string $errorMessage, string $email): array
    {
        $errorMessage = strtolower($errorMessage);
        
        // 550 - User unknown / Mailbox not found
        if (str_contains($errorMessage, '550')) {
            if (str_contains($errorMessage, 'user unknown') || 
                str_contains($errorMessage, 'no such user') ||
                str_contains($errorMessage, 'user not found') ||
                str_contains($errorMessage, 'mailbox not found') ||
                str_contains($errorMessage, 'recipient not found')) {
                return [
                    'valid' => false,
                    'message' => 'This email address does not exist',
                    'code' => 'USER_NOT_FOUND'
                ];
            }
        }

        // 551 - User not local / Invalid recipient
        if (str_contains($errorMessage, '551')) {
            return [
                'valid' => false,
                'message' => 'Invalid email address or domain',
                'code' => 'INVALID_RECIPIENT'
            ];
        }

        // 552 - Mailbox full
        if (str_contains($errorMessage, '552')) {
            return [
                'valid' => false,
                'message' => 'Email address exists but mailbox is full',
                'code' => 'MAILBOX_FULL'
            ];
        }

        // 553 - Mailbox name not allowed
        if (str_contains($errorMessage, '553')) {
            return [
                'valid' => false,
                'message' => 'Email address format is not allowed',
                'code' => 'MAILBOX_NOT_ALLOWED'
            ];
        }

        // 554 - Transaction failed / Rejected
        if (str_contains($errorMessage, '554')) {
            return [
                'valid' => false,
                'message' => 'Email address rejected by mail server',
                'code' => 'TRANSACTION_FAILED'
            ];
        }

        // For other SMTP errors, log and assume valid to avoid false negatives
        Log::info('Unknown SMTP error during email validation', [
            'email' => $email,
            'error' => $errorMessage
        ]);

        return [
            'valid' => true,
            'message' => 'Email validation completed with unknown status',
            'code' => 'UNKNOWN_SMTP_ERROR'
        ];
    }

    /**
     * Quick domain validation for common checks
     */
    public function validateEmailDomain(string $email): array
    {
        $domain = substr(strrchr($email, "@"), 1);
        
        // Check for temporary/disposable email domains
        $disposableDomains = [
            '10minutemail.com',
            'tempmail.org',
            'guerrillamail.com',
            'mailinator.com',
            'throwaway.email',
            'temp-mail.org'
        ];

        if (in_array(strtolower($domain), $disposableDomains)) {
            return [
                'valid' => false,
                'message' => 'Temporary email addresses are not allowed',
                'code' => 'DISPOSABLE_EMAIL'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Domain validation passed',
            'code' => 'DOMAIN_VALID'
        ];
    }
}
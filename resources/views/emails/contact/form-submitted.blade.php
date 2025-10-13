<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission - {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); padding: 40px 20px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" style="width: 100%; max-width: 650px; border-collapse: collapse; background: #ffffff; border-radius: 24px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 50%, #1e40af 100%); padding: 40px 30px; text-align: center; position: relative;">
                            <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; filter: blur(40px);"></div>
                            <div style="position: absolute; bottom: -30px; left: -30px; width: 120px; height: 120px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; filter: blur(40px);"></div>
                            
                            <!-- Message Icon -->
                            <div style="margin-bottom: 20px; position: relative; z-index: 1;">
                                <div style="display: inline-block; width: 80px; height: 80px; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); border-radius: 50%; padding: 15px; box-shadow: 0 8px 32px rgba(255, 255, 255, 0.3);">
                                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block; margin: auto;">
                                        <path d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                            
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: relative; z-index: 1;">
                                New Contact Form Submission
                            </h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255, 255, 255, 0.95); font-size: 16px; position: relative; z-index: 1;">
                                Someone reached out through your website
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Customer Information -->
                    <tr>
                        <td style="padding: 40px 40px 30px 40px;">
                            <h2 style="margin: 0 0 20px 0; color: #1f2937; font-size: 20px; font-weight: 700;">
                                Customer Information
                            </h2>
                            
                            <div style="background: #f9fafb; border-radius: 12px; padding: 25px; border: 1px solid #e5e7eb;">
                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                                            <span style="color: #6b7280; font-size: 14px; font-weight: 600;">Full Name</span>
                                        </td>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">
                                            <strong style="color: #1f2937; font-size: 15px;">{{ $formData['full_name'] }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                                            <span style="color: #6b7280; font-size: 14px; font-weight: 600;">Email Address</span>
                                        </td>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">
                                            <a href="mailto:{{ $formData['email'] }}" style="color: #3b82f6; font-size: 15px; text-decoration: none; font-weight: 500;">{{ $formData['email'] }}</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                                            <span style="color: #6b7280; font-size: 14px; font-weight: 600;">Phone Number</span>
                                        </td>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">
                                            <a href="tel:{{ $formData['phone_number'] }}" style="color: #3b82f6; font-size: 15px; text-decoration: none; font-weight: 500;">{{ $formData['phone_number'] }}</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb;">
                                            <span style="color: #6b7280; font-size: 14px; font-weight: 600;">Subject</span>
                                        </td>
                                        <td style="padding: 12px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">
                                            <strong style="color: #1f2937; font-size: 15px;">{{ $formData['subject'] }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 0;">
                                            <span style="color: #6b7280; font-size: 14px; font-weight: 600;">Submitted</span>
                                        </td>
                                        <td style="padding: 12px 0; text-align: right;">
                                            <span style="color: #1f2937; font-size: 15px;">{{ $formData['submitted_at'] }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- Message Content -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px;">
                            <h3 style="margin: 0 0 15px 0; color: #1f2937; font-size: 18px; font-weight: 700;">
                                Message
                            </h3>
                            
                            <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 12px; padding: 25px; border-left: 4px solid #3b82f6;">
                                <p style="margin: 0; color: #1e40af; font-size: 15px; line-height: 1.7; white-space: pre-wrap;">{{ $formData['message'] }}</p>
                            </div>
                        </td>
                    </tr>

                    <!-- Action Buttons -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px; text-align: center;">
                            <div style="display: inline-block; margin: 0 10px;">
                                <a href="mailto:{{ $formData['email'] }}?subject=Re: {{ $formData['subject'] }}" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: #ffffff; text-decoration: none; padding: 14px 30px; border-radius: 12px; font-size: 15px; font-weight: 600; box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);">
                                    📧 Reply via Email
                                </a>
                            </div>
                            <div style="display: inline-block; margin: 0 10px;">
                                <a href="tel:{{ $formData['phone_number'] }}" style="display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; padding: 14px 30px; border-radius: 12px; font-size: 15px; font-weight: 600; box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);">
                                    📞 Call Customer
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Admin Note -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px;">
                            <div style="background: #fef3c7; border-radius: 12px; padding: 20px; border-left: 4px solid #f59e0b;">
                                <p style="margin: 0; color: #92400e; font-size: 14px; line-height: 1.6;">
                                    <strong>📋 Admin Note:</strong> This email was sent automatically when someone submitted the contact form on your website. You can reply directly to this email to respond to the customer.
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1f2937 0%, #111827 100%); padding: 30px; text-align: center;">
                            <p style="margin: 0 0 10px 0; color: #d1d5db; font-size: 14px;">
                                <strong style="color: #ffffff;">{{ config('app.name') }}</strong><br>
                                Website Contact Form Notification
                            </p>
                            
                            <p style="margin: 0; color: #9ca3af; font-size: 12px;">
                                This email was generated automatically from your website's contact form.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div style="height: 40px;"></div>
            </td>
        </tr>
    </table>
</body>
</html>
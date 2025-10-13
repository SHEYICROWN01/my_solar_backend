<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Email Verification - Gifamz Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 40px 20px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }
        .logo-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            font-size: 40px;
            position: relative;
            z-index: 1;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }
        .header p {
            font-size: 16px;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }
        .content {
            padding: 40px 30px;
        }
        .welcome-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid #86efac;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
            text-align: center;
        }
        .welcome-card h2 {
            font-size: 24px;
            color: #166534;
            margin-bottom: 12px;
            font-weight: 700;
        }
        .welcome-card p {
            color: #15803d;
            font-size: 15px;
            line-height: 1.6;
        }
        .greeting {
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 20px;
        }
        .main-text {
            font-size: 15px;
            color: #475569;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
            padding: 30px 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            border: 2px solid #e2e8f0;
        }
        .verify-button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
            letter-spacing: 0.3px;
        }
        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        .timer-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: #dc2626;
            margin-top: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .security-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }
        .security-box h3 {
            font-size: 16px;
            color: #92400e;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
        }
        .security-box ul {
            margin: 0;
            padding-left: 20px;
            color: #78350f;
        }
        .security-box li {
            margin: 8px 0;
            font-size: 14px;
            line-height: 1.6;
        }
        .manual-link-section {
            margin: 24px 0;
        }
        .manual-link-section p {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .manual-link {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            word-break: break-all;
            font-size: 12px;
            color: #475569;
            font-family: 'Courier New', monospace;
            line-height: 1.6;
        }
        .benefits-section {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .benefits-section h3 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 16px;
            text-align: center;
            font-weight: 700;
        }
        .benefits-grid {
            display: grid;
            gap: 12px;
        }
        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .benefit-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .benefit-text {
            flex: 1;
            font-size: 14px;
            color: #1e40af;
            font-weight: 500;
            padding-top: 6px;
        }
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 30px 0;
        }
        .footer {
            background: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 2px solid #e2e8f0;
        }
        .footer p {
            color: #64748b;
            font-size: 14px;
            margin: 8px 0;
        }
        .footer-brand {
            font-weight: 700;
            color: #0f172a;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .footer-links {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
        .footer-links a {
            color: #6366f1;
            text-decoration: none;
            margin: 0 12px;
            font-size: 13px;
            font-weight: 500;
        }
        .copyright {
            margin-top: 16px;
            font-size: 12px;
            color: #94a3b8;
        }
        @media only screen and (max-width: 600px) {
            body {
                padding: 20px 10px;
            }
            .content {
                padding: 24px 20px;
            }
            .header {
                padding: 30px 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .logo-icon {
                width: 70px;
                height: 70px;
                font-size: 35px;
            }
            .welcome-card h2 {
                font-size: 20px;
            }
            .verify-button {
                padding: 14px 32px;
                font-size: 15px;
            }
            .cta-section {
                padding: 24px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo-icon">🛍️</div>
            <h1>Welcome to Gifamz Store!</h1>
            <p>Let's verify your email and get started</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h2>🎉 Registration Successful!</h2>
                <p>Thank you for creating an account with Gifamz Store. You're just one step away from starting your shopping journey!</p>
            </div>

            <p class="greeting">Hi there! 👋</p>
            
            <p class="main-text">
                We're thrilled to have you join the Gifamz Store community! To complete your registration and unlock all features, please verify your email address by clicking the button below.
            </p>

            <!-- CTA Section -->
            <div class="cta-section">
                <a href="{{ $actionUrl }}" class="verify-button">
                    ✅ Verify My Email Address
                </a>
                <div class="timer-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Expires in 60 minutes
                </div>
            </div>

            <!-- Security Notice -->
            <div class="security-box">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Security Notice
                </h3>
                <ul>
                    <li>This email was sent because someone registered an account with this email address</li>
                    <li>If you didn't create an account, please ignore this email</li>
                    <li>For security, this link will only work once and expires in 60 minutes</li>
                </ul>
            </div>

            <!-- Manual Link -->
            <div class="manual-link-section">
                <p>Having trouble with the button? Copy and paste this link into your browser:</p>
                <div class="manual-link">{{ $actionUrl }}</div>
            </div>

            <div class="divider"></div>

            <!-- Benefits Section -->
            <div class="benefits-section">
                <h3>🌟 What You'll Get After Verification</h3>
                <div class="benefits-grid">
                    <div class="benefit-item">
                        <div class="benefit-icon">✨</div>
                        <div class="benefit-text">Browse our exclusive product collections</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">🛒</div>
                        <div class="benefit-text">Place orders and track deliveries in real-time</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">💎</div>
                        <div class="benefit-text">Access special pre-order opportunities</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">🎁</div>
                        <div class="benefit-text">Receive exclusive offers and promotions</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">📱</div>
                        <div class="benefit-text">Manage your account and preferences</div>
                    </div>
                </div>
            </div>

            <!-- Support Message -->
            <div style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 8px; padding: 20px; text-align: center; margin-top: 24px;">
                <p style="color: #374151; font-size: 14px; margin: 0;">
                    <strong>Need help?</strong> Our support team is ready to assist you.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-brand">🛍️ Gifamz Store</p>
            <p>Thank you for choosing us! We're excited to serve you.</p>
            
            <div class="footer-links">
                <a href="#">Help Center</a>
                <a href="#">Contact Support</a>
                <a href="#">Privacy Policy</a>
            </div>
            
            <p class="copyright">
                © {{ date('Y') }} Gifamz Store. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
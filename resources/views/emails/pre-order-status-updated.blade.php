<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Pre-Order Status Update</title>
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
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header p {
            font-size: 16px;
            opacity: 0.95;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .success-icon svg {
            width: 40px;
            height: 40px;
        }
        .content {
            padding: 40px 30px;
        }
        .status-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid #86efac;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            text-align: center;
        }
        .status-card h2 {
            font-size: 22px;
            color: #166534;
            margin-bottom: 16px;
            font-weight: 700;
        }
        .order-number {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            letter-spacing: 0.5px;
        }
        .status-badges {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .badge-success {
            background: #22c55e;
            color: white;
        }
        .badge-info {
            background: #3b82f6;
            color: white;
        }
        .badge-warning {
            background: #f59e0b;
            color: white;
        }
        .alert-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .alert-box h3 {
            font-size: 20px;
            color: #92400e;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-box p {
            color: #78350f;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-left: 4px solid #10b981;
        }
        .alert-success h3 {
            color: #065f46;
        }
        .alert-success p {
            color: #064e3b;
        }
        .amount-highlight {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .amount-highlight .label {
            font-weight: 600;
            color: #64748b;
            font-size: 15px;
        }
        .amount-highlight .value {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
        }
        .steps-list {
            background: white;
            border-radius: 8px;
            padding: 16px 20px;
            margin-top: 16px;
        }
        .steps-list p {
            margin: 8px 0;
            padding-left: 24px;
            position: relative;
            color: #475569;
        }
        .steps-list p:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #3b82f6;
            font-weight: 700;
        }
        .order-details {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .order-details h2 {
            font-size: 20px;
            color: #0f172a;
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid #e2e8f0;
            align-items: flex-start;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
            flex-shrink: 0;
            margin-right: 16px;
        }
        .detail-value {
            text-align: right;
            color: #1e293b;
            font-size: 14px;
            font-weight: 500;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            transition: transform 0.2s;
            margin-top: 16px;
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
        .footer-links {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
        .footer-links a {
            color: #3b82f6;
            text-decoration: none;
            margin: 0 12px;
            font-size: 13px;
        }
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 24px 0;
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
            .status-badges {
                flex-direction: column;
                align-items: stretch;
            }
            .detail-row {
                flex-direction: column;
                gap: 4px;
            }
            .detail-value {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#3b82f6" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1>Pre-Order Status Update</h1>
            <p>Your order status has been updated</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Status Card -->
            <div class="status-card">
                <h2>✨ Status Update</h2>
                <div class="order-number">Order #{{ $customerPreOrder->pre_order_number }}</div>
                <div class="status-badges">
                    <span class="badge badge-success">
                        <span>📦</span>
                        {{ ucfirst(str_replace('_', ' ', $customerPreOrder->status)) }}
                    </span>
                    <span class="badge badge-info">
                        <span>💳</span>
                        {{ ucfirst(str_replace('_', ' ', $customerPreOrder->payment_status)) }}
                    </span>
                </div>
            </div>

            <!-- Payment Alert for Ready for Pickup -->
            @if($customerPreOrder->status === 'ready_for_pickup' && $customerPreOrder->payment_status === 'deposit_paid')
            <div class="alert-box">
                <h3>🎉 Great News! Your Order is Ready</h3>
                <p>Your pre-order is now ready for {{ $customerPreOrder->fulfillment_method === 'delivery' ? 'delivery' : 'pickup' }}. To complete your order, please pay the remaining balance:</p>
                
                <div class="amount-highlight">
                    <span class="label">Remaining Amount:</span>
                    <span class="value">₦{{ number_format($customerPreOrder->remaining_amount, 2) }}</span>
                </div>

                <div class="steps-list">
                    <strong style="display: block; margin-bottom: 12px; color: #0f172a;">Next Steps:</strong>
                    <p>Pay the remaining balance to confirm your order</p>
                    <p>We'll contact you for {{ $customerPreOrder->fulfillment_method === 'delivery' ? 'delivery' : 'pickup' }} arrangements</p>
                    <p>Have your order number ready for reference</p>
                </div>
            </div>
            @endif

            <!-- Payment Complete Alert -->
            @if($customerPreOrder->payment_status === 'fully_paid')
            <div class="alert-box alert-success">
                <h3>✅ Payment Complete!</h3>
                <p>Thank you! Your payment has been received and your order is fully paid.</p>
                <p style="margin-bottom: 0;"><strong>We'll contact you soon to arrange {{ $customerPreOrder->fulfillment_method === 'delivery' ? 'delivery' : 'pickup' }}.</strong></p>
            </div>
            @endif

            <div class="divider"></div>

            <!-- Order Details -->
            <div class="order-details">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Order Summary
                </h2>
                
                <div class="detail-row">
                    <span class="detail-label">Pre-Order Number</span>
                    <span class="detail-value">{{ $customerPreOrder->pre_order_number }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Product</span>
                    <span class="detail-value">{{ $customerPreOrder->preOrder->product_name }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Quantity</span>
                    <span class="detail-value">{{ $customerPreOrder->quantity }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Total Amount</span>
                    <span class="detail-value"><strong>₦{{ number_format($customerPreOrder->total_amount, 2) }}</strong></span>
                </div>
                
                @if($customerPreOrder->payment_status === 'deposit_paid')
                <div class="detail-row">
                    <span class="detail-label">Paid (Deposit)</span>
                    <span class="detail-value" style="color: #10b981;">₦{{ number_format($customerPreOrder->deposit_amount, 2) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Remaining Balance</span>
                    <span class="detail-value" style="color: #f59e0b; font-weight: 700;">₦{{ number_format($customerPreOrder->remaining_amount, 2) }}</span>
                </div>
                @endif
                
                <div class="detail-row">
                    <span class="detail-label">Fulfillment Method</span>
                    <span class="detail-value">{{ ucfirst($customerPreOrder->fulfillment_method) }}</span>
                </div>
                
                @if($customerPreOrder->fulfillment_method === 'delivery')
                <div class="detail-row">
                    <span class="detail-label">Delivery Address</span>
                    <span class="detail-value">{{ $customerPreOrder->shipping_address }}, {{ $customerPreOrder->city }}, {{ $customerPreOrder->state }}</span>
                </div>
                @else
                <div class="detail-row">
                    <span class="detail-label">Pickup Location</span>
                    <span class="detail-value">{{ $customerPreOrder->pickup_location }}</span>
                </div>
                @endif
            </div>

            <!-- Support Message -->
            <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 8px; padding: 20px; text-align: center; margin-top: 24px;">
                <p style="color: #1e40af; font-size: 14px; margin: 0;">
                    <strong>Need help?</strong> Our customer support team is here for you.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="font-weight: 600; color: #0f172a; margin-bottom: 4px;">Thank you for choosing us! 🙏</p>
            <p>We appreciate your business and look forward to serving you.</p>
            <p style="font-size: 12px; color: #94a3b8; margin-top: 16px;">
                Update sent: {{ now()->format('F j, Y \a\t g:i A') }}
            </p>
            
            <div class="footer-links">
                <a href="#">Track Order</a>
                <a href="#">Contact Support</a>
                <a href="#">FAQs</a>
            </div>
        </div>
    </div>
</body>
</html>
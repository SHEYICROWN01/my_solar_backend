<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Pre-Order Confirmation</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
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
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        .success-icon svg {
            width: 40px;
            height: 40px;
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
            line-height: 1.5;
        }
        .content {
            padding: 40px 30px;
        }
        .highlight-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid #86efac;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            text-align: center;
        }
        .order-number {
            font-size: 24px;
            font-weight: 700;
            color: #166534;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
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
        .badge-primary {
            background: #3b82f6;
            color: white;
        }
        .badge-success {
            background: #10b981;
            color: white;
        }
        .section-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .section-card h2 {
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
            gap: 16px;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
            flex-shrink: 0;
            min-width: 140px;
        }
        .detail-value {
            text-align: right;
            color: #1e293b;
            font-size: 14px;
            font-weight: 500;
            word-break: break-word;
        }
        .total-row {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            margin: 16px -24px -24px -24px;
            padding: 20px 24px;
            border-radius: 0 0 12px 12px;
        }
        .total-row .detail-row {
            border: none;
            padding: 0;
        }
        .total-row .detail-label {
            color: #065f46;
            font-size: 16px;
        }
        .total-row .detail-value {
            color: #065f46;
            font-size: 22px;
            font-weight: 700;
        }
        .amount-breakdown {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
        }
        .amount-row.paid {
            color: #10b981;
            font-weight: 600;
        }
        .amount-row.remaining {
            color: #f59e0b;
            font-weight: 600;
        }
        .info-grid {
            display: grid;
            gap: 16px;
        }
        .info-item {
            background: white;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            gap: 12px;
        }
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .info-icon.user {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }
        .info-icon.mail {
            background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
            color: #9f1239;
        }
        .info-icon.phone {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }
        .info-icon.location {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }
        .info-content {
            flex: 1;
        }
        .info-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-text {
            font-size: 14px;
            color: #0f172a;
            font-weight: 500;
        }
        .specs-list {
            background: white;
            border-radius: 8px;
            padding: 16px;
            color: #475569;
            font-size: 14px;
            line-height: 1.8;
        }
        .spec-item {
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .spec-item:last-child {
            border-bottom: none;
        }
        .notes-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            color: #78350f;
            font-size: 14px;
            line-height: 1.6;
        }
        .timeline-box {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 12px;
            padding: 24px;
            margin-top: 24px;
        }
        .timeline-box h3 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 16px;
            font-weight: 700;
            text-align: center;
        }
        .timeline-step {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            align-items: flex-start;
        }
        .timeline-step:last-child {
            margin-bottom: 0;
        }
        .timeline-icon {
            width: 32px;
            height: 32px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }
        .timeline-text {
            flex: 1;
            padding-top: 4px;
            color: #1e40af;
            font-size: 14px;
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
            font-weight: 500;
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
            .order-number {
                font-size: 20px;
            }
            .status-badges {
                flex-direction: column;
            }
            .detail-row {
                flex-direction: column;
                gap: 4px;
            }
            .detail-label {
                min-width: auto;
            }
            .detail-value {
                text-align: left;
            }
            .section-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#10b981" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1>🎉 Pre-Order Confirmed!</h1>
            <p>Thank you for your pre-order! We've received your order and will keep you updated every step of the way.</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Order Number Highlight -->
            <div class="highlight-card">
                <div style="font-size: 14px; color: #166534; font-weight: 600; margin-bottom: 8px;">ORDER NUMBER</div>
                <div class="order-number">{{ $customerPreOrder->pre_order_number }}</div>
                <div class="status-badges">
                    <span class="badge badge-primary">
                        <span>📦</span>
                        {{ ucfirst(str_replace('_', ' ', $customerPreOrder->status)) }}
                    </span>
                    <span class="badge badge-success">
                        <span>💳</span>
                        {{ ucfirst(str_replace('_', ' ', $customerPreOrder->payment_status)) }}
                    </span>
                </div>
            </div>

            <!-- Order Details -->
            <div class="section-card">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Order Details
                </h2>
                
                <div class="detail-row">
                    <span class="detail-label">Product</span>
                    <span class="detail-value"><strong>{{ $preOrder->product_name }}</strong></span>
                </div>
                
                @if($category ?? null)
                <div class="detail-row">
                    <span class="detail-label">Category</span>
                    <span class="detail-value">{{ $category->name }}</span>
                </div>
                @endif
                
                <div class="detail-row">
                    <span class="detail-label">Quantity</span>
                    <span class="detail-value">{{ $customerPreOrder->quantity }} unit(s)</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Unit Price</span>
                    <span class="detail-value">₦{{ number_format($customerPreOrder->unit_price, 2) }}</span>
                </div>

                <div class="amount-breakdown">
                    <div class="amount-row paid">
                        <span>Deposit Paid</span>
                        <span>₦{{ number_format($customerPreOrder->deposit_amount, 2) }}</span>
                    </div>
                    <div class="amount-row remaining">
                        <span>Remaining Balance</span>
                        <span>₦{{ number_format($customerPreOrder->remaining_amount, 2) }}</span>
                    </div>
                </div>

                <div class="total-row">
                    <div class="detail-row">
                        <span class="detail-label">Total Amount</span>
                        <span class="detail-value">₦{{ number_format($customerPreOrder->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="section-card">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Customer Information
                </h2>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-icon user">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Full Name</div>
                            <div class="info-text">{{ $customerPreOrder->full_name }}</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon mail">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Email Address</div>
                            <div class="info-text">{{ $customerPreOrder->customer_email }}</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon phone">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Phone Number</div>
                            <div class="info-text">{{ $customerPreOrder->customer_phone }}</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon location">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Fulfillment Method</div>
                            <div class="info-text">{{ ucfirst($customerPreOrder->fulfillment_method) }}</div>
                            @if($customerPreOrder->fulfillment_method === 'delivery')
                            <div class="info-text" style="margin-top: 4px; font-size: 13px; color: #64748b;">
                                {{ $customerPreOrder->shipping_address }}, {{ $customerPreOrder->city }}, {{ $customerPreOrder->state }}
                            </div>
                            @else
                            <div class="info-text" style="margin-top: 4px; font-size: 13px; color: #64748b;">
                                {{ $customerPreOrder->pickup_location }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Specifications -->
            @if($preOrder->specifications || $preOrder->power_output || $preOrder->warranty_period || $preOrder->expected_availability)
            <div class="section-card">
                <h2>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    Product Specifications
                </h2>
                
                <div class="specs-list">
                    @if($preOrder->specifications)
                    <div style="margin-bottom: 12px; line-height: 1.6;">{{ $preOrder->specifications }}</div>
                    @endif
                    
                    @if($preOrder->power_output)
                    <div class="spec-item">
                        <strong style="color: #0f172a;">Power Output:</strong> {{ $preOrder->power_output }}
                    </div>
                    @endif
                    
                    @if($preOrder->warranty_period)
                    <div class="spec-item">
                        <strong style="color: #0f172a;">Warranty:</strong> {{ $preOrder->warranty_period }}
                    </div>
                    @endif
                    
                    @if($preOrder->expected_availability)
                    <div class="spec-item">
                        <strong style="color: #0f172a;">Expected Availability:</strong> {{ $preOrder->expected_availability }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Additional Notes -->
            @if($customerPreOrder->notes)
            <div class="section-card" style="border-color: #fbbf24;">
                <h2 style="color: #92400e;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                    Additional Notes
                </h2>
                <div class="notes-box">
                    {{ $customerPreOrder->notes }}
                </div>
            </div>
            @endif

            <div class="divider"></div>

            <!-- What's Next Timeline -->
            <div class="timeline-box">
                <h3>📋 What Happens Next?</h3>
                
                <div class="timeline-step">
                    <div class="timeline-icon">1</div>
                    <div class="timeline-text">
                        <strong>Order Processing</strong><br>
                        We're preparing your order
                    </div>
                </div>
                
                <div class="timeline-step">
                    <div class="timeline-icon">2</div>
                    <div class="timeline-text">
                        <strong>Ready Notification</strong><br>
                        You'll receive an email when ready for {{ $customerPreOrder->fulfillment_method === 'delivery' ? 'delivery' : 'pickup' }}
                    </div>
                </div>
                
                <div class="timeline-step">
                    <div class="timeline-icon">3</div>
                    <div class="timeline-text">
                        <strong>Final Payment</strong><br>
                        Pay remaining balance to complete your order
                    </div>
                </div>
                
                <div class="timeline-step">
                    <div class="timeline-icon">4</div>
                    <div class="timeline-text">
                        <strong>{{ ucfirst($customerPreOrder->fulfillment_method) }}</strong><br>
                        Receive your product!
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="font-weight: 600; color: #0f172a; margin-bottom: 4px;">Thank you for choosing us! 🙏</p>
            <p>We're excited to fulfill your order and appreciate your business.</p>
            <p style="font-size: 12px; color: #94a3b8; margin-top: 16px;">
                Order Date: {{ $customerPreOrder->created_at->format('F j, Y \a\t g:i A') }}
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
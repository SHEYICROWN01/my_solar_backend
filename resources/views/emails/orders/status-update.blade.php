<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update - {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #fff5eb 0%, #fff9f0 50%, #fffbf5 100%);">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background: linear-gradient(135deg, #fff5eb 0%, #fff9f0 50%, #fffbf5 100%); padding: 40px 20px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" style="width: 100%; max-width: 650px; border-collapse: collapse; background: #ffffff; border-radius: 24px; box-shadow: 0 20px 60px rgba(251, 146, 60, 0.15); overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f97316 0%, #fb923c 50%, #fbbf24 100%); padding: 40px 30px; text-align: center; position: relative;">
                            <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; filter: blur(40px);"></div>
                            <div style="position: absolute; bottom: -30px; left: -30px; width: 120px; height: 120px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; filter: blur(40px);"></div>
                            
                            <!-- Status Icon -->
                            <div style="margin-bottom: 20px; position: relative; z-index: 1;">
                                <div style="display: inline-block; width: 80px; height: 80px; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); border-radius: 50%; padding: 15px; box-shadow: 0 8px 32px rgba(255, 255, 255, 0.3);">
                                    <!-- Dynamic icon based on status -->
                                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block; margin: auto;">
                                        <path d="M9 11l3 3L22 4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                            
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: relative; z-index: 1;">
                                Order Status Updated!
                            </h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255, 255, 255, 0.95); font-size: 16px; position: relative; z-index: 1;">
                                Your solar products are on their way
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Greeting -->
                    <tr>
                        <td style="padding: 40px 40px 20px 40px;">
                            <h2 style="margin: 0 0 15px 0; color: #1f2937; font-size: 24px; font-weight: 700;">
                                Hi {{ $order->first_name }},
                            </h2>
                            <p style="margin: 0; color: #4b5563; font-size: 16px; line-height: 1.6;">
                                Great news! Your order status has been updated.
                            </p>
                        </td>
                    </tr>

                    <!-- Status Change Banner -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <div style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border-radius: 16px; padding: 25px; border-left: 4px solid #f97316;">
                                <div style="text-align: center; margin-bottom: 15px;">
                                    <span style="display: inline-block; padding: 8px 16px; background: #fef3c7; border-radius: 20px; color: #78350f; font-size: 14px; font-weight: 600; margin-right: 10px;">
                                        {{ ucfirst($oldStatus) }}
                                    </span>
                                    <span style="color: #f97316; font-size: 20px; font-weight: bold;">→</span>
                                    <span style="display: inline-block; padding: 8px 16px; background: linear-gradient(135deg, #f97316 0%, #fbbf24 100%); border-radius: 20px; color: white; font-size: 14px; font-weight: 600; margin-left: 10px;">
                                        {{ ucfirst($newStatus) }}
                                    </span>
                                </div>
                                <p style="margin: 15px 0 0 0; color: #92400e; font-size: 15px; line-height: 1.6; text-align: center;">
                                    <!-- Dynamic message based on status -->
                                    <strong>
                                        @if($newStatus === 'paid')
                                            Great news! Your payment has been confirmed and your order is now being prepared.
                                        @elseif($newStatus === 'processing')
                                            We are now preparing your order for shipment. We will notify you again once it has been shipped.
                                        @elseif($newStatus === 'shipped')
                                            Your order is on its way! You can expect delivery soon.
                                        @elseif($newStatus === 'delivered')
                                            Your order has been delivered. We hope you enjoy your products!
                                        @elseif($newStatus === 'cancelled')
                                            Your order has been cancelled. If you have any questions, please contact our support team.
                                        @endif
                                    </strong>
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Order Details -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <h3 style="margin: 0 0 20px 0; color: #ea580c; font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="3" width="18" height="18" rx="2" stroke="#ea580c" stroke-width="2"/>
                                    <path d="M9 3v18M15 3v18M3 9h18M3 15h18" stroke="#ea580c" stroke-width="2"/>
                                </svg>
                                Order Details
                            </h3>
                            
                            <div style="background: #f9fafb; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb;">
                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                                            <span style="color: #6b7280; font-size: 14px;">Order Number</span>
                                        </td>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">
                                            <strong style="color: #1f2937; font-size: 14px;">{{ $order->order_number }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                                            <span style="color: #6b7280; font-size: 14px;">Customer</span>
                                        </td>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">
                                            <strong style="color: #1f2937; font-size: 14px;">{{ $order->first_name }} {{ $order->last_name }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                                            <span style="color: #6b7280; font-size: 14px;">Total Amount</span>
                                        </td>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">
                                            <strong style="color: #f97316; font-size: 16px;">₦{{ number_format($order->total_amount) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <span style="color: #6b7280; font-size: 14px;">Items</span>
                                        </td>
                                        <td style="padding: 10px 0; text-align: right;">
                                            <strong style="color: #1f2937; font-size: 14px;">{{ $order->orderItems->sum('quantity') }} item(s)</strong>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- Delivery/Pickup Information -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; padding: 20px; border-left: 4px solid #3b82f6;">
                                <h4 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 11V6l11-4v5M9 11l11-4M9 11l-7 4m18 0v5l-11 4-7-4v-5" stroke="#1e40af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    @if($order->fulfillment_method === 'delivery')
                                        Delivery Information
                                    @else
                                        Pickup Information
                                    @endif
                                </h4>
                                <p style="margin: 0; color: #1e3a8a; font-size: 14px; line-height: 1.6;">
                                    @if($order->fulfillment_method === 'delivery' && $order->shipping_address)
                                        <strong>Address:</strong> {{ $order->shipping_address }}, {{ $order->city }}, {{ $order->state }}
                                    @elseif($order->fulfillment_method === 'pickup' && $order->pickup_location)
                                        <strong>Pickup Location:</strong> {{ $order->pickup_location }}
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Track Order Button -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px; text-align: center;">
                            <a href="{{ config('app.frontend_url') }}/order-confirmation/{{ $order->order_number }}" style="display: inline-block; background: linear-gradient(135deg, #f97316 0%, #fb923c 50%, #fbbf24 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 16px; font-size: 16px; font-weight: 700; box-shadow: 0 10px 30px rgba(251, 146, 60, 0.4); transition: all 0.3s ease;">
                                📦 Track Your Order1
                            </a>
                        </td>
                    </tr>

                    <!-- Help Section -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px;">
                            <div style="background: #f3f4f6; border-radius: 12px; padding: 20px; text-align: center;">
                                <p style="margin: 0 0 10px 0; color: #374151; font-size: 14px; line-height: 1.6;">
                                    <strong>Need Help?</strong>
                                </p>
                                <p style="margin: 0; color: #6b7280; font-size: 13px; line-height: 1.6;">
                                    If you have any questions about your order, our customer support team is here to help.
                                </p>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1f2937 0%, #111827 100%); padding: 40px; text-align: center;">
                            <p style="margin: 0 0 15px 0; color: #d1d5db; font-size: 14px;">
                                <strong style="color: #ffffff;">{{ config('app.name') }}</strong><br>
                                Nigeria's #1 Solar Energy Solutions Provider
                            </p>
                            
                            <p style="margin: 0 0 15px 0; color: #9ca3af; font-size: 13px; line-height: 1.6;">
                                📧 {{ config('mail.from.address') }} | 📞 +234 800 SOLAR-GO<br>
                                📍 Lagos, Nigeria
                            </p>
                            
                            <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 20px; margin-top: 20px;">
                                <p style="margin: 0 0 10px 0; color: #6b7280; font-size: 12px;">
                                    © 2024 {{ config('app.name') }}. All rights reserved.
                                </p>
                                <p style="margin: 0; font-size: 11px;">
                                    <a href="{{ config('app.frontend_url') }}/orders/{{ $order->order_number }}" style="color: #fb923c; text-decoration: none; margin: 0 8px;">Track Order</a>
                                    <span style="color: #4b5563;">|</span>
                                    <a href="{{ config('app.frontend_url') }}/contact" style="color: #fb923c; text-decoration: none; margin: 0 8px;">Contact Support</a>
                                    <span style="color: #4b5563;">|</span>
                                    <a href="{{ config('app.frontend_url') }}/privacy" style="color: #fb923c; text-decoration: none; margin: 0 8px;">Privacy Policy</a>
                                </p>
                            </div>
                            
                            <div style="margin-top: 20px; padding: 15px; background: rgba(251, 146, 60, 0.1); border-radius: 8px; border: 1px solid rgba(251, 146, 60, 0.2);">
                                <p style="margin: 0; color: #fbbf24; font-size: 13px; font-weight: 600;">
                                    ☀️ Powered by 100% Renewable Solar Energy
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <div style="height: 40px;"></div>
            </td>
        </tr>
    </table>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #fff5eb 0%, #fff9f0 50%, #fffbf5 100%);">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background: linear-gradient(135deg, #fff5eb 0%, #fff9f0 50%, #fffbf5 100%); padding: 40px 20px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" style="width: 100%; max-width: 650px; border-collapse: collapse; background: #ffffff; border-radius: 24px; box-shadow: 0 20px 60px rgba(251, 146, 60, 0.15); overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #10b981 0%, #34d399 50%, #6ee7b7 100%); padding: 40px 30px; text-align: center; position: relative;">
                            <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; filter: blur(40px);"></div>
                            <div style="position: absolute; bottom: -30px; left: -30px; width: 120px; height: 120px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; filter: blur(40px);"></div>
                            
                            <!-- Success Icon -->
                            <div style="margin-bottom: 20px; position: relative; z-index: 1;">
                                <div style="display: inline-block; width: 80px; height: 80px; background: rgba(255, 255, 255, 0.3); backdrop-filter: blur(10px); border-radius: 50%; padding: 15px; box-shadow: 0 8px 32px rgba(255, 255, 255, 0.3);">
                                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block; margin: auto;">
                                        <path d="M9 11l3 3L22 4" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </div>
                            
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: relative; z-index: 1;">
                                Order Confirmed!
                            </h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255, 255, 255, 0.95); font-size: 16px; position: relative; z-index: 1;">
                                Thank you for your purchase
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
                                Your order has been placed successfully and payment has been confirmed!
                            </p>
                        </td>
                    </tr>

                    <!-- Order Items -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <h3 style="margin: 0 0 20px 0; color: #ea580c; font-size: 20px; font-weight: 700;">
                                Order Summary
                            </h3>
                            
                            <div style="background: #f9fafb; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb;">
                                @foreach($order->orderItems as $item)
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0; @if(!$loop->last) border-bottom: 1px solid #e5e7eb; @endif">
                                    <div>
                                        <strong style="color: #1f2937; font-size: 16px; display: block; margin-bottom: 5px;">{{ $item->product_name }}</strong>
                                        <span style="color: #6b7280; font-size: 14px;">{{ $item->quantity }} x ₦{{ number_format($item->product_price) }}</span>
                                    </div>
                                    <div style="text-align: right;">
                                        <strong style="color: #f97316; font-size: 16px;">₦{{ number_format($item->total_price) }}</strong>
                                    </div>
                                </div>
                                @endforeach
                                
                                <div style="text-align: right; font-size: 18px; font-weight: bold; margin-top: 20px; padding-top: 15px; border-top: 2px solid #f97316;">
                                    <span style="color: #1f2937;">Total: </span>
                                    <span style="color: #f97316;">₦{{ number_format($order->total_amount) }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Customer Details -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <h3 style="margin: 0 0 20px 0; color: #ea580c; font-size: 20px; font-weight: 700;">
                                Customer Details
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
                                            <span style="color: #6b7280; font-size: 14px;">Email</span>
                                        </td>
                                        <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">
                                            <strong style="color: #1f2937; font-size: 14px;">{{ $order->customer_email }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <span style="color: #6b7280; font-size: 14px;">Phone</span>
                                        </td>
                                        <td style="padding: 10px 0; text-align: right;">
                                            <strong style="color: #1f2937; font-size: 14px;">{{ $order->customer_phone }}</strong>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- Fulfillment Information -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; padding: 20px; border-left: 4px solid #3b82f6;">
                                <h4 style="margin: 0 0 10px 0; color: #1e40af; font-size: 16px; font-weight: 700;">
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
                                <div style="margin-top: 15px;">
                                    <span style="display: inline-block; padding: 6px 12px; background: #3b82f6; color: white; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                        Status: {{ ucfirst($order->status) }}
                                    </span>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- What's Next -->
                    <tr>
                        <td style="padding: 0 40px 30px 40px;">
                            <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 12px; padding: 20px; border-left: 4px solid #10b981;">
                                <h4 style="margin: 0 0 15px 0; color: #065f46; font-size: 16px; font-weight: 700;">
                                    What happens next?
                                </h4>
                                <div style="color: #064e3b; font-size: 14px; line-height: 1.8;">
                                    <div style="margin-bottom: 8px;">
                                        <span style="color: #10b981; font-weight: bold;">✅</span> <strong>Order Confirmation</strong> (Complete)
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <span style="color: #6b7280; font-weight: bold;">⏳</span> <strong>Processing</strong> - We're preparing your items
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <span style="color: #6b7280; font-weight: bold;">🚚</span> <strong>Shipped</strong> - Your order is on the way
                                    </div>
                                    <div>
                                        <span style="color: #6b7280; font-weight: bold;">📦</span> <strong>Delivered</strong> - Enjoy your products!
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Track Order Button -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px; text-align: center;">
                            <a href="{{ config('app.frontend_url') }}/order-confirmation/{{ $order->order_number }}" style="display: inline-block; background: linear-gradient(135deg, #f97316 0%, #fb923c 50%, #fbbf24 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 16px; font-size: 16px; font-weight: 700; box-shadow: 0 10px 30px rgba(251, 146, 60, 0.4); transition: all 0.3s ease;">
                                📦 Track Your Order
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
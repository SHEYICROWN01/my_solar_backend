<?php

namespace App\Mail;

use App\Models\CustomerPreOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PreOrderStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public CustomerPreOrder $customerPreOrder;
    public string $previousStatus;

    /**
     * Create a new message instance.
     */
    public function __construct(CustomerPreOrder $customerPreOrder, string $previousStatus)
    {
        $this->customerPreOrder = $customerPreOrder;
        $this->previousStatus = $previousStatus;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pre-Order Status Update - ' . $this->customerPreOrder->pre_order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $data = [
            'customerPreOrder' => $this->customerPreOrder,
            'preOrder' => $this->customerPreOrder->preOrder,
            'category' => $this->customerPreOrder->preOrder->category,
            'previousStatus' => $this->previousStatus,
            'payRemainingLink' => null,
            'frontendDeepLink' => null,
        ];

        // Generate payment links if order is ready for pickup and deposit is paid
        if ($this->customerPreOrder->status === 'ready_for_pickup' && 
            $this->customerPreOrder->payment_status === 'deposit_paid') {
            
            // Option A: Server redirect one-click link (expires in 72 hours)
            $data['payRemainingLink'] = URL::temporarySignedRoute(
                'customer-pre-orders.pay-remaining',
                now()->addHours(72),
                ['preOrderNumber' => $this->customerPreOrder->pre_order_number]
            );

            // Option B: Frontend deep link with token
            $tokenData = [
                'pre_order_id' => $this->customerPreOrder->id,
                'pre_order_number' => $this->customerPreOrder->pre_order_number,
                'customer_email' => $this->customerPreOrder->customer_email,
                'payment_type' => 'full',
                'expires_at' => now()->addHours(72)->timestamp
            ];
            $token = encrypt($tokenData);
            
            $frontendUrl = config('app.frontend_url', config('app.url'));
            $data['frontendDeepLink'] = $frontendUrl . "/pre-orders/confirmation/{$this->customerPreOrder->pre_order_number}?action=pay-remaining&token={$token}";
        }

        return new Content(
            view: 'emails.pre-order-status-updated',
            with: $data
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

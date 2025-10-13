<?php

namespace App\Mail;

use App\Models\CustomerPreOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PreOrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public CustomerPreOrder $customerPreOrder;

    /**
     * Create a new message instance.
     */
    public function __construct(CustomerPreOrder $customerPreOrder)
    {
        $this->customerPreOrder = $customerPreOrder;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pre-Order Confirmation - ' . $this->customerPreOrder->pre_order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.pre-order-confirmation',
            with: [
                'customerPreOrder' => $this->customerPreOrder,
                'preOrder' => $this->customerPreOrder->preOrder,
                'category' => $this->customerPreOrder->preOrder->category,
            ]
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

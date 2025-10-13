<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name',
        'contact_email',
        'phone_number',
        'payment_settings',
        'shipping_settings',
        'notification_preferences',
    ];

    protected $casts = [
        'payment_settings' => 'array',
        'shipping_settings' => 'array',
        'notification_preferences' => 'array',
    ];
}

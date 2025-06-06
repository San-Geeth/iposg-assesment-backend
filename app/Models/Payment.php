<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'customer_email', 'reference_no', 'payment_date', 'currency', 'amount', 'usd_amount', 'exchange_rate_id',
        'processed', 'invoice_id'
    ];

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'customer_id','customer_email', 'reference_no', 'payment_date', 'currency', 'amount',
        'exchange_rate', 'usd_amount', 'exchange_rate_id', 'processed', 'invoice_id', 'file_id'
    ];

    public function file()
    {
        return $this->belongsTo(File::class);
    }

}

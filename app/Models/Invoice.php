<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['payment_ids'];

    protected $casts = [
        'payment_ids' => 'array',
    ];
}

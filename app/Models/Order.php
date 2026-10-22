<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'payment_method',
        'total_cents',
        'phone',
        'address',
        'city',
        'paymob_intention_id',
        'paymob_transaction_id',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'customer_id',
        'machine_id',
        'order_date',
        'total_price',
        'status',
        'payment_status',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
}
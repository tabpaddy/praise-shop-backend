<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'user_id',
        'delivery_information_id',
        'amount',
        'invoice_no',
        'payment_reference',
        'order_status',
        'items',
        'payment_method',
        'payment_status'
    ];


    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function user(): BelongsTo{
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    public function deliveryInformation(): BelongsTo
    {
        return $this->belongsTo(DeliveryInformation::class, 'delivery_information_id', 'id');
    }
}

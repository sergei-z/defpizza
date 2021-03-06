<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'phone', 'email'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public function state()
    {
        return $this->belongsTo(OrderState::class);
    }

    public function appliedPromo()
    {
        return $this->belongsTo(Promo::class);
    }

    public function deliveryType()
    {
        return $this->belongsTo(DeliveryType::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, OrderItem::class, 'order_id', 'item_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id');
    }
}

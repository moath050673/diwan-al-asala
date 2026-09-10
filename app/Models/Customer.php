<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'whatsapp', 'city', 'area', 'address', 'notes'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

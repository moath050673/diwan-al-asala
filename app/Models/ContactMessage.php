<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'phone', 'message', 'status'];

    protected static function booted()
    {
        static::creating(function ($msg) {
            $msg->created_at = now();
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscribe extends Model
{
    //
    use HasFactory;
    
    protected $fillable = [
        'email',
        'ip_address',
        'created_at'
    ];

    public $timestamps = false;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Product extends Model
{
    //
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'description',
        'keyword',
        'price',
        'image1',
        'image2',
        'image3',
        'image4',
        'image5',
        'category_id',
        'sub_category_id',
        'sizes',
        'bestseller',
    ];

    protected $casts = [
        'sizes' => 'array',
        'bestseller' => 'boolean',
        'price' => 'decimal:2',
    ];

}

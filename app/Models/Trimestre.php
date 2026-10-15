<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trimestre extends Model
{
    use HasFactory, SoftDeletes; 
    protected $fillable = [
        'txt_designationtri', 
    ];
}

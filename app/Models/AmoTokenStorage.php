<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmoTokenStorage extends Model
{
    protected $fillable = [
        'domain',
        'tokens'
    ];
    protected function casts(): array
    {
        return [
            'tokens'=>'array'
        ];
    }
}

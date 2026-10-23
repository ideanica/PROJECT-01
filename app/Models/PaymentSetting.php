<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $fillable = ['dp_percentage'];

    protected function casts(): array
    {
        return ['dp_percentage' => 'decimal:2'];
    }
}

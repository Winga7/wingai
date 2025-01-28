<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IaPersonalization extends Model
{
    protected $fillable = [
        'identity',
        'behavior',
        'slash_commands'
    ];

    protected $casts = [
        'slash_commands' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

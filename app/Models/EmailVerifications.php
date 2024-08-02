<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailVerifications extends Model
{
    use HasFactory;
    protected $table = 'email_verifications';

    protected $fillable = [
        'email',
        'token',
        'token_type',
        'user_id',
        'active'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostReactions extends Model
{
    use HasFactory;
    protected $table = 'post_reactions';
    protected $fillable = ['user_id', 'post_id', 'reaction'];
}

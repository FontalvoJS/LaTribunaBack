<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostComments extends Model
{
    use HasFactory;
    protected $table = 'post_comments';
    protected $guarded = [];
    protected $fillable = ['post_id', 'user_id', 'parent_id', 'content', 'active'];
    // app\Models\PostComments.php
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

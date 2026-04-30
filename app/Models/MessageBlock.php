<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageBlock extends Model
{
    protected $fillable = ['formation_id', 'blocked_user_id', 'blocked_by'];
}
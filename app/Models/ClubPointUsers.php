<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubPointUsers extends Model
{
    use HasFactory;

    protected $table = 'club_points_users';
    public $timestamps = false;
}

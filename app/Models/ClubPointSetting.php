<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubPointSetting extends Model
{
    use HasFactory;

    protected $table = 'club_points_setting';
    public $timestamps = false;
}

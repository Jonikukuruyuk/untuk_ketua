<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClubPoint extends Model
{
    public function user(){
    	return $this->belongsTo(\App\Models\user::class);
    }

    public function order(){
    	return $this->belongsTo(\App\Models\Order::class);
    }
}

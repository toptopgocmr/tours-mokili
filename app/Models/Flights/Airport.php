<?php

namespace App\Models\Flights;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $guarded = [];

    public function label(): string
    {
        return $this->city.' ('.$this->iata.')';
    }
}

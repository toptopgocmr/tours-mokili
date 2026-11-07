<?php

namespace App\Models\Fret;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ClaimMessage extends Model
{
    protected $table = 'freight_claim_messages';

    protected $guarded = [];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

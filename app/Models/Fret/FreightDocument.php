<?php

namespace App\Models\Fret;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Piece stockee sur le disque PRIVE ; telechargement uniquement via le controleur (controle d'acces + journal). */
class FreightDocument extends Model
{
    protected $guarded = [];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return ['expires_at' => 'date'];
    }

    public function documentable()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

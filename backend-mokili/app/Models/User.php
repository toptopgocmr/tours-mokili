<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'country_code',
        'password',
        'avatar',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function peexVerifications()
    {
        return $this->hasMany(PeexVerification::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isPartner(): bool
    {
        return $this->role === 'partner';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['admin', 'agent'], true);
    }

    /**
     * Where to send the user right after login/registration - each
     * role lands in its own space (see routes/web.php: admin.*,
     * partner.*, or the public "home" route for clients).
     */
    public function homeRoute(): string
    {
        return match ($this->role) {
            'admin', 'agent' => 'admin.dashboard',
            'partner' => 'partner.dashboard',
            default => 'home',
        };
    }

    // Listings owned by this user when role = partner.
    public function lodgingListings()
    {
        return $this->hasMany(\App\Models\Logement\LodgingListing::class, 'owner_id');
    }

    public function vehicles()
    {
        return $this->hasMany(\App\Models\Voiture\Vehicle::class, 'owner_id');
    }

    public function events()
    {
        return $this->hasMany(\App\Models\Divertissement\Event::class, 'organizer_id');
    }

    public function products()
    {
        return $this->hasMany(\App\Models\Marketplace\Product::class, 'seller_id');
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


#[Fillable(['name', 'phone_number', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function profile(){
        return $this->hasOne(Profile::class);
    }
    public function orders(){
        return $this->hasMany(Order::class);
    }
    public function wallet(){
        return $this->hasOne(Wallet::class);
    }
    public function carts(){
        return $this->hasMany(Cart::class);
    }
    public function cart(){
        return $this->carts();
    }
    public function targets()
    {
        return $this->belongsToMany(Target::class, 'user_targets', 'user_id', 'target_id');
    }

    public function monthlyTargets()
    {
        return $this->belongsToMany(MonthlyTarget::class, 'user_monthly_targets', 'user_id', 'monthly_target_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDelivery(): bool
    {
        return $this->role === 'delivery';
    }
    public function isCustomer(): bool
    {
        return $this->role  === 'customer';
    }
}

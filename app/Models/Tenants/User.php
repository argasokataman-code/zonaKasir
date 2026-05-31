<?php

namespace App\Models\Tenants;

use Filament\Models\Contracts\FilamentUser;
use App\Models\Tenants\UploadedFile;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements FilamentUser, HasAvatar, HasName
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'fcm_token',
        'is_owner',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'fcm_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function secureInitialPrice(): HasOne
    {
        return $this->hasOne(SecureInitialPrice::class);
    }

    public function cashDrawer(): HasOne
    {
        return $this->hasOne(CashDrawer::class);
    }

    public function sellings(): HasMany
    {
        return $this->hasMany(Selling::class);
    }

    public function getFilamentName(): string
    {
        return $this->name ?? $this->email ?? '';
    }

    public function getFullNameAttribute()
    {
        return $this->name ?? $this->email ?? '';
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile?->photo ? UploadedFile::urlFromPath($this->profile->photo, config('filesystems.upload_disk')) : null;
    }

    public function cashierName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name ?? $this->email
        );
    }

    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }

    public function scopeOwner(Builder $builder)
    {
        return $builder->whereIsOwner(true);
    }
}

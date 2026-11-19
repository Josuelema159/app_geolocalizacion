<?php

namespace App\Models;

use App\Concerns\HasTeams;
use Database\Factories\UserFactory;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property string|null $phone
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $privacy_consent_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 */
#[Fillable(['name', 'email', 'password', 'current_team_id', 'role', 'phone'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable {
        HasTeams::teams insteadof HasRoles;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'privacy_consent_at'      => 'datetime',
            'password'                => 'hashed',
            /* @chisel-2fa */
            'two_factor_confirmed_at' => 'datetime',
            /* @end-chisel-2fa */
        ];
    }

    public function entrepreneurProfile(): HasOne
    {
        return $this->hasOne(EntrepreneurProfile::class);
    }

    public function ownedEstablishments(): HasMany
    {
        return $this->hasMany(Establishment::class, 'merchant_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEmprendedor(): bool
    {
        return $this->role === 'emprendedor';
    }

    public function isComerciante(): bool
    {
        return $this->role === 'comercio';
    }
}

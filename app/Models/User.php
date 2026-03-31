<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_MEDIA_MANAGER = 'media_manager';
    public const ROLE_IMPORT_MANAGER = 'import_manager';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
        ];
    }

    public static function allowedRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_EDITOR,
            self::ROLE_MEDIA_MANAGER,
            self::ROLE_IMPORT_MANAGER,
        ];
    }

    public function getRoleName(): string
    {
        $role = trim((string) ($this->role ?? ''));

        if ($role !== '' && in_array($role, self::allowedRoles(), true)) {
            return $role;
        }

        return $this->is_admin ? self::ROLE_ADMIN : 'user';
    }

    public function hasRole(string ...$roles): bool
    {
        $currentRole = $this->getRoleName();

        return in_array($currentRole, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function isAdminUser(): bool
    {
        return $this->is_admin || $this->hasRole(
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_EDITOR,
            self::ROLE_MEDIA_MANAGER,
            self::ROLE_IMPORT_MANAGER,
        );
    }

    public function canManageAdminPanel(): bool
    {
        return $this->hasRole(
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_EDITOR,
            self::ROLE_MEDIA_MANAGER,
            self::ROLE_IMPORT_MANAGER,
        ) || $this->is_admin;
    }

    public function canManagePosts(): bool
    {
        return $this->hasRole(
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_EDITOR,
        ) || $this->is_admin;
    }

    public function canPublishPosts(): bool
    {
        return $this->hasRole(
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
        ) || $this->is_admin;
    }

    public function canDeletePosts(): bool
    {
        return $this->hasRole(
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
        ) || $this->is_admin;
    }

    public function canManageMedia(): bool
    {
        return $this->hasRole(
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_MEDIA_MANAGER,
        ) || $this->is_admin;
    }

    public function canImportAiPosts(): bool
    {
        return $this->hasRole(
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_IMPORT_MANAGER,
        ) || $this->is_admin;
    }

    public function canManagePopups(): bool
    {
        return $this->hasRole(
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
        ) || $this->is_admin;
    }

    public function canManageLeadBoxes(): bool
{
    return $this->hasRole(
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
    ) || $this->is_admin;
}

    public function canManageTaxonomy(): bool
    {
        return $this->hasRole(
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_EDITOR,
        ) || $this->is_admin;
    }
}

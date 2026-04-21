<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';
    case Parent = 'parent';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Pemilik',
            self::Admin => 'Admin',
            self::Teacher => 'Guru',
            self::Student => 'Siswa',
            self::Parent => 'Orang Tua',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::Owner => ['*'],
            self::Admin => ['manage-team', 'manage-content', 'manage-academic'],
            self::Teacher => [],
            self::Student => [],
            self::Parent => [],
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::Owner => 5,
            self::Admin => 4,
            self::Teacher => 3,
            self::Student => 2,
            self::Parent => 1,
        };
    }

    /**
     * Check if this role is at least as privileged as another role.
     */
    public function isAtLeast(TeamRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Determine if the role has the given TeamPermission.
     */
    public function hasPermission(TeamPermission $permission): bool
    {
        $permissions = $this->permissions();

        if (in_array('*', $permissions)) {
            return true;
        }

        // Map string-based permissions to legacy TeamPermission cases
        $permissionMap = [
            'manage-team' => [
                TeamPermission::UpdateTeam,
                TeamPermission::CreateInvitation,
                TeamPermission::CancelInvitation,
            ],
        ];

        foreach ($permissionMap as $stringPerm => $teamPerms) {
            if (in_array($stringPerm, $permissions) && in_array($permission, $teamPerms)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the roles that can be assigned to team members (excludes Owner).
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignable(): array
    {
        return collect(self::cases())
            ->filter(fn (self $role) => $role !== self::Owner)
            ->map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->toArray();
    }
}

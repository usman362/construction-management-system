<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // ─── Role Constants ─────────────────────────────────────────
    const ROLE_ADMIN = 'admin';
    const ROLE_PROJECT_MANAGER = 'project_manager';
    // 2026-04-28 (Brenda): Site Manager is the on-site authority who can
    // approve timesheets alongside the Admin. Distinct from Project Manager
    // (office-based PM) because BAK's structure has site supervisors who
    // sign off labor without owning the project P&L.
    const ROLE_SITE_MANAGER = 'site_manager';
    const ROLE_ACCOUNTANT = 'accountant';
    const ROLE_FIELD = 'field';
    const ROLE_FOREMAN = 'foreman';
    const ROLE_VIEWER = 'viewer';

    const ROLES = [
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_PROJECT_MANAGER => 'Project Manager',
        self::ROLE_SITE_MANAGER => 'Site Manager',
        self::ROLE_ACCOUNTANT => 'Accountant',
        self::ROLE_FOREMAN => 'Foreman',
        self::ROLE_FIELD => 'Field Staff',
        self::ROLE_VIEWER => 'Viewer',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'is_active' => 'boolean',
        ];
    }

    // ─── Role Helpers ───────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isProjectManager(): bool
    {
        return $this->role === self::ROLE_PROJECT_MANAGER;
    }

    public function isSiteManager(): bool
    {
        return $this->role === self::ROLE_SITE_MANAGER;
    }

    public function isForeman(): bool
    {
        return $this->role === self::ROLE_FOREMAN;
    }

    public function isAccountant(): bool
    {
        return $this->role === self::ROLE_ACCOUNTANT;
    }

    /**
     * Can this user approve / reject timesheets?
     *
     * Brenda's policy (04.28.2026): only the Admin (her) and Site Managers
     * sign off on labor. PMs/accountants/field staff can submit and view but
     * cannot approve. Foremen submit on behalf of their crew.
     */
    public function canApproveTimesheets(): bool
    {
        return $this->hasRole([self::ROLE_ADMIN, self::ROLE_SITE_MANAGER]);
    }

    public function isField(): bool
    {
        return $this->role === self::ROLE_FIELD;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    /**
     * Check if user has one of the given roles.
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            return $this->role === $roles;
        }
        return in_array($this->role, $roles);
    }

    /**
     * Fetch active users in any of the given roles, with a non-empty email.
     *
     * Used by notification dispatchers (TimesheetController, RfiController,
     * etc.) to fan out emails to the right approvers without duplicating the
     * "active + has email" filter at every call site.
     */
    public static function notifiableForRoles(array $roles): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->whereIn('role', $roles)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();
    }

    /**
     * Check if user can access a given section.
     */
    public function canAccess(string $section): bool
    {
        // Admin can access everything
        if ($this->isAdmin()) {
            return true;
        }

        $permissions = [
            'dashboard' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_ACCOUNTANT, self::ROLE_FIELD, self::ROLE_VIEWER],
            'projects' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_ACCOUNTANT, self::ROLE_FIELD, self::ROLE_VIEWER],
            'employees' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_ACCOUNTANT],
            'crafts' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER],
            'crews' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_FIELD],
            'shifts' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER],
            'timesheets' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_ACCOUNTANT, self::ROLE_FIELD],
            'payroll' => [self::ROLE_ADMIN, self::ROLE_ACCOUNTANT],
            'cost-codes' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_ACCOUNTANT],
            'clients' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_ACCOUNTANT],
            'invoices' => [self::ROLE_ADMIN, self::ROLE_ACCOUNTANT],
            'purchase-orders' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_ACCOUNTANT],
            'vendors' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_ACCOUNTANT],
            'equipment' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_FIELD],
            'materials' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_FIELD],
            'billing' => [self::ROLE_ADMIN, self::ROLE_ACCOUNTANT],
            'reports' => [self::ROLE_ADMIN, self::ROLE_PROJECT_MANAGER, self::ROLE_ACCOUNTANT],
            'users' => [self::ROLE_ADMIN],
        ];

        $allowedRoles = $permissions[$section] ?? [self::ROLE_ADMIN];
        return in_array($this->role, $allowedRoles);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    // ─── Relationships ──────────────────────────────────────────

    public function approvedTimesheets()
    {
        return $this->hasMany(Timesheet::class, 'approved_by');
    }

    public function createdEstimates()
    {
        return $this->hasMany(Estimate::class, 'created_by');
    }

    public function dailyLogs()
    {
        return $this->hasMany(DailyLog::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_number',
        'name',
        'client_id',
        'description',
        'address',
        'city',
        'state',
        'zip',
        'status',
        'start_date',
        'end_date',
        'substantial_completion_date',
        'original_budget',
        'current_budget',
        'estimate',
        'contract_value',
        'retainage_percent',
        'default_per_diem_rate',
        'po_number',
        'po_date',
        'latitude',
        'longitude',
        'geofence_radius_m',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'substantial_completion_date' => 'date',
        'po_date' => 'date',
        'original_budget' => 'decimal:2',
        'current_budget' => 'decimal:2',
        'estimate' => 'decimal:2',
        'contract_value' => 'decimal:2',
        'retainage_percent' => 'decimal:2',
        'default_per_diem_rate' => 'decimal:2',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'geofence_radius_m' => 'integer',
    ];

    /** @var list<string> */
    protected $appends = ['budget'];

    /**
     * Form/API "budget" maps to stored current_budget (and original on create).
     */
    public function getBudgetAttribute(): ?string
    {
        if ($this->current_budget === null) {
            return null;
        }

        return (string) $this->current_budget;
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class);
    }

    /**
     * Cost codes enabled for this project (Brenda 2026-06-17 — project-scoped
     * phase codes). When this is empty for a project, callers should fall
     * back to the full global library so legacy jobs don't break.
     */
    public function costCodes(): BelongsToMany
    {
        return $this->belongsToMany(CostCode::class, 'project_cost_codes')
            ->withPivot(['is_active', 'sort_order', 'notes'])
            ->withTimestamps()
            ->orderBy('cost_codes.code');
    }

    /**
     * The cost-code list the UI pickers should use: project-scoped when
     * configured, full global library when not. Pass to dropdowns.
     */
    public function effectiveCostCodes()
    {
        if ($this->costCodes()->wherePivot('is_active', true)->exists()) {
            return $this->costCodes()->wherePivot('is_active', true)->get();
        }
        return CostCode::orderBy('code')->get();
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function crews(): HasMany
    {
        return $this->hasMany(Crew::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function commitments(): HasMany
    {
        return $this->hasMany(Commitment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function changeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class);
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(CostEntry::class);
    }

    public function equipmentAssignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function materialUsages(): HasMany
    {
        return $this->hasMany(MaterialUsage::class);
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    public function manhourBudgets(): HasMany
    {
        return $this->hasMany(ManhourBudget::class);
    }

    public function billingInvoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class);
    }

    public function lienWaivers(): HasMany
    {
        return $this->hasMany(LienWaiver::class);
    }

    public function rfis(): HasMany
    {
        return $this->hasMany(Rfi::class);
    }

    public function timeClockEntries(): HasMany
    {
        return $this->hasMany(TimeClockEntry::class);
    }

    /**
     * Haversine distance, in meters, from this project's geofence center
     * to an arbitrary (lat, lng) pair. Returns null when the project has
     * no center configured.
     */
    public function distanceToMeters(?float $lat, ?float $lng): ?int
    {
        if ($this->latitude === null || $this->longitude === null || $lat === null || $lng === null) {
            return null;
        }

        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat - (float) $this->latitude);
        $dLng = deg2rad($lng - (float) $this->longitude);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad((float) $this->latitude)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadius * $c);
    }

    /**
     * True when the given coordinates fall within the project's geofence.
     * Null = indeterminate (no geofence configured or no coords provided).
     */
    public function isWithinGeofence(?float $lat, ?float $lng): ?bool
    {
        if ($this->geofence_radius_m === null) {
            return null;
        }
        $distance = $this->distanceToMeters($lat, $lng);
        if ($distance === null) {
            return null;
        }
        return $distance <= (int) $this->geofence_radius_m;
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyLog::class);
    }

    /**
     * Total retainage withheld across all non-voided billing invoices and still un-released.
     */
    public function getRetainageHeldAttribute(): float
    {
        return (float) $this->billingInvoices()
            ->where('retainage_released', false)
            ->sum('retainage_amount');
    }

    /**
     * Total retainage that has been formally released back to the owner/contractor.
     */
    public function getRetainageReleasedAttribute(): float
    {
        return (float) $this->billingInvoices()
            ->where('retainage_released', true)
            ->sum('retainage_amount');
    }

    public function payrollEntries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function perDiemRates(): HasMany
    {
        return $this->hasMany(PerDiemRate::class);
    }

    public function projectBillableRates(): HasMany
    {
        return $this->hasMany(ProjectBillableRate::class);
    }

    public function getProfitAttribute()
    {
        return $this->effectiveEstimate() - $this->effectiveBudget();
    }

    public function getProfitPercentageAttribute()
    {
        $est = $this->effectiveEstimate();
        if ($est == 0) {
            return 0;
        }
        return (($est - $this->effectiveBudget()) / $est) * 100;
    }

    /**
     * 2026-05-30 (Brenda): "On the dashboard the %Complete, Profit Margin
     * and cash flow are not updating." — these accessors used to look at
     * the raw `estimate` / `current_budget` columns only, which most
     * projects leave empty (the real values live in the related Estimate
     * rows and budget_lines). Switched to an effective-estimate / effective-
     * budget helper so the dashboard numbers move the moment a user enters
     * an estimate line, a budget line, or types a contract value.
     */
    public function effectiveEstimate(): float
    {
        // 1. Column on the project record (manually typed)
        $direct = (float) ($this->estimate ?? 0);
        if ($direct > 0) {
            return $direct;
        }
        // 2. Approved estimate(s) total
        $approved = (float) $this->estimates()
            ->where('status', 'approved')
            ->sum('total_amount');
        if ($approved > 0) {
            return $approved;
        }
        // 3. Any estimate(s) total — drafts, sent, etc.
        $any = (float) $this->estimates()->sum('total_amount');
        if ($any > 0) {
            return $any;
        }
        // 4. Sum of budget_lines.revised_amount (or budget_amount) — fallback
        $lineTotal = (float) $this->budgetLines()->get()->sum(function ($l) {
            return (float) ($l->revised_amount ?? $l->budget_amount ?? 0);
        });
        if ($lineTotal > 0) {
            return $lineTotal;
        }
        // 5. contract_value
        return (float) ($this->contract_value ?? 0);
    }

    public function effectiveBudget(): float
    {
        $direct = (float) ($this->current_budget ?? 0);
        if ($direct > 0) {
            return $direct;
        }
        return (float) $this->budgetLines()->get()->sum(function ($l) {
            return (float) ($l->revised_amount ?? $l->budget_amount ?? 0);
        });
    }

    /**
     * Total of approved change orders — added to the "denominator" so
     * % Committed and Margin both reflect approved scope changes.
     */
    public function effectiveApprovedCOs(): float
    {
        return (float) $this->changeOrders()
            ->where('status', 'approved')
            ->sum('amount');
    }

    /**
     * Real committed-cost total: vendor commitments + invoices (no
     * double-count) + booked labor from approved/submitted timesheets.
     * Labor is the chunk that was missing before, which made the
     * dashboard Margin and %Complete look stuck.
     */
    public function effectiveCommittedCost(): float
    {
        $invoices    = $this->invoices()->get();
        $commitments = $this->commitments()->get();
        $invoicedIds = $invoices->pluck('commitment_id')->filter()->unique();
        $uncommitted = $commitments->whereNotIn('id', $invoicedIds);
        $vendor = (float) $invoices->sum('amount') + (float) $uncommitted->sum('amount');

        $labor = (float) $this->timesheets()
            ->where('status', '!=', 'rejected')
            ->sum('total_cost');

        return $vendor + $labor;
    }

    /**
     * % Committed — what fraction of the effective budget (+approved COs)
     * has been committed so far. Drives the "% Committed" column on the
     * dashboard project list. Updates as soon as estimates / budget lines
     * / commitments / labor flow in.
     */
    public function getCommittedPercentageAttribute(): float
    {
        $denom = $this->effectiveBudget() + $this->effectiveApprovedCOs();
        if ($denom <= 0) {
            // Fall back to estimate so we don't show 0% on an active
            // project that only has an estimate (no separate budget yet).
            $denom = $this->effectiveEstimate();
        }
        if ($denom <= 0) {
            return 0;
        }
        return round(($this->effectiveCommittedCost() / $denom) * 100, 1);
    }

    /**
     * Margin % = (effective estimate - committed cost) / estimate * 100.
     * "Updating" because both sides use the fallback helpers now, so
     * editing an estimate line OR booking a vendor invoice OR approving
     * a timesheet will move this number.
     */
    public function getProfitMarginAttribute(): float
    {
        $estimate = $this->effectiveEstimate() + $this->effectiveApprovedCOs();
        if ($estimate <= 0) {
            return 0;
        }
        return round((($estimate - $this->effectiveCommittedCost()) / $estimate) * 100, 1);
    }
}

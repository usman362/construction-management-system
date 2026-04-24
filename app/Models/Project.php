<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        return $this->estimate - $this->current_budget;
    }

    public function getProfitPercentageAttribute()
    {
        if ($this->estimate == 0) {
            return 0;
        }
        return (($this->estimate - $this->current_budget) / $this->estimate) * 100;
    }

    /**
     * % of current budget that is already committed (PO / subcontracts).
     */
    public function getCommittedPercentageAttribute(): float
    {
        $budget = (float) ($this->current_budget ?? 0);
        if ($budget <= 0) {
            return 0;
        }
        $committed = (float) $this->commitments()->sum('amount');
        return round(($committed / $budget) * 100, 1);
    }

    /**
     * Profit margin % = (estimate - committed cost) / estimate * 100.
     * Uses commitments + invoices (avoiding double-count) as actual cost.
     */
    public function getProfitMarginAttribute(): float
    {
        $estimate = (float) ($this->estimate ?? 0);
        if ($estimate <= 0) {
            return 0;
        }
        $invoices = $this->invoices()->get();
        $commitments = $this->commitments()->get();
        $invoicedCommitmentIds = $invoices->pluck('commitment_id')->filter()->unique();
        $uninvoicedCommitments = $commitments->whereNotIn('id', $invoicedCommitmentIds);
        $totalCost = (float) $invoices->sum('amount') + (float) $uninvoicedCommitments->sum('amount');
        return round((($estimate - $totalCost) / $estimate) * 100, 1);
    }
}

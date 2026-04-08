<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'po_number',
        'po_date',
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

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyLog::class);
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

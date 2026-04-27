<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolAssignment extends Model
{
    protected $fillable = [
        'tool_id', 'employee_id', 'project_id',
        'issued_date', 'due_back_date', 'returned_date',
        'notes', 'issued_by',
    ];

    protected $casts = [
        'issued_date'   => 'date',
        'due_back_date' => 'date',
        'returned_date' => 'date',
    ];

    public function tool(): BelongsTo     { return $this->belongsTo(Tool::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function project(): BelongsTo  { return $this->belongsTo(Project::class); }
    public function issuer(): BelongsTo   { return $this->belongsTo(User::class, 'issued_by'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'vendor_code',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}

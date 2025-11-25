<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use Filterable;
    protected $guarded = false;
    public $timestamps = false;
}

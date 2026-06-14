<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $connection = 'central';

    protected $guarded = ['id'];

    public $incrementing = false;

    protected $keyType = 'string';

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

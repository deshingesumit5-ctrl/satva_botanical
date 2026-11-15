<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcRequestResult extends Model
{
    protected $guarded = [];

    public function qcRequest()
    {
        return $this->belongsTo(QcRequest::class);
    }

    public function qcTest()
    {
        return $this->belongsTo(QcTest::class);
    }
}

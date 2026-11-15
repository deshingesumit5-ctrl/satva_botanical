<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeQcTest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'requires_approval' => 'boolean',
        'blocks_next_stage' => 'boolean',
    ];

    public function recipeStage()
    {
        return $this->belongsTo(RecipeStage::class);
    }

    public function qcTest()
    {
        return $this->belongsTo(QcTest::class);
    }
}

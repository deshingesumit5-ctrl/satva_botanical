<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeStage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sequence_no' => 'integer',
        'occurrence_no' => 'integer',
        'is_mandatory' => 'boolean',
        'is_optional' => 'boolean',
        'is_conditional' => 'boolean',
        'is_repeatable' => 'boolean',
        'min_repetitions' => 'integer',
        'acceptance_parameter_ids' => 'array',
        'expected_process_conditions' => 'array',
        'qc_required' => 'boolean',
        'supervisor_auth_required' => 'boolean',
    ];

    public function recipeVersion()
    {
        return $this->belongsTo(RecipeVersion::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function activities()
    {
        return $this->hasMany(RecipeStageActivity::class)->orderBy('sequence_no');
    }

    public function parameters()
    {
        return $this->hasMany(RecipeParameter::class)->orderBy('display_sequence');
    }

    public function materials()
    {
        return $this->hasMany(RecipeMaterial::class);
    }

    public function inputMaterials()
    {
        return $this->hasMany(RecipeMaterial::class)->where('direction', 'input');
    }

    public function outputMaterials()
    {
        return $this->hasMany(RecipeMaterial::class)->where('direction', 'output');
    }

    public function equipments()
    {
        return $this->hasMany(RecipeEquipment::class);
    }

    public function qcTests()
    {
        return $this->hasMany(RecipeQcTest::class);
    }
}

<?php

namespace App\Services;

class RecipeBuilderNav
{
    public static function render(int $currentStep, int $recipeId): string
    {
        $steps = [
            1 => '1. Basic Info',
            2 => '2. Stage Flow',
            3 => '3. Activities',
            4 => '4. Parameters',
            5 => '5. Materials & Solvents',
            6 => '6. Equipments',
            7 => '7. QC Tests',
            8 => '8. Review & Activate',
        ];

        $html = '<div class="card card-sattva mb-4"><div class="card-sattva-body p-2"><div class="row g-1 text-center">';
        foreach ($steps as $s => $label) {
            $isActive = ($s === $currentStep);
            $isPast = ($s < $currentStep);
            $cls = $isActive ? 'btn-primary-sattva text-white fw-bold shadow-sm' : ($isPast ? 'btn-light text-success border-success-subtle' : 'btn-light text-muted');
            $html .= '<div class="col-6 col-md-3 col-lg">';
            $html .= '<a href="' . route('recipe.builder.step', ['recipe' => $recipeId, 'step' => $s]) . '" class="btn btn-sm w-100 text-truncate ' . $cls . '">';
            if ($isPast) $html .= '<i class="bi bi-check-circle me-1"></i>';
            $html .= $label . '</a></div>';
        }
        $html .= '</div></div></div>';
        return $html;
    }
}

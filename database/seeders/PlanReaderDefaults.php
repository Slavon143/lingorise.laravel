<?php

namespace Database\Seeders;

use App\Enums\PlanCode;
use App\Models\Plan;
use App\Models\PlanReaderSettings;
use App\Services\Plans\PlanDefaults;
use Illuminate\Database\Seeder;

class PlanReaderDefaults extends Seeder
{
    public function run(): void
    {
        $freePlan = Plan::where('code', PlanCode::Free->value)->first();
        $premiumPlan = Plan::where('code', PlanCode::Premium->value)->first();
        $proPlan = Plan::where('code', PlanCode::Pro->value)->first();

        if ($freePlan) {
            PlanReaderSettings::updateOrCreate(['plan_id' => $freePlan->id], PlanDefaults::free());
        }

        if ($premiumPlan) {
            PlanReaderSettings::updateOrCreate(['plan_id' => $premiumPlan->id], PlanDefaults::premium());
        }

        if ($proPlan) {
            PlanReaderSettings::updateOrCreate(['plan_id' => $proPlan->id], PlanDefaults::pro());
        }
    }
}

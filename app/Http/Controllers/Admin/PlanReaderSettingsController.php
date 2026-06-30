<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePlanReaderSettingsRequest;
use App\Models\Plan;
use App\Models\PlanReaderSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanReaderSettingsController extends Controller
{
    public function edit(Plan $plan): View
    {
        $settings = $plan->readerSettings ?: new PlanReaderSettings(['plan_id' => $plan->id]);

        return view('admin.plans.reader-settings.edit', compact('plan', 'settings'));
    }

    public function update(UpdatePlanReaderSettingsRequest $request, Plan $plan): RedirectResponse
    {
        $settings = $plan->readerSettings ?? new PlanReaderSettings(['plan_id' => $plan->id]);
        $settings->fill($request->validated());
        $settings->save();

        return redirect()
            ->route('admin.plans.edit', $plan)
            ->with('status', 'Reader settings updated successfully.');
    }
}

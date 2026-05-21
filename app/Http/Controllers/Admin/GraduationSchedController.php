<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GradSchedRequest;
use App\Models\GraduationSchedule;
use Illuminate\Support\Facades\Cache;

class GraduationSchedController extends Controller
{
    public function index()
    {
        $schedules = Cache::remember('graduation_schedules', 300, function () {
            return GraduationSchedule::orderBy('start_date', 'asc')->get();
        });

        return response()->json($schedules);
    }

    // Admin: Store new event/notice
    public function store(GradSchedRequest $request)
    {
        $validated = $request->validated();
        $schedule = GraduationSchedule::create($validated);
        return response()->json($schedule, 201);
    }

    // Admin: Update event/notice
    public function update(GradSchedRequest $request, GraduationSchedule $graduationSchedule)
    {
        $validated = $request->validated();
        $graduationSchedule->update($validated);
        return response()->json($graduationSchedule);
    }

    // Admin: Delete event
    public function destroy(GraduationSchedule $graduationSchedule)
    {
        $graduationSchedule->delete();
        return response()->json(['message' => 'Event deleted successfully']);
    }
}

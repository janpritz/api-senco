<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Queue;
use App\Models\Student;
use App\Services\Queue\QueueService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    public function handleAction(Request $request, $action)
    {
        return DB::transaction(function () use ($action) {

            // --- 1. START AT CREATIVE ---
            if ($action === 'next_creative') {
                $currentCreative = Queue::where('status', 'creative')
                    ->orderBy('updated_at', 'asc')
                    ->first();

                if ($currentCreative) {
                    $currentCreative->update([
                        'status' => 'toga',
                        'updated_at' => now()
                    ]);
                }

                $next = Queue::where('status', 'waiting')
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($next) {
                    $next->update([
                        'status' => 'creative',
                        'called_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // --- 2. FINISH AT TOGA ---
            if ($action === 'next_toga') {
                $currentToga = Queue::where('status', 'toga')
                    ->orderBy('updated_at', 'asc')
                    ->first();

                if ($currentToga) {
                    $currentToga->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // --- 3. UNDO CREATIVE (Move back to Waiting) ---
            if ($action === 'undo_creative') {
                // Find the most recent person moved to Creative
                $latestCreative = Queue::where('status', 'creative')
                    ->orderBy('updated_at', 'desc')
                    ->first();

                if ($latestCreative) {
                    $latestCreative->update([
                        'status' => 'waiting',
                        'called_at' => null,
                        'updated_at' => now()
                    ]);
                }
            }

            // --- 4. UNDO TOGA (Move back to Creative) ---
            if ($action === 'undo_toga') {
                // Find the most recent person moved to Toga
                $latestToga = Queue::where('status', 'toga')
                    ->orderBy('updated_at', 'desc')
                    ->first();

                if ($latestToga) {
                    $latestToga->update([
                        'status' => 'creative',
                        'updated_at' => now()
                    ]);
                } else {
                    // If Toga is empty, check if someone was JUST completed
                    $latestCompleted = Queue::where('status', 'completed')
                        ->orderBy('completed_at', 'desc')
                        ->first();

                    if ($latestCompleted) {
                        $latestCompleted->update([
                            'status' => 'toga',
                            'completed_at' => null,
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            return response()->json(['success' => true]);
        });
    }

    public function prioritize(Request $request, $id)
    {
        $service = new QueueService();
        $student = $service->prioritizeStudent($id);

        // Broadcast the update so the TV and Staff Controller refresh immediately
        // event(new \App\Events\QueueUpdated()); 

        return response()->json([
            'message' => "{$student->name} has been moved to priority.",
            'data' => $student
        ]);
    }
    public function getStatus()
    {
        $togaActive = Queue::where('status', 'toga')
            ->orderBy('called_at', 'asc') // OLDEST FIRST = ACTIVE
            ->orderBy('created_at', 'asc') // fallback safety
            ->first();

        return response()->json([
            'active_sessions' => Queue::whereIn('status', ['toga', 'creative'])
                ->orderBy('created_at', 'asc')
                ->get(),

            'upcoming' => Queue::where('status', 'waiting')
                ->orderBy('priority_group', 'asc')
                ->orderBy('created_at', 'asc')
                ->get(),

            'total_waiting' => Queue::where('status', 'waiting')->count(),

            // ✅ FIXED SOURCE OF TRUTH
            'toga_active' => $togaActive,

            'toga_queue' => Queue::where('status', 'toga')
                ->where('id', '!=', optional($togaActive)->id)
                ->orderBy('called_at', 'asc')
                ->get(),

            'recent_completed' => Queue::where('status', 'completed')
                ->orderBy('completed_at', 'desc')
                ->take(10)
                ->get(),
        ]);
    }

    /**
     * Attendance: Search student and add to waiting queue
     */
    public function register(Request $request)
    {
        $query = $request->input('student_id') ?? $request->input('query');

        $student = Student::where(function ($q) use ($query) {
            $q->where('student_id', $query)
                ->orWhere('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%");
        })->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found in masterlist'], 404);
        }

        // Check if already in an active part of the queue
        $exists = Queue::where('student_id', $student->student_id)
            ->whereIn('status', ['waiting', 'toga', 'creative'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Student is already in the queue'], 400);
        }

        $entry = Queue::create([
            'student_id'   => $student->student_id,
            'name'         => $student->full_name, // Ensure this accessor exists in Student model
            'status'       => 'waiting',
            'processed_by' => Auth::id()
        ]);

        return response()->json([
            'message' => 'Added to queue successfully',
            'data'    => $entry
        ]);
    }

    /**
     * Control: Move the queue forward (The State Machine)
     */
    public function triggerNext(Request $request)
    {
        return DB::transaction(function () {
            // 1. CLEAR CREATIVE BOOTH (Student is done)
            $inCreative = Queue::where('status', 'creative')->first();
            if ($inCreative) {
                $inCreative->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
            }

            // 2. MOVE TOGA TO CREATIVE (The Shift)
            $inToga = Queue::where('status', 'toga')->first();
            if ($inToga) {
                $inToga->update(['status' => 'creative']);
            }

            // 3. FILL THE NOW-EMPTY TOGA (The Fill)
            // We pull the next waiting student immediately
            $next = Queue::where('status', 'waiting')
                ->orderBy('priority_group', 'asc') // Use your priority logic
                ->orderBy('created_at', 'asc')
                ->first();

            if ($next) {
                $next->update([
                    'status' => 'toga',
                    'called_at' => now()
                ]);
            }

            return response()->json([
                'status' => 'success',
                'rotated' => !!$inToga,
                'called' => !!$next
            ]);
        });
    }

    /**
     * Control: Reverse the queue (The Undo Machine)
     */
    public function triggerBack(Request $request)
    {
        $serving = Queue::whereIn('status', ['toga', 'creative'])->first();

        // If no one is currently being served, pull the last completed person back
        if (!$serving) {
            $lastCompleted = Queue::where('status', 'completed')
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($lastCompleted) {
                $lastCompleted->update(['status' => 'creative']);
                return response()->json(['status' => 'recalled_to_creative']);
            }
            return response()->json(['message' => 'Nothing to undo'], 400);
        }

        // If in Creative -> Back to Toga
        if ($serving->status === 'creative') {
            $serving->update(['status' => 'toga']);
            return response()->json(['status' => 'returned_to_toga']);
        }

        // If in Toga -> Back to Waiting
        if ($serving->status === 'toga') {
            $serving->update(['status' => 'waiting']);
            return response()->json(['status' => 'returned_to_waiting']);
        }
    }

    /**
     * Force Complete (Manual override)
     */
    public function complete()
    {
        Queue::whereIn('status', ['toga', 'creative'])->update(['status' => 'completed']);
        return response()->json(['success' => true]);
    }
}

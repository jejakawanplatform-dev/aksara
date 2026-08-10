<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WaliMuridController extends Controller
{
    public function index(): Response
    {
        $parent = Auth::user();

        abort_unless($parent?->isParent(), 403);

        $childIds = DB::table('parent_students')
            ->where('parent_id', $parent->id)
            ->pluck('student_id');

        $children = User::whereIn('id', $childIds)->get();

        $childData = $children->map(function (User $child) {
            $attendances = $child->attendances;
            $totalAttendance = $attendances->count();
            $hadirCount = $attendances->where('status', AttendanceStatus::Present)->count();
            $pctHadir = $totalAttendance > 0 ? (int) round(($hadirCount / $totalAttendance) * 100) : 0;

            $attempts = $child->quizAttempts;
            $avgScore = $attempts->count() > 0 ? (int) round($attempts->avg('score')) : null;

            $status = $pctHadir >= 80 && ($avgScore ?? 0) >= 70
                ? 'Baik'
                : ($pctHadir >= 60 ? 'Perlu Perhatian' : 'Perlu Tindakan');

            return [
                'id' => $child->id,
                'name' => $child->name,
                'initial' => mb_strtoupper(mb_substr($child->name, 0, 1)),
                'totalAttendance' => $totalAttendance,
                'hadirCount' => $hadirCount,
                'pctHadir' => $pctHadir,
                'quizCount' => $attempts->count(),
                'avgScore' => $avgScore,
                'status' => $status,
            ];
        })->values();

        return Inertia::render('Dashboard/WaliMurid', [
            'parentName' => $parent->name,
            'childData' => $childData,
        ]);
    }
}

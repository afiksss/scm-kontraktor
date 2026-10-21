<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($s = trim((string) $request->query('q'))) {
            $query->where('description', 'like', "%{$s}%");
        }
        foreach (['module', 'user_id'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->query($f));
            }
        }

        return view('activity.index', [
            'logs' => $query->latest('id')->paginate(15)->withQueryString(),
            'modules' => ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module'),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}

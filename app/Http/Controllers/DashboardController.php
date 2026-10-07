<?php

namespace App\Http\Controllers;

use App\Contracts\SecurityDataProvider;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private SecurityDataProvider $data) {}

    public function index()
    {
        return view('dashboard');
    }

    public function stats(Request $request)
    {
        return response()->json([
            'summary'  => $this->data->summary(),
            'timeline' => $this->data->timeline(),
            'severity' => $this->data->bySeverity(),
            'attacks'  => $this->data->topAttackTypes(),
            'alerts'   => $this->data->recentAlerts($request->only(['severity', 'status'])),
        ]);
    }
        public function vulnerabilities(Request $request)
    {
        return response()->json([
            'summary' => $this->data->vulnSummary(),
            'items'   => $this->data->vulnerabilities($request->only(['severity'])),
        ]);
    }
}
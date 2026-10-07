<?php

namespace App\Http\Controllers;

use App\Services\AgentService;

class AgentController extends Controller
{
    public function show(string $name)
    {
        return view('agent', ['name' => $name]);
    }

    public function data(string $name, AgentService $service)
    {
        return response()->json($service->overview($name));
    }
}
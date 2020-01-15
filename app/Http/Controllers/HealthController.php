<?php

namespace App\Http\Controllers;

class HealthController extends Controller
{
    public function health()
    {
        return response()->json(["status" => "ok"]);
    }
}

<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class MinutesController extends Controller
{
    public function index()
    {
        return Inertia::render('Minutes/Index', [
            'minutes' => [], // Placeholder for future implementation
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index()
    {
        return Inertia::render('reports/Index', [
            'message' => 'Módulo de reportes en desarrollo',
        ]);
    }
}

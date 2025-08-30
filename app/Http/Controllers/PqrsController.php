<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PqrsController extends Controller
{
    public function index()
    {
        return Inertia::render('Pqrs/Index', [
            'pqrs' => [], // Placeholder for future implementation
        ]);
    }
}
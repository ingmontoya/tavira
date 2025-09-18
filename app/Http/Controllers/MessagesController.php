<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class MessagesController extends Controller
{
    public function index()
    {
        return Inertia::render('Messages/Index', [
            'messages' => [], // Placeholder for future implementation
        ]);
    }
}

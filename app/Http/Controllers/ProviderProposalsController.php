<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ProviderProposalsController extends Controller
{
    public function index()
    {
        return Inertia::render('ProviderProposals/Index', [
            'proposals' => [], // Placeholder for future implementation
        ]);
    }
}

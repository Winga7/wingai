<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\IaPersonalization;

class IaPersonalizationController extends Controller
{
    public function index()
    {
        $personalization = auth()->user()->iaPersonalization ?? new IaPersonalization();

        return Inertia::render('Ia/Personalization', [
            'personalization' => $personalization
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'identity' => 'required|string|max:1000',
            'behavior' => 'required|string|max:1000',
            'custom_commands' => 'array',
            'custom_commands.*.name' => 'required|string|max:100',
            'custom_commands.*.prompt' => 'required|string|max:1000'
        ]);

        $personalization = auth()->user()->iaPersonalization()->updateOrCreate(
            [],
            $validated
        );

        return redirect()->back()->with('success', 'Personnalisation sauvegardée avec succès');
    }
}

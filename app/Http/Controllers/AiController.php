<?php

namespace App\Http\Controllers;

use App\Services\AiCopilotService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function index()
    {
        return view('ai.index', [
            'providers' => AiCopilotService::providers(),
            'active' => AiCopilotService::configuredProvider(),
            'history' => AiCopilotService::history(auth()->id()),
        ]);
    }

    public function ask(Request $request)
    {
        $validated = $request->validate(['question' => 'required|string|max:1000']);
        try {
            $result = AiCopilotService::ask(auth()->id(), $validated['question']);
        } catch (\DomainException|\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        return back()->with('ai_answer', $result);
    }
}

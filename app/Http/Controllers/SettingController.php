<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('key')->get()->groupBy(function ($s) {
            return explode('.', $s->key)[0];
        });
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        foreach ($request->input('settings', []) as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value, 'updated_by' => auth()->id()]);
        }
        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}

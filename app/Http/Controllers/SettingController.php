<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditService;
use App\Support\SettingCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        $stored = Setting::query()->get()->keyBy('key');
        $rows = collect();
        foreach (SettingCatalog::all() as $key => $meta) {
            $row = $stored->get($key);
            $rows->push(['key' => $key, 'meta' => $meta, 'value' => $row?->value ?? $meta['default'], 'exists' => (bool) $row]);
        }
        foreach ($stored as $key => $row) {
            if (! array_key_exists($key, SettingCatalog::all())) {
                $rows->push(['key' => $key, 'meta' => SettingCatalog::get($key), 'value' => $row->value, 'exists' => true]);
            }
        }
        $settings = $rows->groupBy(fn ($row) => $row['meta']['group']);
        $developerLabels = auth()->user()->isSuperAdmin() && filter_var(Setting::get('developer_labels_enabled', false), FILTER_VALIDATE_BOOLEAN);
        return view('settings.index', compact('settings', 'developerLabels'));
    }

    public function update(Request $request)
    {
        $incoming = $request->input('settings', []);
        foreach ($incoming as $key => $value) {
            $meta = SettingCatalog::get($key);
            if (($meta['group'] ?? null) === 'Advanced' && ! auth()->user()->isSuperAdmin()) {
                continue;
            }
            if (($meta['sensitive'] ?? false) && ($value === null || $value === '')) {
                continue;
            }
            $rule = $meta['validation'] ?? 'nullable|string|max:255';
            $validator = Validator::make(
                ['value' => $value],
                ['value' => $rule],
                [],
                ['value' => $meta['label']]
            );
            $validated = $validator->validate();
            $setting = Setting::where('key', $key)->first();
            $oldValue = $setting?->value;
            $setting = Setting::updateOrCreate(['key' => $key], ['value' => $validated['value'], 'type' => $meta['type'], 'updated_by' => auth()->id()]);
            $redacted = $meta['sensitive'] ? '[REDACTED]' : null;
            AuditService::log('UPDATE', 'SETTING', $setting->id, Setting::class, ['key' => $key, 'label' => $meta['label'], 'value' => $redacted ?? $oldValue], ['key' => $key, 'label' => $meta['label'], 'value' => $redacted ?? $setting->value]);
        }
        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}

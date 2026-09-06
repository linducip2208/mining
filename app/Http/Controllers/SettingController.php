<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditService;
use App\Support\SettingCatalog;
use App\Support\SettingReferenceResolver;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $catalog = SettingCatalog::all();
        $stored = Setting::query()->where('scope_type', 'GLOBAL')->whereNull('scope_id')->get()->keyBy('key');
        $rows = collect();
        foreach ($catalog as $key => $meta) {
            $row = $stored->get($key);
            $rows->push(['key' => $key, 'meta' => $meta, 'value' => $row?->value ?? $meta['default'], 'exists' => (bool) $row]);
        }
        foreach ($stored as $key => $row) {
            if (! array_key_exists($key, $catalog)) {
                $rows->push(['key' => $key, 'meta' => SettingCatalog::get($key), 'value' => $row->value, 'exists' => true]);
            }
        }
        $settings = $rows->groupBy(fn ($row) => $row['meta']['group']);
        $developerLabels = auth()->user()->isSuperAdmin()
            && filter_var(Setting::get('developer_labels_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $referenceOptions = [];
        foreach ($catalog as $key => $meta) {
            if (($meta['type'] ?? null) === 'model_select') {
                $referenceOptions[$key] = SettingReferenceResolver::options($meta);
            }
        }
        $activeGroup = $request->string('group')->toString() ?: $settings->keys()->first();

        return view('settings.index', compact('settings', 'developerLabels', 'referenceOptions', 'activeGroup'));
    }

    public function update(Request $request)
    {
        $incoming = $request->input('settings', []);
        $files = $request->file('settings', []);
        $keys = array_unique(array_merge(array_keys($incoming), array_keys($files)));

        foreach ($keys as $key) {
            $meta = SettingCatalog::get($key);
            if (! $this->canUpdate($meta)) {
                continue;
            }
            $value = $incoming[$key] ?? null;
            if (($meta['type'] ?? null) === 'multiselect') {
                $value = is_array($value) ? array_values(array_intersect(array_keys($meta['options'] ?? []), $value)) : [];
            }
            if (($meta['type'] ?? null) === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }
            if (($meta['type'] ?? null) === 'model_select' && filled($value) && ! SettingReferenceResolver::valid($key, $value)) {
                return back()->withErrors([$key => $meta['label'].' tidak valid.']);
            }

            $uploaded = $files[$key] ?? null;
            if ($uploaded instanceof UploadedFile) {
                Validator::make(['file' => $uploaded], ['file' => 'required|file|mimes:png,jpg,jpeg,webp,ico|max:5120'], [], ['file' => $meta['label']])->validate();
                $oldFile = Setting::where('key', $key)->where('scope_type', 'GLOBAL')->whereNull('scope_id')->value('value');
                $value = $this->storeBrandingFile($uploaded);
                $this->deleteStoredFile($oldFile);
            }
            if (($meta['sensitive'] ?? false) && ($value === null || $value === '')) {
                continue;
            }

            $validated = Validator::make(
                ['value' => $value],
                ['value' => $meta['validation'] ?? 'nullable|string|max:255'],
                [],
                ['value' => $meta['label']]
            )->validate();
            $value = is_array($validated['value']) ? json_encode($validated['value'], JSON_UNESCAPED_UNICODE) : $validated['value'];
            $setting = Setting::where('key', $key)->where('scope_type', 'GLOBAL')->whereNull('scope_id')->first();
            $oldValue = $setting?->value;
            $storedValue = $meta['sensitive'] && filled($value) ? Crypt::encryptString((string) $value) : $value;
            $setting = Setting::updateOrCreate(
                ['key' => $key, 'scope_type' => 'GLOBAL', 'scope_id' => null],
                ['value' => $storedValue, 'type' => $meta['type'], 'updated_by' => auth()->id()]
            );
            if ($oldValue !== (string) $value) {
                $redactedOld = $meta['sensitive'] ? '[REDACTED]' : $oldValue;
                $redactedNew = $meta['sensitive'] ? '[REDACTED]' : $value;
                AuditService::log('UPDATE', 'SETTING', $setting->id, Setting::class,
                    ['key' => $key, 'label' => $meta['label'], 'value' => $redactedOld],
                    ['key' => $key, 'label' => $meta['label'], 'value' => $redactedNew]);
            }
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function reset(string $key)
    {
        $meta = SettingCatalog::get($key);
        abort_unless($this->canUpdate($meta), 403);
        $this->resetValue($key);

        return back()->with('success', $meta['label'].' dikembalikan ke default.');
    }

    public function resetGroup(Request $request)
    {
        $group = $request->string('group')->toString();
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('setting.update'), 403);
        if (in_array($group, ['Branding', 'Keamanan', 'Integrasi', 'Advanced'], true) && ! auth()->user()->isSuperAdmin()) {
            abort(403);
        }
        foreach (collect(SettingCatalog::all())->filter(fn ($meta) => $meta['group'] === $group)->keys() as $key) {
            $this->resetValue($key);
        }

        return back()->with('success', 'Grup '.$group.' dikembalikan ke default.');
    }

    public function resetAll()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        foreach (Setting::query()->where('scope_type', 'GLOBAL')->whereNull('scope_id')->get() as $setting) {
            $this->resetValue($setting->key);
        }

        return back()->with('success', 'Semua pengaturan dikembalikan ke default.');
    }

    public function export()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $output = [];
        foreach (Setting::query()->where('scope_type', 'GLOBAL')->whereNull('scope_id')->get() as $setting) {
            $meta = SettingCatalog::get($setting->key);
            if (! $meta['sensitive']) {
                $output[$setting->key] = ['label' => $meta['label'], 'type' => $meta['type'], 'value' => $setting->value];
            }
        }

        return response()->json(['version' => 1, 'exported_at' => now()->toIso8601String(), 'settings' => $output])
            ->header('Content-Disposition', 'attachment; filename="mining-configuration.json"');
    }

    public function import(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $request->validate(['configuration' => 'required|file|mimes:json,txt|max:2048']);
        $payload = json_decode($request->file('configuration')->get(), true);
        if (! is_array($payload) || ! is_array($payload['settings'] ?? null)) {
            return back()->withErrors(['configuration' => 'File konfigurasi tidak valid.']);
        }
        $safe = [];
        foreach ($payload['settings'] as $key => $item) {
            if (! array_key_exists($key, SettingCatalog::all()) || SettingCatalog::get($key)['sensitive']) {
                continue;
            }
            $safe[$key] = $item['value'] ?? $item;
        }
        $request->merge(['settings' => $safe]);

        return $this->update($request);
    }

    private function resetValue(string $key): void
    {
        $setting = Setting::where('key', $key)->where('scope_type', 'GLOBAL')->whereNull('scope_id')->first();
        if (! $setting) {
            return;
        }
        $meta = SettingCatalog::get($key);
        $this->deleteStoredFile($setting->value);
        AuditService::log('RESET', 'SETTING', $setting->id, Setting::class,
            ['key' => $key, 'label' => $meta['label'], 'value' => $meta['sensitive'] ? '[REDACTED]' : $setting->value],
            ['key' => $key, 'label' => $meta['label'], 'value' => $meta['sensitive'] ? '[REDACTED]' : $meta['default']]);
        $setting->delete();
    }

    private function storeBrandingFile(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->extension());

        return $file->storeAs('branding', Str::lower(Str::random(32)).'.'.$extension, 'public');
    }

    private function deleteStoredFile(?string $value): void
    {
        if (is_string($value) && Str::startsWith($value, 'branding/') && Storage::disk('public')->exists($value)) {
            Storage::disk('public')->delete($value);
        }
    }

    private function canUpdate(array $meta): bool
    {
        $user = auth()->user();
        if ($user?->isSuperAdmin()) {
            return true;
        }

        return match ($meta['group'] ?? 'Advanced') {
            'Branding' => $user?->hasPermission('branding.update'),
            'Integrasi' => $user?->hasPermission('integration.setting.update'),
            'Keamanan' => $user?->hasPermission('security.setting.update'),
            'Printer & Perangkat' => $user?->hasPermission('printer.manage'),
            'Advanced' => false,
            default => $user?->hasPermission('setting.update'),
        };
    }
}

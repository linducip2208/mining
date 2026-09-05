<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Overtime;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $items = Attendance::with(['employee'])
            ->when($request->date, fn ($q) => $q->whereDate('date', $request->date), fn ($q) => $q->whereDate('date', today()))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('employee_id')->paginate(25)->withQueryString();

        return view('hr.attendance.index', [
            'items' => $items,
            'statuses' => ['PRESENT', 'LATE', 'ABSENT', 'LEAVE', 'SICK', 'HOLIDAY', 'OFF'],
            'date' => $request->date ?? today()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'check_in' => 'nullable',
            'check_out' => 'nullable',
            'status' => 'required|in:PRESENT,LATE,ABSENT,LEAVE,SICK,HOLIDAY,OFF',
            'notes' => 'nullable|max:500',
        ]);

        Attendance::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'date' => $validated['date']],
            $validated + ['source' => 'MANUAL', 'created_by' => auth()->id()]
        );

        return back()->with('success', 'Absensi tersimpan.');
    }

    /**
     * Fingerprint CSV import: employee_code,date,check_in,check_out
     */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $count = 0;
        $errors = [];

        DB::transaction(function () use ($handle, $header, &$count, &$errors) {
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, array_pad($row, count($header), null));
                $employee = Employee::where('code', trim($data['employee_code'] ?? ''))->first();
                if (!$employee) {
                    $errors[] = "Karyawan '{$data['employee_code']}' tidak ditemukan";
                    continue;
                }
                $checkIn = !empty($data['check_in']) ? \Carbon\Carbon::parse($data['check_in']) : null;
                $checkOut = !empty($data['check_out']) ? \Carbon\Carbon::parse($data['check_out']) : null;
                $late = 0;
                if ($checkIn && $employee->shift) {
                    $shiftStart = \Carbon\Carbon::parse($checkIn->toDateString() . ' ' . $employee->shift->start_time);
                    $late = max(0, $checkIn->diffInMinutes($shiftStart, false) * -1);
                }

                Attendance::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $checkIn?->toDateString() ?? $data['date']],
                    [
                        'check_in' => $checkIn?->format('H:i:s'),
                        'check_out' => $checkOut?->format('H:i:s'),
                        'status' => $late > 15 ? 'LATE' : 'PRESENT',
                        'late_minutes' => $late,
                        'source' => 'FINGERPRINT',
                    ]
                );
                $count++;
            }
        });
        fclose($handle);

        $msg = "Import selesai: {$count} baris.";
        if ($errors) {
            $msg .= ' Dilewati: ' . implode('; ', array_slice($errors, 0, 5));
        }
        return back()->with('success', $msg);
    }
}

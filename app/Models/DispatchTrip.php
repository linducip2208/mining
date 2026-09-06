<?php

namespace App\Models;

class DispatchTrip extends BaseModel
{
    protected $table = 'dispatch_trips';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
            'start_time' => 'datetime',
            'loading_start' => 'datetime',
            'loading_finish' => 'datetime',
            'dump_time' => 'datetime',
            'end_time' => 'datetime',
            'tonnage' => 'decimal:4',
        ];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function shift() { return $this->belongsTo(Shift::class); }
    public function truck() { return $this->belongsTo(Equipment::class, 'truck_id'); }
    public function driver() { return $this->belongsTo(Employee::class, 'driver_id'); }
    public function loader() { return $this->belongsTo(Equipment::class, 'loader_id'); }
    public function pit() { return $this->belongsTo(Pit::class); }
    public function loadingPoint() { return $this->belongsTo(LoadingPoint::class); }
    public function dumpingPoint() { return $this->belongsTo(DumpingPoint::class); }
    public function route() { return $this->belongsTo(HaulingRoute::class, 'hauling_route_id'); }
    public function ticket() { return $this->belongsTo(WeighbridgeTicket::class, 'weighbridge_ticket_id'); }

    protected function minutes(?string $from, ?string $to): ?float
    {
        if (!$from || !$to) {
            return null;
        }
        return round(\Carbon\Carbon::parse($from)->diffInMinutes(\Carbon\Carbon::parse($to), false), 1);
    }

    /** Cycle analytics in minutes (null when timestamps incomplete). */
    public function cycleStats(): array
    {
        $s = fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d H:i:s') : $v;
        return [
            'queue_min' => $this->minutes($s($this->start_time), $s($this->loading_start)),
            'loading_min' => $this->minutes($s($this->loading_start), $s($this->loading_finish)),
            'travel_loaded_min' => $this->minutes($s($this->loading_finish), $s($this->dump_time)),
            'travel_empty_min' => $this->minutes($s($this->dump_time), $s($this->end_time)),
            'cycle_min' => $this->minutes($s($this->start_time), $s($this->end_time)),
        ];
    }
}

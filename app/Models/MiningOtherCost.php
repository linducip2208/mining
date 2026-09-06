<?php

namespace App\Models;

class MiningOtherCost extends BaseModel
{
    protected $table = 'mining_other_costs';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function company() { return $this->belongsTo(Company::class); }
    public function site() { return $this->belongsTo(Site::class); }
    public function pit() { return $this->belongsTo(Pit::class); }
    public function journalEntry() { return $this->belongsTo(JournalEntry::class); }
}

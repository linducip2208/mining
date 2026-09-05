<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Invoice extends BaseModel
{
    protected $table = 'invoices';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    
    public function salesOrder() { return $this->belongsTo(SalesOrder::class); }
    public function paymentTerm() { return $this->belongsTo(PaymentTerm::class, 'payment_term_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function journalEntry() { return $this->belongsTo(JournalEntry::class); }

}

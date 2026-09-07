<?php

namespace App\Models;

class LetterRegister extends BaseModel
{
    protected $table = 'letter_registers';

    protected $guarded = ['id'];

    public const STATUSES = ['DRAFT', 'NUMBER_RESERVED', 'REVIEW', 'APPROVED', 'SIGNED', 'SENT', 'ARCHIVED', 'PUBLISHED', 'REJECTED', 'CANCELLED', 'VOID'];

    public const SIMPLE_STATUSES = ['DRAFT', 'PUBLISHED', 'ARCHIVED'];

    public const RECIPIENT_TYPES = ['CUSTOMER', 'SUPPLIER', 'EMPLOYEE', 'GOVERNMENT', 'INTERNAL_DEPARTMENT', 'OTHER'];

    public function type()
    {
        return $this->belongsTo(LetterType::class, 'letter_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function signedBy()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function related()
    {
        return $this->morphTo();
    }

    public function reservation()
    {
        return $this->hasOne(LetterNumberReservation::class, 'letter_register_id');
    }
}

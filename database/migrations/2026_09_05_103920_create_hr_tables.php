<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->foreignId('company_id')->constrained();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('position', 100)->nullable();
            $table->string('employment_type', 30)->default('PERMANENT')->comment('PERMANENT, CONTRACT, PROBATION, DAILY, OUTSOURCED');
            $table->string('npwp', 50)->nullable();
            $table->string('nik', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 50)->nullable();
            $table->decimal('basic_salary', 20, 2)->default(0);
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->date('join_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('ACTIVE')->comment('ACTIVE, INACTIVE, TERMINATED');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'status']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('source', 20)->default('MANUAL')->comment('MANUAL, FINGERPRINT, IMPORT');
            $table->string('status', 20)->default('PRESENT')->comment('PRESENT, LATE, ABSENT, LEAVE, SICK, HOLIDAY, OFF');
            $table->decimal('late_minutes', 8, 2)->default(0);
            $table->decimal('overtime_minutes', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'date']);
            $table->index('date');
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->comment('ANNUAL, SICK, UNPAID, MATERNITY, OTHER');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('days');
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, SUBMITTED, APPROVED, REJECTED, CANCELLED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('hours', 8, 2)->default(0);
            $table->string('reason', 255)->nullable();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['date', 'status']);
        });

        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('type', 20)->comment('EARNING, DEDUCTION, ALLOWANCE, BONUS, INCOME_TAX');
            $table->string('calc_method', 20)->default('FIXED')->comment('FIXED, FORMULA, PERCENT, INPUT');
            $table->text('formula')->nullable()->comment('expression with vars');
            $table->string('gl_account_code', 20)->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_payroll_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('operator', 10)->default('+')->comment('+, -');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained();
            $table->string('period', 7)->comment('YYYY-MM');
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, CALCULATED, APPROVED, POSTED, PAID, CANCELLED');
            $table->decimal('total_gross', 20, 2)->default(0);
            $table->decimal('total_deduction', 20, 2)->default(0);
            $table->decimal('total_net', 20, 2)->default(0);
            $table->unsignedInteger('employee_count')->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'period']);
        });

        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('basic_salary', 20, 2)->default(0);
            $table->json('components')->nullable()->comment('breakdown earning/deduction');
            $table->decimal('total_earning', 20, 2)->default(0);
            $table->decimal('total_deduction', 20, 2)->default(0);
            $table->decimal('net_salary', 20, 2)->default(0);
            $table->timestamps();
            $table->unique(['payroll_run_id', 'employee_id']);
        });

        Schema::create('operator_incentives', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('basis', 30)->comment('TONNAGE, SHIFT, ACTIVITY, EQUIPMENT');
            $table->string('ref_type', 30)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('rate', 20, 2)->default(0);
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('period', 7);
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, APPROVED, INCLUDED_IN_PAYROLL, CANCELLED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['period', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_incentives');
        Schema::dropIfExists('payroll_details');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('employee_payroll_rules');
        Schema::dropIfExists('payroll_components');
        Schema::dropIfExists('overtimes');
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('employees');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hse_reports', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20)->index()->comment('INCIDENT, NEAR_MISS, HAZARD');
            $table->string('location', 255)->nullable();
            $table->dateTime('occurred_at');
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('severity', 30)->default('LOW')->comment('configurable via setting hse.severity_levels');
            $table->text('description');
            $table->text('cause')->nullable();
            $table->text('immediate_action')->nullable();
            $table->text('root_cause')->nullable();
            $table->string('photo', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 20)->default('OPEN')->comment('OPEN, IN_PROGRESS, CLOSED, CANCELLED');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['kind', 'status', 'occurred_at']);
        });

        Schema::create('hse_corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hse_report_id')->constrained()->cascadeOnDelete();
            $table->text('action');
            $table->foreignId('responsible_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('OPEN')->comment('OPEN, DONE, OVERDUE, VERIFIED');
            $table->text('evidence')->nullable()->comment('closure evidence notes');
            $table->string('evidence_file', 255)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['status', 'due_date']);
        });

        Schema::create('hse_permits', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('work_type', 100);
            $table->string('location', 255)->nullable();
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->foreignId('requester_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained()->nullOnDelete();
            $table->text('precautions')->nullable();
            $table->string('status', 20)->default('DRAFT')->comment('DRAFT, APPROVED, ACTIVE, EXPIRED, CLOSED, REJECTED');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('hse_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 30)->comment('INSPECTION, TOOLBOX, TRAINING, PPE_CHECK');
            $table->string('topic', 255);
            $table->date('activity_date');
            $table->text('participants')->nullable();
            $table->text('result')->nullable();
            $table->string('attachment', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hse_activities');
        Schema::dropIfExists('hse_permits');
        Schema::dropIfExists('hse_corrective_actions');
        Schema::dropIfExists('hse_reports');
    }
};

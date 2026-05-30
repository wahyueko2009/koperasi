<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('application_number')->nullable()->unique()->after('id');
            $table->string('loan_type')->default('serbaguna')->after('member_id');
            $table->date('submission_date')->nullable()->after('loan_type');
            $table->string('repayment_method')->default('salary_cut')->after('tenor_months');
            $table->text('purpose')->nullable()->after('repayment_method');
            $table->string('applicant_name')->nullable()->after('purpose');
            $table->string('applicant_nik', 50)->nullable()->after('applicant_name');
            $table->string('company_unit')->nullable()->after('applicant_nik');
            $table->string('applicant_phone', 50)->nullable()->after('company_unit');
            $table->text('ktp_address')->nullable()->after('applicant_phone');
            $table->text('current_address')->nullable()->after('ktp_address');
            $table->string('payroll_account_number', 50)->nullable()->after('current_address');
            $table->string('family_member_name')->nullable()->after('payroll_account_number');
            $table->string('family_relationship')->nullable()->after('family_member_name');
            $table->unsignedTinyInteger('child_number')->nullable()->after('family_relationship');
            $table->string('education_level')->nullable()->after('child_number');
            $table->string('school_name')->nullable()->after('education_level');
            $table->text('school_address')->nullable()->after('school_name');
            $table->string('school_phone', 50)->nullable()->after('school_address');
            $table->string('hospital_name')->nullable()->after('school_phone');
            $table->text('hospital_address')->nullable()->after('hospital_name');
            $table->string('hospital_phone', 50)->nullable()->after('hospital_address');
            $table->string('collateral_type')->nullable()->after('hospital_phone');
            $table->decimal('collateral_value', 15, 2)->nullable()->after('collateral_type');
            $table->text('collateral_description')->nullable()->after('collateral_value');
            $table->string('application_status')->default('submitted')->after('status');
            $table->text('member_notes')->nullable()->after('notes');
            $table->text('verification_notes')->nullable()->after('member_notes');
            $table->text('approval_notes')->nullable()->after('verification_notes');
            $table->timestamp('verified_at')->nullable()->after('approval_notes');
            $table->timestamp('approved_at')->nullable()->after('verified_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });

        Schema::create('loan_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->restrictOnDelete();
            $table->string('document_type');
            $table->string('document_label');
            $table->string('status')->default('pending');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['loan_id', 'document_type']);
        });

        Schema::create('loan_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loans')->restrictOnDelete();
            $table->foreignId('official_id')->nullable()->constrained('officials')->nullOnDelete();
            $table->string('action');
            $table->text('notes')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->index(['loan_id', 'action']);
        });

        $loans = DB::table('loans')->get(['id', 'member_id', 'created_at']);
        foreach ($loans as $loan) {
            DB::table('loans')
                ->where('id', $loan->id)
                ->update([
                    'application_number' => 'LOAN-' . date('Y', strtotime((string) $loan->created_at)) . '-' . str_pad((string) $loan->id, 6, '0', STR_PAD_LEFT),
                    'submission_date' => $loan->created_at ? date('Y-m-d', strtotime((string) $loan->created_at)) : date('Y-m-d'),
                    'applicant_name' => DB::table('members')->where('id', $loan->member_id)->value('name'),
                    'applicant_nik' => DB::table('members')->where('id', $loan->member_id)->value('nik'),
                    'company_unit' => DB::table('members')->where('id', $loan->member_id)->value('company_unit'),
                    'applicant_phone' => DB::table('members')->where('id', $loan->member_id)->value('phone'),
                    'ktp_address' => DB::table('members')->where('id', $loan->member_id)->value('address'),
                    'current_address' => DB::table('members')->where('id', $loan->member_id)->value('address'),
                    'payroll_account_number' => DB::table('members')->where('id', $loan->member_id)->value('account_number'),
                    'application_status' => match (DB::table('loans')->where('id', $loan->id)->value('status')) {
                        'approved' => 'approved',
                        'disbursed', 'active' => 'disbursed',
                        'completed' => 'completed',
                        default => 'submitted',
                    },
                ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_approvals');
        Schema::dropIfExists('loan_documents');

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'application_number',
                'loan_type',
                'submission_date',
                'repayment_method',
                'purpose',
                'applicant_name',
                'applicant_nik',
                'company_unit',
                'applicant_phone',
                'ktp_address',
                'current_address',
                'payroll_account_number',
                'family_member_name',
                'family_relationship',
                'child_number',
                'education_level',
                'school_name',
                'school_address',
                'school_phone',
                'hospital_name',
                'hospital_address',
                'hospital_phone',
                'collateral_type',
                'collateral_value',
                'collateral_description',
                'application_status',
                'member_notes',
                'verification_notes',
                'approval_notes',
                'verified_at',
                'approved_at',
                'rejected_at',
            ]);
        });
    }
};

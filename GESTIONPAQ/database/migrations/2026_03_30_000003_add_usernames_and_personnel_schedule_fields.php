<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('usuarios', 'username')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->string('username', 60)->nullable()->after('email');
            });

            Schema::table('usuarios', function (Blueprint $table): void {
                $table->unique('username');
            });
        }

        Schema::table('personas', function (Blueprint $table): void {
            if (! Schema::hasColumn('personas', 'employee_code')) {
                $table->string('employee_code', 40)->nullable()->after('email');
            }
            if (! Schema::hasColumn('personas', 'job_title')) {
                $table->string('job_title', 120)->nullable()->after('employee_code');
            }
            if (! Schema::hasColumn('personas', 'schedule_label')) {
                $table->string('schedule_label', 120)->nullable()->after('job_title');
            }
            if (! Schema::hasColumn('personas', 'work_days')) {
                $table->string('work_days', 120)->nullable()->after('schedule_label');
            }
            if (! Schema::hasColumn('personas', 'shift_start')) {
                $table->time('shift_start')->nullable()->after('work_days');
            }
            if (! Schema::hasColumn('personas', 'shift_end')) {
                $table->time('shift_end')->nullable()->after('shift_start');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table): void {
            $columns = ['employee_code', 'job_title', 'schedule_label', 'work_days', 'shift_start', 'shift_end'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('personas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('usuarios', 'username')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            });
        }
    }
};
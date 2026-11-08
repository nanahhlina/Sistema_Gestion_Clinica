<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vida_salud.sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('vida_salud.usuario');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('vida_salud.cache', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->text('value');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('vida_salud.cache_locks', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('owner');
            $table->bigInteger('expiration')->index();
        });

        Schema::create('vida_salud.jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->text('payload');
            $table->unsignedSmallInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('vida_salud.job_batches', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->text('failed_job_ids');
            $table->text('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('vida_salud.failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->text('payload');
            $table->text('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['failed_jobs', 'job_batches', 'jobs', 'cache_locks', 'cache', 'sessions'];

        foreach ($tables as $table) {
            if (DB::table('vida_salud.'.$table)->exists()) {
                throw new RuntimeException('No se eliminan tablas de infraestructura con datos. Vacielas de forma explicita antes de revertir.');
            }
        }

        foreach ($tables as $table) {
            Schema::drop('vida_salud.'.$table);
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(config('activity-log.table_name', 'activity_logs'), function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');

            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');

            $table->string('event')->nullable();
            $table->json('properties')->nullable();
            $table->json('context')->nullable();
            $table->string('template')->nullable();

            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();

            $table->index('log_name');
            $table->index('event');
            $table->index('batch_uuid');
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('activity-log.table_name', 'activity_logs'));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model');
            $table->string('field');
            $table->longText('old_value');
            $table->longText('new_value');
            $table->string('model_id');
            $table->string('user_id')->nullable();
            $table->dateTime('event_time');
            $table->timestamps();

            $table->index(['model', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};

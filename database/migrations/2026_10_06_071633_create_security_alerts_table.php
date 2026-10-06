<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('security_alerts', function (Blueprint $table) {
        $table->id();
        $table->string('source_ip');
        $table->string('destination_ip');
        $table->string('country', 2)->nullable();
        $table->string('attack_type');
        $table->enum('severity', ['low', 'medium', 'high', 'critical']);
        $table->enum('status', ['open', 'investigating', 'resolved', 'false_positive']);
        $table->string('source_system');
        $table->text('description')->nullable();
        $table->timestamp('detected_at');
        $table->timestamp('resolved_at')->nullable();
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_alerts');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_aktivitas_pesanan', function (Blueprint $table) {
            $table->id();

            // Aktivitas
            $table->string('aktivitas', 50);

            // Identitas pesanan
            $table->unsignedBigInteger('order_id');
            $table->string('order_code', 255);
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('machine_id')->nullable();

            // Informasi pembayaran dan status
            $table->decimal('total_price', 12, 2)->nullable();
            $table->string('payment_status', 50)->nullable();
            $table->string('status_sebelum', 50)->nullable();
            $table->string('status_sesudah', 50)->nullable();

            // Waktu
            $table->date('order_date')->nullable();
            $table->dateTime('waktu_aktivitas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas_pesanan');
    }
};

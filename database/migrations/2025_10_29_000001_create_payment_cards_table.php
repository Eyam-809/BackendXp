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
        Schema::create('payment_cards', function (Blueprint $table) {
            $table->id();

            // relación al usuario
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // tipo: 'debit'|'credit'
            if (Schema::getConnection()->getDoctrineSchemaManager()->tablesExist(['payment_cards'])) {
                // no-op para compatibilidad con Doctrine en entornos raros
            }
            $table->enum('type', ['debit', 'credit'])->default('debit');

            // datos mostrables
            $table->string('card_holder_name')->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('card_expiry', 10)->nullable(); // formato MM/YY o MM/YYYY

            // datos cifrados (se recomienda usar cast 'encrypted' en el modelo)
            $table->text('card_number')->nullable();
            $table->text('cvv')->nullable();

            // proveedor/token para integraciones con pasarelas (opcional)
            $table->string('provider')->nullable()->index();
            $table->string('provider_token')->nullable()->unique()->comment('Token/ID retornado por pasarela (si aplica)');

            // se puede marcar como método por defecto del usuario
            $table->boolean('is_default')->default(false)->index();

            // auditoría
            $table->timestamps();
            $table->softDeletes();

            // índices adicionales
            $table->index(['user_id', 'is_default'], 'cards_user_default_idx');
            $table->index(['user_id', 'card_last4'], 'cards_user_last4_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_cards', function (Blueprint $table) {
            // remover índices/foreign keys antes de eliminar
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            // intentar eliminar FK si existe (silencioso)
            try {
                $table->dropForeign(['user_id']);
            } catch (\Throwable $e) {
            }
        });

        Schema::dropIfExists('payment_cards');
    }
};
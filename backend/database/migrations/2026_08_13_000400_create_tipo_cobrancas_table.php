<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_cobrancas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cobranca_id')->unique()->constrained('cobrancas')->cascadeOnDelete();

            $table->string('codigo_barras', 44)->nullable();
            $table->string('linha_digitavel', 54)->nullable();

            $table->string('cartao_bandeira', 20)->nullable();
            $table->string('cartao_titular', 100)->nullable();
            $table->text('cartao_numero')->nullable();
            $table->char('cartao_ultimos_digitos', 4)->nullable();
            $table->char('cartao_validade', 7)->nullable();

            $table->enum('pix_tipo_chave', ['cpf', 'cnpj', 'email', 'telefone', 'aleatoria'])->nullable();
            $table->string('pix_chave', 77)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_cobrancas');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobrancas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contrato_id')->constrained('contratos')->restrictOnDelete();

            $table->date('competencia');
            $table->enum('tipo', ['boleto', 'cartao', 'pix']);
            $table->date('data_vencimento');

            $table->decimal('valor_original', 10, 2);
            $table->decimal('valor_multa', 10, 2)->default(0);
            $table->decimal('valor_juros', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2);
            $table->unsignedSmallInteger('dias_atraso')->default(0);

            $table->enum('situacao', ['aberta', 'paga'])->default('aberta');
            $table->dateTime('data_pagamento')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['contrato_id', 'competencia']);
            $table->index(['situacao', 'data_vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobrancas');
    }
};

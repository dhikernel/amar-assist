<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();

            $table->string('numero', 30)->unique();
            $table->enum('tipo', ['PF', 'PJ']);
            $table->unsignedTinyInteger('ciclo');
            $table->decimal('valor_mensal', 10, 2);
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->enum('situacao', ['ativo', 'suspenso', 'encerrado'])->default('ativo');

            $table->timestamps();
            $table->softDeletes();

            $table->index('situacao');
            $table->index(['cliente_id', 'situacao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};

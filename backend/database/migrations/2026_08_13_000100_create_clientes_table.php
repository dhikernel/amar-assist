<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            $table->string('nome', 150);
            $table->enum('tipo_pessoa', ['PF', 'PJ']);
            $table->string('cpf_cnpj', 14)->unique();

            $table->string('cep', 8);
            $table->string('logradouro', 150);
            $table->string('numero', 20);
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100);
            $table->string('cidade', 100);
            $table->char('uf', 2);

            $table->string('email', 150)->nullable();
            $table->string('telefone', 11);

            $table->enum('situacao', ['ativo', 'inativo'])->default('ativo');

            $table->timestamps();
            $table->softDeletes();

            $table->index('nome');
            $table->index('situacao');
            $table->index(['situacao', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};

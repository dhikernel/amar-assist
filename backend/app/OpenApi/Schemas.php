<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="User",
 *   type="object",
 *
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="nome", type="string", example="Administrador"),
 *   @OA\Property(property="email", type="string", format="email", example="admin@amarassist.com.br"),
 *   @OA\Property(property="criado_em", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *   schema="LoginResponse",
 *   type="object",
 *
 *   @OA\Property(property="token", type="string", example="1|abc123xyz"),
 *   @OA\Property(property="tipo", type="string", example="Bearer"),
 *   @OA\Property(property="expira_em", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="usuario", ref="#/components/schemas/User")
 * )
 *
 * @OA\Schema(
 *   schema="Cliente",
 *   type="object",
 *
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="nome", type="string", example="Joana Ribeiro"),
 *   @OA\Property(property="tipo_pessoa", type="string", enum={"PF","PJ"}, example="PF"),
 *   @OA\Property(property="tipo_pessoa_rotulo", type="string", example="Pessoa Física"),
 *   @OA\Property(property="cpf_cnpj", type="string", example="11144477735"),
 *   @OA\Property(property="cpf_cnpj_formatado", type="string", example="111.444.777-35"),
 *   @OA\Property(property="endereco", type="object",
 *     @OA\Property(property="cep", type="string", example="01310100"),
 *     @OA\Property(property="cep_formatado", type="string", example="01310-100"),
 *     @OA\Property(property="logradouro", type="string", example="Avenida Paulista"),
 *     @OA\Property(property="numero", type="string", example="1578"),
 *     @OA\Property(property="complemento", type="string", nullable=true, example="Conjunto 12"),
 *     @OA\Property(property="bairro", type="string", example="Bela Vista"),
 *     @OA\Property(property="cidade", type="string", example="São Paulo"),
 *     @OA\Property(property="uf", type="string", example="SP")
 *   ),
 *   @OA\Property(property="contato", type="object",
 *     @OA\Property(property="email", type="string", format="email", nullable=true),
 *     @OA\Property(property="telefone", type="string", example="11987654321"),
 *     @OA\Property(property="telefone_formatado", type="string", example="(11) 98765-4321")
 *   ),
 *   @OA\Property(property="situacao", type="string", enum={"ativo","inativo"}, example="ativo"),
 *   @OA\Property(property="situacao_rotulo", type="string", example="Ativo"),
 *   @OA\Property(property="criado_em", type="string", format="date-time"),
 *   @OA\Property(property="atualizado_em", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *   schema="Contrato",
 *   type="object",
 *
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="numero", type="string", example="CT-000123"),
 *   @OA\Property(property="tipo", type="string", enum={"PF","PJ"}, example="PF", description="Derivado do documento do cliente vinculado"),
 *   @OA\Property(property="tipo_rotulo", type="string", example="Pessoa Física"),
 *   @OA\Property(property="cliente", type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nome", type="string", example="Joana Ribeiro"),
 *     @OA\Property(property="cpf_cnpj_formatado", type="string", example="111.444.777-35")
 *   ),
 *   @OA\Property(property="ciclo", type="integer", minimum=1, maximum=31, example=31, description="Dia do mês pretendido para o vencimento"),
 *   @OA\Property(property="proximo_vencimento", type="string", format="date", example="2027-02-28", description="Ciclo resolvido para o mês corrente, limitado ao último dia disponível — ciclo 31 vence em 28/02, ou 29/02 em ano bissexto"),
 *   @OA\Property(property="valor_mensal", type="string", example="249.90"),
 *   @OA\Property(property="data_inicio", type="string", format="date", example="2027-01-05"),
 *   @OA\Property(property="data_fim", type="string", format="date", nullable=true),
 *   @OA\Property(property="situacao", type="string", enum={"ativo","suspenso","encerrado"}, example="ativo"),
 *   @OA\Property(property="situacao_rotulo", type="string", example="Ativo"),
 *   @OA\Property(property="criado_em", type="string", format="date-time"),
 *   @OA\Property(property="atualizado_em", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *   schema="Cobranca",
 *   type="object",
 *
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="competencia", type="string", example="2027-02", description="Mês de referência da fatura"),
 *   @OA\Property(property="tipo", type="string", enum={"boleto","cartao","pix"}, example="boleto"),
 *   @OA\Property(property="tipo_rotulo", type="string", example="Boleto"),
 *   @OA\Property(property="data_vencimento", type="string", format="date", example="2027-02-28", description="Resolvido pelo ciclo do contrato, conforme a diretiva (b)"),
 *   @OA\Property(property="dias_atraso", type="integer", example=10),
 *   @OA\Property(property="em_atraso", type="boolean", example=true),
 *   @OA\Property(property="valor_original", type="string", example="100.00"),
 *   @OA\Property(property="valor_multa", type="string", example="2.00", description="Percentual fixo sobre o principal, aplicado uma vez quando há atraso"),
 *   @OA\Property(property="valor_juros", type="string", example="10.00", description="Diretiva (c): 1% ao dia sobre o principal, por dia de atraso"),
 *   @OA\Property(property="valor_total", type="string", example="112.00", description="Recalculado a cada consulta enquanto aberta; congelado no valor pago após a quitação"),
 *   @OA\Property(property="situacao", type="string", enum={"aberta","paga"}, example="aberta"),
 *   @OA\Property(property="situacao_rotulo", type="string", example="Aberta"),
 *   @OA\Property(property="data_pagamento", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="contrato", type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="numero", type="string", example="CT-000123"),
 *     @OA\Property(property="cliente", type="object",
 *       @OA\Property(property="id", type="integer"),
 *       @OA\Property(property="nome", type="string", example="Joana Ribeiro")
 *     )
 *   ),
 *   @OA\Property(property="detalhe", type="object", description="Campos conforme o tipo: boleto devolve codigo_barras e linha_digitavel; cartao devolve bandeira, titular, ultimos_digitos e validade — nunca o número completo; pix devolve tipo_chave e chave"),
 *   @OA\Property(property="criado_em", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *   schema="ResumoCobranca",
 *   type="object",
 *
 *   @OA\Property(property="total_em_aberto", type="integer", example=16),
 *   @OA\Property(property="total_em_atraso", type="integer", example=11),
 *   @OA\Property(property="total_pagas", type="integer", example=8),
 *   @OA\Property(property="valor_em_aberto", type="string", example="33831.46"),
 *   @OA\Property(property="valor_recebido", type="string", example="16915.73"),
 *   @OA\Property(property="atualizado_em", type="string", format="date-time", description="Momento em que o resumo foi calculado. Fica em cache no Redis por 5 minutos, e o cache é descartado a cada cobrança criada, paga ou removida.")
 * )
 *
 * @OA\Schema(
 *   schema="MessageResponse",
 *   type="object",
 *
 *   @OA\Property(property="message", type="string", example="Operação realizada com sucesso.")
 * )
 *
 * @OA\Schema(
 *   schema="ValidationError",
 *   type="object",
 *
 *   @OA\Property(property="message", type="string", example="The given data was invalid."),
 *   @OA\Property(
 *     property="errors",
 *     type="object",
 *
 *     @OA\AdditionalProperties(
 *       type="array",
 *
 *       @OA\Items(type="string", example="O campo é obrigatório.")
 *     )
 *   )
 * )
 */
class Schemas {}

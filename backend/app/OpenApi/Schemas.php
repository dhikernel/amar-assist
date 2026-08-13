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

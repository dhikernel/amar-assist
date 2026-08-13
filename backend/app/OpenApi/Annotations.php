<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   title="Amar Assist - Sistema de Cobrança",
 *   version="1.0.0",
 *   description="Documentação da API do sistema de cobrança."
 * )
 *
 * @OA\Server(
 *   url=L5_SWAGGER_CONST_HOST,
 *   description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearer",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="Token",
 *   description="Token Sanctum. Faça login em POST /api/login e informe o token aqui."
 * )
 *
 * @OA\Tag(name="Auth", description="Autenticação")
 * @OA\Tag(name="Clientes", description="Cadastro de clientes")
 *
 * ------- AUTH -------
 *
 * @OA\Post(
 *   path="/api/login", tags={"Auth"}, summary="Login",
 *   description="Autentica e emite um token de acesso. Bloqueia após 5 tentativas malsucedidas, por e-mail + IP.",
 *
 *   @OA\RequestBody(required=true,
 *
 *     @OA\MediaType(mediaType="application/json",
 *
 *       @OA\Schema(example={"email": "admin@amarassist.com.br", "password": "Amar@2026"})
 *     )
 *   ),
 *
 *   @OA\Response(response=200, description="Login realizado com sucesso",
 *
 *     @OA\JsonContent(ref="#/components/schemas/LoginResponse")
 *   ),
 *
 *   @OA\Response(response=422, description="Credenciais inválidas",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=429, description="Muitas tentativas — bloqueado temporariamente",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   )
 * )
 *
 * @OA\Get(
 *   path="/api/me", tags={"Auth"}, summary="Usuário autenticado",
 *   security={{"bearer":{}}},
 *
 *   @OA\Response(response=200, description="Dados do usuário autenticado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/User")
 *   ),
 *
 *   @OA\Response(response=401, description="Não autenticado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/MessageResponse")
 *   )
 * )
 *
 * @OA\Post(
 *   path="/api/logout", tags={"Auth"}, summary="Logout",
 *   description="Revoga apenas o token da requisição atual — os demais dispositivos permanecem autenticados.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Response(response=200, description="Sessão encerrada",
 *
 *     @OA\JsonContent(ref="#/components/schemas/MessageResponse")
 *   ),
 *
 *   @OA\Response(response=401, description="Não autenticado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/MessageResponse")
 *   )
 * )
 *
 * ------- CLIENTES -------
 *
 * @OA\Get(
 *   path="/api/clientes", tags={"Clientes"}, summary="Lista clientes",
 *   description="Consulta por nome, situação e/ou CPF/CNPJ, com paginação.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="filter[nome]", in="query", description="Busca parcial pelo nome", @OA\Schema(type="string")),
 *   @OA\Parameter(name="filter[situacao]", in="query", @OA\Schema(type="string", enum={"ativo","inativo"})),
 *   @OA\Parameter(name="filter[cpf_cnpj]", in="query", description="Com ou sem máscara, inclusive parcial", @OA\Schema(type="string")),
 *   @OA\Parameter(name="sort", in="query", description="Ex.: nome ou -nome", @OA\Schema(type="string")),
 *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", maximum=100)),
 *
 *   @OA\Response(response=200, description="Lista paginada de clientes",
 *
 *     @OA\JsonContent(type="object",
 *       @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Cliente")),
 *       @OA\Property(property="current_page", type="integer"),
 *       @OA\Property(property="per_page", type="integer"),
 *       @OA\Property(property="total", type="integer")
 *     )
 *   ),
 *
 *   @OA\Response(response=400, description="Filtro ou ordenação não permitidos"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Post(
 *   path="/api/clientes", tags={"Clientes"}, summary="Cadastra um cliente",
 *   description="O tipo de pessoa (PF/PJ) é derivado da quantidade de dígitos do CPF/CNPJ, não é aceito do cliente.",
 *   security={{"bearer":{}}},
 *
 *   @OA\RequestBody(required=true,
 *
 *     @OA\MediaType(mediaType="application/json",
 *
 *       @OA\Schema(example={
 *         "nome": "Joana Ribeiro",
 *         "cpf_cnpj": "111.444.777-35",
 *         "cep": "01310-100",
 *         "logradouro": "Avenida Paulista",
 *         "numero": "1578",
 *         "complemento": "Conjunto 12",
 *         "bairro": "Bela Vista",
 *         "cidade": "São Paulo",
 *         "uf": "SP",
 *         "email": "joana@exemplo.com.br",
 *         "telefone": "(11) 98765-4321"
 *       })
 *     )
 *   ),
 *
 *   @OA\Response(response=201, description="Cliente cadastrado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Cliente")
 *   ),
 *
 *   @OA\Response(response=422, description="Dados inválidos ou documento já cadastrado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Get(
 *   path="/api/clientes/{id}", tags={"Clientes"}, summary="Exibe um cliente",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Cliente encontrado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Cliente")
 *   ),
 *
 *   @OA\Response(response=404, description="Cliente não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Put(
 *   path="/api/clientes/{id}", tags={"Clientes"}, summary="Atualiza um cliente",
 *   description="Aceita atualização parcial — envie apenas os campos que deseja alterar.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\RequestBody(required=true,
 *
 *     @OA\MediaType(mediaType="application/json",
 *
 *       @OA\Schema(example={"nome": "Novo Nome"})
 *     )
 *   ),
 *
 *   @OA\Response(response=200, description="Cliente atualizado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Cliente")
 *   ),
 *
 *   @OA\Response(response=422, description="Dados inválidos ou documento já cadastrado para outro cliente",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=404, description="Cliente não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Get(
 *   path="/api/clientes/{id}/check-delete", tags={"Clientes"}, summary="Verifica vínculos do cliente",
 *   description="Devolve quantos contratos o cliente possui. Consultado pela tela antes de oferecer a desativação, para não deixar o usuário tentar uma ação que será recusada.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Quantidade de contratos vinculados",
 *
 *     @OA\JsonContent(type="object",
 *       @OA\Property(property="count", type="integer", example=2),
 *       @OA\Property(property="haveRelationship", type="boolean", example=true)
 *     )
 *   ),
 *
 *   @OA\Response(response=404, description="Cliente não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Patch(
 *   path="/api/clientes/{id}/inactive", tags={"Clientes"}, summary="Desativa um cliente",
 *   description="Aplica a diretiva (a) do enunciado: cliente que possui contrato não pode ser desativado. A situação só muda por este endpoint e pelo /active — o cadastro e a atualização não aceitam o campo situacao.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Cliente desativado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Cliente")
 *   ),
 *
 *   @OA\Response(response=422, description="Cliente possui contrato vinculado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=404, description="Cliente não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Patch(
 *   path="/api/clientes/{id}/active", tags={"Clientes"}, summary="Reativa um cliente",
 *   description="Reativação não depende de contrato — a restrição existe apenas para desativar.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Cliente reativado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Cliente")
 *   ),
 *
 *   @OA\Response(response=404, description="Cliente não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Delete(
 *   path="/api/clientes", tags={"Clientes"}, summary="Remove um cliente",
 *   description="Remoção lógica (soft delete) — o registro é preservado para histórico de cobrança.",
 *   security={{"bearer":{}}},
 *
 *   @OA\RequestBody(required=true,
 *
 *     @OA\MediaType(mediaType="application/json",
 *
 *       @OA\Schema(example={"uuid": 1})
 *     )
 *   ),
 *
 *   @OA\Response(response=204, description="Cliente removido"),
 *   @OA\Response(response=404, description="Cliente não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 */
class Annotations {}

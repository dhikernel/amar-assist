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
 * @OA\Tag(name="Contratos", description="Contratos e ciclo de vencimento")
 * @OA\Tag(name="Cobranças", description="Faturas, acréscimo por atraso e pagamento")
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
 *
 * ------- CONTRATOS -------
 *
 * @OA\Get(
 *   path="/api/contratos", tags={"Contratos"}, summary="Lista contratos",
 *   description="Cada item traz o proximo_vencimento já resolvido pela diretiva (b): o ciclo é limitado ao último dia do mês corrente.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="filter[numero]", in="query", description="Busca parcial pelo número", @OA\Schema(type="string")),
 *   @OA\Parameter(name="filter[situacao]", in="query", @OA\Schema(type="string", enum={"ativo","suspenso","encerrado"})),
 *   @OA\Parameter(name="filter[tipo]", in="query", @OA\Schema(type="string", enum={"PF","PJ"})),
 *   @OA\Parameter(name="filter[cliente_id]", in="query", @OA\Schema(type="integer")),
 *   @OA\Parameter(name="filter[cliente]", in="query", description="Busca parcial pelo nome do cliente", @OA\Schema(type="string")),
 *   @OA\Parameter(name="sort", in="query", description="numero, data_inicio, valor_mensal, situacao, created_at (prefixe com - para descendente)", @OA\Schema(type="string")),
 *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Lista paginada de contratos",
 *
 *     @OA\JsonContent(type="object",
 *       @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Contrato")),
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
 *   path="/api/contratos", tags={"Contratos"}, summary="Cadastra um contrato",
 *   description="O tipo (PF/PJ) é derivado do documento do cliente e não é aceito na requisição. O ciclo é o dia do mês pretendido para o vencimento (1 a 31); o dia real é resolvido a cada mês. Cliente inativo não pode receber contrato.",
 *   security={{"bearer":{}}},
 *
 *   @OA\RequestBody(required=true,
 *
 *     @OA\MediaType(mediaType="application/json",
 *
 *       @OA\Schema(example={
 *         "cliente_id": 1,
 *         "numero": "CT-000123",
 *         "ciclo": 31,
 *         "valor_mensal": 249.90,
 *         "data_inicio": "2027-01-05"
 *       })
 *     )
 *   ),
 *
 *   @OA\Response(response=201, description="Contrato cadastrado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Contrato")
 *   ),
 *
 *   @OA\Response(response=422, description="Dados inválidos, número duplicado ou cliente inativo",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Get(
 *   path="/api/contratos/{id}", tags={"Contratos"}, summary="Exibe um contrato",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Contrato encontrado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Contrato")
 *   ),
 *
 *   @OA\Response(response=404, description="Contrato não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Put(
 *   path="/api/contratos/{id}", tags={"Contratos"}, summary="Atualiza um contrato",
 *   description="Atualização parcial. Trocar o cliente recalcula o tipo. A situação não é aceita aqui — use os endpoints de suspender, reativar ou encerrar.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\RequestBody(required=true,
 *
 *     @OA\MediaType(mediaType="application/json",
 *
 *       @OA\Schema(example={"ciclo": 20, "valor_mensal": 299.90})
 *     )
 *   ),
 *
 *   @OA\Response(response=200, description="Contrato atualizado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Contrato")
 *   ),
 *
 *   @OA\Response(response=422, description="Dados inválidos ou número já usado por outro contrato",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=404, description="Contrato não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Patch(
 *   path="/api/contratos/{id}/suspender", tags={"Contratos"}, summary="Suspende um contrato",
 *   description="Contrato encerrado não pode ser suspenso — encerrado é estado terminal.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Contrato suspenso",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Contrato")
 *   ),
 *
 *   @OA\Response(response=422, description="Transição não permitida",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=404, description="Contrato não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Patch(
 *   path="/api/contratos/{id}/reativar", tags={"Contratos"}, summary="Reativa um contrato",
 *   description="Volta o contrato para ativo. Contrato encerrado não pode ser reativado.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Contrato reativado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Contrato")
 *   ),
 *
 *   @OA\Response(response=422, description="Transição não permitida",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=404, description="Contrato não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Patch(
 *   path="/api/contratos/{id}/encerrar", tags={"Contratos"}, summary="Encerra um contrato",
 *   description="Grava a data_fim com a data corrente. Estado terminal: não é possível encerrar de novo, suspender nem reativar depois.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Contrato encerrado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Contrato")
 *   ),
 *
 *   @OA\Response(response=422, description="Contrato já está encerrado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=404, description="Contrato não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Delete(
 *   path="/api/contratos", tags={"Contratos"}, summary="Remove um contrato",
 *   description="Remoção lógica (soft delete). O id vai no corpo, no campo uuid.",
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
 *   @OA\Response(response=204, description="Contrato removido"),
 *   @OA\Response(response=404, description="Contrato não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Get(
 *   path="/api/contratos/{id}/check-delete", tags={"Contratos"}, summary="Verifica vínculos do contrato",
 *   description="Devolve quantas cobranças o contrato possui.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Quantidade de cobranças vinculadas",
 *
 *     @OA\JsonContent(type="object",
 *       @OA\Property(property="count", type="integer", example=3),
 *       @OA\Property(property="haveRelationship", type="boolean", example=true)
 *     )
 *   ),
 *
 *   @OA\Response(response=404, description="Contrato não encontrado"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * ------- COBRANÇAS -------
 *
 * @OA\Get(
 *   path="/api/cobrancas", tags={"Cobranças"}, summary="Lista cobranças",
 *   description="Ordenação exigida pelo enunciado: as faturas em aberto e em atraso vêm primeiro, da mais antiga para a mais recente; depois as em aberto a vencer; as pagas ficam sempre por último, mesmo que tenham vencido antes. Os acréscimos de cada item são recalculados na consulta, conforme a diretiva (c).",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="filter[situacao]", in="query", @OA\Schema(type="string", enum={"aberta","paga"})),
 *   @OA\Parameter(name="filter[tipo]", in="query", @OA\Schema(type="string", enum={"boleto","cartao","pix"})),
 *   @OA\Parameter(name="filter[em_atraso]", in="query", description="1 para listar apenas as vencidas e em aberto", @OA\Schema(type="boolean")),
 *   @OA\Parameter(name="filter[contrato_id]", in="query", @OA\Schema(type="integer")),
 *   @OA\Parameter(name="filter[cliente]", in="query", description="Busca parcial pelo nome do cliente", @OA\Schema(type="string")),
 *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Lista paginada de cobranças",
 *
 *     @OA\JsonContent(type="object",
 *       @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Cobranca")),
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
 *   path="/api/cobrancas", tags={"Cobranças"}, summary="Gera uma cobrança",
 *   description="A data de vencimento vem do ciclo do contrato (diretiva b) e não é aceita na requisição. O valor original cai para o valor mensal do contrato quando omitido. Se a competência informada já estiver vencida, os acréscimos da diretiva (c) já entram na geração. Somente contrato ativo gera cobrança, e não é possível repetir a competência do mesmo contrato. Os campos do detalhe são exigidos conforme o tipo: boleto pede codigo_barras; cartao pede bandeira, titular, numero e validade; pix pede tipo_chave e chave. O CVV não é aceito em nenhuma hipótese, e o número do cartão é gravado criptografado.",
 *   security={{"bearer":{}}},
 *
 *   @OA\RequestBody(required=true,
 *
 *     @OA\MediaType(mediaType="application/json",
 *
 *       @OA\Schema(example={
 *         "contrato_id": 1,
 *         "competencia": "2027-02",
 *         "tipo": "pix",
 *         "pix_tipo_chave": "email",
 *         "pix_chave": "financeiro@amarassist.com.br"
 *       })
 *     )
 *   ),
 *
 *   @OA\Response(response=201, description="Cobrança gerada",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Cobranca")
 *   ),
 *
 *   @OA\Response(response=422, description="Dados inválidos, competência repetida ou contrato não ativo",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Get(
 *   path="/api/cobrancas/{id}", tags={"Cobranças"}, summary="Exibe uma cobrança",
 *   description="Enquanto aberta, os acréscimos são recalculados a cada consulta — o valor cresce 1% ao dia de atraso.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Cobrança encontrada",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Cobranca")
 *   ),
 *
 *   @OA\Response(response=404, description="Cobrança não encontrada"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Put(
 *   path="/api/cobrancas/{id}", tags={"Cobranças"}, summary="Atualiza uma cobrança",
 *   description="Aceita valor_original e data_vencimento; os acréscimos são recalculados na sequência. Cobrança paga não pode ser alterada.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\RequestBody(required=true,
 *
 *     @OA\MediaType(mediaType="application/json",
 *
 *       @OA\Schema(example={"valor_original": 299.90})
 *     )
 *   ),
 *
 *   @OA\Response(response=200, description="Cobrança atualizada",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Cobranca")
 *   ),
 *
 *   @OA\Response(response=422, description="Dados inválidos ou cobrança já paga",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=404, description="Cobrança não encontrada"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Patch(
 *   path="/api/cobrancas/{id}/pagar", tags={"Cobranças"}, summary="Registra o pagamento",
 *   description="Congela os acréscimos no valor apurado no dia do pagamento: a partir daí a fatura para de crescer, e a consulta passa a devolver o valor efetivamente pago, e não um recálculo.",
 *   security={{"bearer":{}}},
 *
 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *
 *   @OA\Response(response=200, description="Pagamento registrado",
 *
 *     @OA\JsonContent(ref="#/components/schemas/Cobranca")
 *   ),
 *
 *   @OA\Response(response=422, description="Cobrança já paga",
 *
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 *   ),
 *
 *   @OA\Response(response=404, description="Cobrança não encontrada"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 *
 * @OA\Delete(
 *   path="/api/cobrancas", tags={"Cobranças"}, summary="Remove uma cobrança",
 *   description="Remoção lógica (soft delete). O id vai no corpo, no campo uuid.",
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
 *   @OA\Response(response=204, description="Cobrança removida"),
 *   @OA\Response(response=404, description="Cobrança não encontrada"),
 *   @OA\Response(response=401, description="Não autenticado")
 * )
 */
class Annotations {}

# Amar Assist — Sistema de Cobrança

API do sistema de cobrança: clientes, contratos e faturas com acréscimo por
atraso, filas monitoradas e documentação interativa.

O front-end fica em repositório separado:
**[amar-assist-frontend](https://github.com/dhikernel/amar-assist-frontend)**.

| | |
|---|---|
| Back-end | Laravel 9.52 · PHP 8.1 |
| Banco | MySQL 8 |
| Cache e filas | Redis 7 · Laravel Horizon |
| Autenticação | Laravel Sanctum (tokens Bearer) |
| Documentação | OpenAPI (Swagger UI) · collection do Postman |
| Testes | PHPUnit — 208 testes |

## Pré-requisitos

Docker e Docker Compose. Nada além disso: PHP, Composer e MySQL rodam dentro
dos containers.

## Subindo o projeto

```bash
git clone git@github.com:dhikernel/amar-assist.git
cd amar-assist

cp .env.example .env
cp backend/.env.example backend/.env

docker compose up -d --build

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

A aplicação responde em **<http://localhost:8000>**.

O `.env` da raiz configura os containers; o de `backend/` configura o Laravel.
São arquivos distintos de propósito, com nomes de variável diferentes — o
Compose resolve o `.env` pelo diretório corrente, e nomes iguais fariam ele
publicar as portas erradas ao ser executado de dentro de `backend/`.

### Acesso

```
admin@amarassist.com.br
Amar@2026
```

### Endereços

| | |
|---|---|
| API | <http://localhost:8000/api> |
| Documentação (Swagger) | <http://localhost:8000/api/documentation> |
| Painel de filas (Horizon) | <http://localhost:8000/horizon> |
| MySQL | `localhost:3307` |
| Redis | `localhost:6381` |

As portas fogem do padrão para não conflitar com serviços já em execução na
máquina. Ajuste em `.env` se precisar.

### Domínio com HTTPS (opcional)

O projeto também atende em `https://amar-assist.site`, com certificado local.
Requer duas linhas de configuração na máquina:

```bash
# 1. Apontar o domínio para a máquina local
echo "127.0.0.1 amar-assist.site www.amar-assist.site" | sudo tee -a /etc/hosts

# 2. Confiar na autoridade certificadora local
mkcert -install
```

Sem esses passos o projeto funciona normalmente em `localhost:8000` — o
domínio é conveniência, não requisito.

O certificado em `docker/nginx/certs/` está versionado por decisão consciente,
e não por descuido: ele vale apenas para `amar-assist.site`, nome que só
resolve via arquivo `hosts` apontando para `127.0.0.1`, e é assinado por uma
autoridade local cuja chave permanece fora do repositório. Sem ele no
repositório o nginx não inicia, e a falha derrubaria junto o acesso por
`localhost:8000`. Em produção o certificado viria de uma autoridade pública e
a chave jamais entraria no controle de versão.

Para regenerá-lo: `./docker/nginx/certs/gerar-certificados.sh`

## Testes

```bash
docker compose exec app php artisan test
```

Rodam contra o MySQL real, em banco separado (`amar_assist_test`), criado no
primeiro boot do container. Testar em SQLite daria falso positivo: as regras
dependem de comportamento de data e de constraints do MySQL.

## As diretivas do enunciado

**(a) Cliente com contrato não pode ser desativado**
`app/Domain/Cliente/Repositories/ClienteRepository.php`

A situação não é campo comum: sai do `$validators`, então cadastro e
atualização não a alteram. Muda apenas por `PATCH /clientes/{id}/inactive`,
que verifica os contratos antes. Sem isso a regra seria contornável por um
`PUT` comum.

**(b) Ciclo de vencimento respeitando os dias de cada mês**
`app/Domain/Contrato/Models/Contrato.php` — `vencimentoPara()`

O ciclo guarda o dia pretendido (1 a 31) e o dia real é resolvido por mês,
limitado ao último dia disponível: ciclo 31 vence em 28/02, ou 29/02 em ano
bissexto, e em 30/04. Coberto por 17 testes, incluindo ano secular — 2100 não
é bissexto, 2000 é.

**(c) Acréscimo de 1% ao dia sobre a fatura em atraso**
`app/Domain/Cobranca/Models/Cobranca.php` — `calcularAcrescimos()`

Juros simples de 1% ao dia sobre o principal, mais multa percentual. O cálculo
usa `bcmath`, não ponto flutuante: em `float`, 0,1 + 0,2 não dá 0,3, e num
sistema de cobrança isso vira divergência de centavo entre a tela e o banco.

Enquanto aberta, a fatura recalcula a cada consulta — é o que a diretiva pede
ao falar em verificar a data atual. Ao ser paga, o valor apurado é gravado e
congela.

## Estrutura

```
app/Domain/<Modulo>/
├── Controllers/     rota → controller
├── Repositories/    acesso a dados e regra de negócio
├── Resources/       formato da resposta
├── Models/
└── Enums/
```

Módulos: `Auth`, `Cliente`, `Contrato`, `Cobranca` e `Shared`.

O CRUD comum vive em `app/Traits/ControllerTrait.php`, aplicado na classe base
`Controller`. Cada controller declara apenas o repositório e o `$validators`.

## Tabelas

`clientes` · `contratos` · `cobrancas` · `tipo_cobrancas` · `users`

`tipo_cobrancas` guarda o que é específico de cada forma de pagamento: código
de barras do boleto, dados do cartão e chave do pix.

O número do cartão é gravado criptografado e a resposta devolve apenas os
quatro últimos dígitos. O CVV não existe como campo em lugar nenhum, conforme
o PCI-DSS.

## Filas e cache

`POST /api/cobrancas/gerar-lote` enfileira um job por contrato ativo na fila
`cobrancas` do Redis e responde 202 sem esperar a geração. O progresso é
acompanhado em `/horizon`.

A operação é idempotente: reenviar o mesmo lote não duplica faturas, porque
cada job trata competência já existente como trabalho concluído — o que também
torna seguras as retentativas automáticas.

`GET /api/cobrancas/resumo` serve os indicadores da tela de cobrança a partir
do cache no Redis, descartado a cada cobrança criada, paga ou removida.

## Collection do Postman

`amar-assist.postman_collection.json` — 47 requisições cobrindo os 28
endpoints, incluindo os casos de erro. As pastas estão na ordem de execução:
autentique-se em **Auth › Login** e o token é guardado automaticamente para as
demais.

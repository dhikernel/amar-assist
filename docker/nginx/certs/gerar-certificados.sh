#!/usr/bin/env bash
#
# Gera o certificado TLS local de amar-assist.site.
# Requer o mkcert: https://github.com/FiloSottile/mkcert
#
set -euo pipefail

cd "$(dirname "$0")"

if ! command -v mkcert >/dev/null 2>&1; then
    echo "mkcert não encontrado. Instale-o antes de continuar:" >&2
    echo "  https://github.com/FiloSottile/mkcert#installation" >&2
    exit 1
fi

# Instala a CA local no trust store (idempotente).
mkcert -install

# Gera o par para o domínio e o www. O mkcert nomeia os arquivos com o sufixo
# "+1" por causa do nome adicional — é o nome esperado pelo default.conf.
mkcert amar-assist.site www.amar-assist.site

# Cópia do certificado público da CA, usada para validar a cadeia em testes
# automatizados (curl --cacert). A chave da CA não é copiada.
cp "$(mkcert -CAROOT)/rootCA.pem" .

echo
echo "Certificados gerados em $(pwd):"
ls -1 ./*.pem
echo
echo "Lembre-se de apontar o domínio para 127.0.0.1 no arquivo hosts:"
echo "  127.0.0.1 amar-assist.site www.amar-assist.site"

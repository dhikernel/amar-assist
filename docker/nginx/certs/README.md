# Certificados de desenvolvimento

Os arquivos `amar-assist.site+1.pem` e `amar-assist.site+1-key.pem` são um
certificado TLS **exclusivo do ambiente local**, emitido com
[mkcert](https://github.com/FiloSottile/mkcert).

## Por que a chave privada está versionada

Versionar chave privada é, em regra, um erro grave — então registro aqui o
raciocínio, já que é uma exceção consciente e não um descuido:

1. O certificado é válido **apenas** para `amar-assist.site` e
   `www.amar-assist.site`, nomes que só resolvem via arquivo `hosts` apontando
   para `127.0.0.1`. Não existe host público que ele consiga autenticar.
2. Ele é assinado por uma CA local. Um atacante só conseguiria usá-lo contra
   quem já tem **essa** CA instalada na máquina — ou seja, o próprio
   desenvolvedor. A chave da CA (`rootCA-key.pem`) permanece fora do
   repositório, em `~/.local/share/mkcert`.
3. Sem os certificados no repositório o nginx não inicia (`ssl_certificate`
   aponta para arquivo inexistente), e a falha derruba **também** o acesso por
   `http://localhost:8000`. O projeto deixaria de subir com um
   `docker compose up`, que é justamente o que se espera de um ambiente
   dockerizado.

Em produção nada disso se aplica: o certificado viria de uma CA pública
(Let's Encrypt, por exemplo), seria emitido no servidor e a chave jamais
entraria no controle de versão.

## Regenerar

```bash
./gerar-certificados.sh
```

## Confiar na CA local

Necessário uma única vez por máquina, para o navegador não acusar certificado
inválido:

```bash
mkcert -install
```

No WSL, o navegador roda no Windows: instale a CA também no Windows, executando
`mkcert -install` a partir de um terminal do Windows, ou importando o
`rootCA.pem` em *Autoridades de Certificação Raiz Confiáveis*.

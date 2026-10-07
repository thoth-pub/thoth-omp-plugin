[English](README.md) | [Español](README-es.md) | **Português Brasileiro**

# Testes Cypress

Os testes usam OMP 3.5 e Thoth descartáveis, sem alterar sua instalação local
nem gravar nas APIs públicas do Thoth.

## Requisitos

- Docker com Compose v2 e Python 3.9 ou posterior.
- Host Linux/amd64 ou emulação compatível.
- Dataset MySQL do OMP `stable-3_5_0`, contexto `publicknowledge`, com
  `database.sql`, `files/` e `public/` do mesmo snapshot.

## Preparar e executar

Na raiz do plugin:

```sh
python3 tests/environment/environment.py prepare --dataset /path/to/omp-dataset --apply
python3 tests/environment/environment.py run --apply
```

`prepare` constrói as imagens, inicia ou retoma Thoth e seus bancos, renova o token
quando necessário e restaura o dataset no OMP descartável. Não precisa de `up`.
A restauração substitui os dados de teste do OMP; o dataset de origem permanece intacto.
Publisher e imprint do Thoth são preservados entre preparações.

`run` executa a suíte uma vez. Para executar somente uma spec:

```sh
python3 tests/environment/environment.py run --spec ThothRegistration.cy.js --apply
```

Sem `--apply`, os comandos de alteração apenas mostram o plano. `status` consulta
os contêineres do projeto sem modificá-los.

## Uso interativo

Depois de `prepare`, na mesma sessão gráfica:

```sh
python3 tests/environment/environment.py open --apply
```

Requer X11/XWayland e `xauth`. O contêiner acessa sua sessão X11; use somente imagens
e testes confiáveis. Alterações nas specs ficam disponíveis sem reconstruir a imagem.
Fechar Cypress mantém o ambiente ativo. Feche-o antes de executar outro comando.

`open` e `run` reutilizam os dados e verificam a comunicação autenticada do OMP com
Thoth, sem usar o cache do plugin. Se os serviços estiverem parados ou a autenticação
falhar, interrompem antes de abrir Cypress e orientam executar `prepare` novamente.
Essa preparação restaura o dataset; não há restauração implícita ao abrir os testes.

## Credenciais e retomada

O token da conta de testes dura dois dias. `prepare` renova tokens expirados,
revogados ou com menos de uma hora restante. A API também verifica o token ao reiniciar.
A identidade da API e o PAT administrativo ficam no volume privado `bootstrap`;
o OMP recebe somente o volume `client`, em leitura. O PAT administrativo usa validade
longa apenas nesta instância isolada e é removido com `down`.

Na CI, o runner compartilha `/builds` entre job e serviços: os arquivos administrativos
ficam acessíveis ao job até a limpeza final. A separação por volumes descrita acima
é do Compose local. A CI usa os mesmos comandos internos `prepare` e `run`, executando
a suíte duas vezes após uma preparação.

## Encerrar ou recriar

```sh
python3 tests/environment/environment.py down --apply
```

Remove somente os serviços, volumes e dados de teste deste projeto. Para começar do
zero, execute `prepare` novamente. Após reiniciar o computador, basta `prepare`.

Ambientes criados pela versão antiga exigem `down` uma vez antes do novo `prepare`,
pois não guardavam a chave necessária para retomar a API. Os comandos antigos `up`,
`cypress` e `smoke` foram removidos; use `prepare`, `run` e `status`.

## Cobertura do registro

`ThothRegistration.cy.js` prepara no OMP um livro publicado com metadados completos,
registra pela interface e verifica os dados persistidos na Thoth por uma consulta GraphQL
independente. Cobre títulos, resumos e biografias em dois idiomas; DOI, data, edição,
local, páginas e imagens; licença, direitos autorais e URL da capa; autoria, ORCID,
website e afiliação ROR; idioma, assuntos e referências; PDF, EPUB e impresso, ISBN,
acessibilidade e links digitais; e um capítulo com DOI, páginas, metadados traduzidos e autoria.

A fixture completa é exclusiva desse cenário. Padrões e exceção de acessibilidade ficam
em formatos digitais distintos, conforme as regras da Thoth. Upload/hospedagem da capa
em S3 e arquivos/links próprios de capítulo ficam fora desse cenário. A cobertura abrange
os grupos de metadados, não todas as combinações de valores.

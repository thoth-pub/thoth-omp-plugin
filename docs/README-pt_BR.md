[English](/README.md) | [Español](/docs/README-es.md) | **Português Brasileiro**

# Plugin Thoth OMP

[![Versão Atual](https://img.shields.io/badge/versão-v0.3.0.0-blue)](https://github.com/thoth-pub/thoth-omp-plugin/releases)
[![Licença: GPL v3](https://img.shields.io/badge/Licença-GPLv3-green.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Compatibilidade OMP](https://img.shields.io/badge/OMP-3.3_%7C_3.4_%7C_3.5-blue)](https://pkp.sfu.ca/software/omp/)

Integra o [OMP (Open Monograph Press)](https://pkp.sfu.ca/software/omp/) com o [Thoth](https://thoth.pub/), uma plataforma aberta de gestão de metadados para livros. Este plugin permite o registro e a sincronização de metadados em nível de livro e capítulo diretamente do OMP para o Thoth, onde podem ser disseminados em múltiplos formatos padrão da indústria, incluindo ONIX, MARC, KBART e Crossref XML.

## Compatibilidade

Este plugin é compatível com as seguintes aplicações PKP:

- OMP 3.3.0-x
- OMP 3.4.0-x
- OMP 3.5.0-x

## Instalação

1. Baixe a versão mais recente do pacote de instalação (`thoth.tar.gz`) na [página de lançamentos](https://github.com/thoth-pub/thoth-omp-plugin/releases).

2. Acesse a área de administração do seu site OMP através do Dashboard. Navegue até `Configurações` > `Site` > `Plugins` > `Enviar um novo plugin` e selecione o arquivo `thoth.tar.gz`.

3. Clique em 'Salvar' para instalar o plugin.

## Uso

### Configuração

Após habilitar o plugin, vá nas configurações do plugin e preencha:

- **E-mail** e **Senha**: Credenciais de uma conta do Thoth para conectar com a API.
- **API Thoth personalizada**: Marque esta opção para usar uma API Thoth personalizada em vez da oficial.
- **URL da API Thoth**: A URL da API Thoth personalizada (necessária apenas quando a opção de API personalizada está habilitada).

<img src="/docs/images/plugin_settings.png" alt="Formulário de configuração do plugin com campos de e-mail, senha, API personalizada e URL" width="700">

### Registro de Monografias

#### Monografias Não Publicadas

Registre os metadados no Thoth durante o processo de publicação, selecionando a opção para registrar metadados no modal de publicação e escolhendo um selo.

<img src="/docs/images/register_field.png" alt="Modal de publicação com opção de registro no Thoth" width="700">

#### Monografias Publicadas

Registre os metadados para monografias já publicadas usando o botão 'Registrar' ao lado do status de publicação.

<img src="/docs/images/register_button.png" alt="Botão de registro no fluxo de trabalho de publicação" width="700">
<img src="/docs/images/register_modal.png" alt="Modal de registro com seleção de selo" width="700">

### Atualização de Metadados

Uma vez que uma monografia está registrada, as atualizações de metadados são **automáticas**. Despublique a monografia, edite os dados e as alterações serão sincronizadas com o Thoth ao republicar.

Também é possível atualizar manualmente os metadados no Thoth clicando no botão 'Atualizar metadados' ao lado do status de publicação.

<img src="/docs/images/update_button.png" alt="Botão de atualização de metadados no fluxo de trabalho de publicação" width="700">

### Acessando Registros de Livros no Thoth

Após o registro dos metadados, um link para o livro no Thoth aparecerá no topo do fluxo de trabalho de publicação.

<img src="/docs/images/view_button.png" alt="Link para o registro do livro no Thoth" width="700">

### Registro em Massa

Na página de gestão do Thoth, você pode enviar em massa uma seleção de títulos do OMP para o Thoth.

<img src="/docs/images/bulk_register_page.png" alt="Página de gestão do Thoth com registro em massa" width="700">

### Orientações

- Apenas tags HTML básicas são preservadas em campos de texto: `<strong>`, `<mark>`, `<em>`, `<i>`, `<u>`, `<sup>`, `<sub>`, `<ul>`, `<ol>` e `<li>`. Todas as outras tags serão removidas.
- O ISBN deve estar devidamente formatado como ISBN-13 (por exemplo, `978-3-16-148410-0`).

## Mapeamento OMP-Thoth

<details>
<summary>Clique aqui para ver a relação de dados entre OMP e Thoth</summary>

| OMP               |                    |   | Thoth                  |                     |             |
| ----------------- | ------------------ | - | ---------------------- | ------------------- | ----------- |
| Submission        |                    |   | Work                   |                     |             |
|                   | WorkType           |   |                        | WorkType            |             |
| SubmissionUrl     |                    |   |                        | LandingPage         |             |
| Publication       |                    |   |                        |                     |             |
|                   | FullTitle          |   |                        | FullTitle           |             |
|                   | Title              |   |                        | Title               |             |
|                   | Subtitle           |   |                        | Subtitle            |             |
|                   | Abstract           |   |                        | Abstract            |             |
|                   | Version            |   |                        | Edition             |             |
|                   | DOI                |   |                        | DOI                 |             |
|                   | DatePublished      |   |                        | PublicationDate     |             |
|                   | License            |   |                        | License             |             |
|                   | CopyrightHolder    |   |                        | CopyrightHolder     |             |
|                   | CoverUrl           |   |                        | CoverImageUrl       |             |
| Author            |                    |   | Contribution           |                     |             |
|                   | UserGroupId        |   |                        | ContributionType    |             |
|                   | PrimaryContactId   |   |                        | MainContribution    |             |
|                   | Sequence           |   |                        | ContributionOrdinal |             |
|                   | GivenName          |   |                        | FirstName           |             |
|                   | LastName           |   |                        | FamilyName          |             |
|                   | FullName           |   |                        | FullName            |             |
|                   | Biography          |   |                        | Biography           |             |
|                   | Affiliation        |   | Affiliation            |                     |             |
| Chapter           |                    |   | Work(Type: Chapter)    |                     |             |
|                   | FullTitle          |   |                        | FullTitle           |             |
|                   | Title              |   |                        | Title               |             |
|                   | Subtitle           |   |                        | Subtitle            |             |
|                   | Abstract           |   |                        | Abstract            |             |
|                   | Pages              |   |                        | pageCount           |             |
|                   | DatePublished      |   |                        | PublicationDate     |             |
|                   | DOI                |   |                        | DOI                 |             |
| SubmissionLocale  |                    |   | Language               |                     |             |
| PublicationFormat |                    |   | Publication            |                     |             |
|                   | EntryKey           |   |                        | PublicationType     |             |
|                   | IdentificationCode |   |                        | ISBN                |             |
|                   |                    |   |                        | Location            |             |
|                   | RemoteUrl/FileUrl  |   |                        |                     | FullTextUrl |
| SubmissionUrl     |                    |   |                        |                     | LandingPage |
| Keyword           |                    |   | Subject(Type: Keyword) |                     |             |
| Citation          |                    |   | Reference              |                     |             |

</details>

## Desenvolvimento

### Requisitos

- PHP 8.1+
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (para compilar os recursos do frontend)

### Configuração do Ambiente

```bash
# Instalar dependências PHP
composer install

# Instalar dependências do Node.js e compilar recursos do frontend
npm install
npm run build
```

### Execução de Testes

```bash
# A partir do diretório raiz do OMP
php lib/pkp/lib/vendor/phpunit/phpunit/phpunit --configuration lib/pkp/tests/phpunit.xml -v plugins/generic/thoth/tests
```

## Créditos

Este plugin foi idealizado e patrocinado pelo [Thoth](https://thoth.pub/).

Desenvolvido por [Lepidus Tecnologia](https://github.com/lepidus).

## Licença

Este plugin está licenciado sob a Licença Pública Geral GNU v3.0 - [Veja o arquivo de licença.](/LICENSE)

Copyright (c) 2024-2026 Lepidus Tecnologia

Copyright (c) 2024-2026 Thoth

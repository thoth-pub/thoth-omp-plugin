[English](/README.md) | [Español](/docs/README-es.md) | **Português Brasileiro**

# Plugin Thoth OMP

[![Versão Atual](https://img.shields.io/badge/versão-v0.3.0.0-blue)](https://github.com/thoth-pub/thoth-omp-plugin/releases)
[![Licença: GPL v3](https://img.shields.io/badge/Licença-GPLv3-green.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Compatibilidade OMP](https://img.shields.io/badge/OMP-3.3-blue)](https://pkp.sfu.ca/software/omp/)

Integra o [OMP (Open Monograph Press)](https://pkp.sfu.ca/software/omp/) com o [Thoth](https://thoth.pub/), uma plataforma aberta de gestão de metadados para livros. Este plugin permite o registro e a sincronização de metadados em nível de livro e capítulo diretamente do OMP para o Thoth, onde podem ser disseminados em múltiplos formatos padrão da indústria, incluindo ONIX, MARC, KBART e Crossref XML.

## Compatibilidade

Este plugin é compatível com as seguintes aplicações PKP:

- OMP 3.3.0-x

## Requisitos

### Requisitos da Editora

1. **api_key_secret**

A instância do OMP deve ter o `api_key_secret` configurado. Você pode entrar em contato com o administrador do sistema para configurar isso (consulte [este post](https://forum.pkp.sfu.ca/t/how-to-generate-a-api-key-secret-code-in-ojs-3/72008)).

Isso é necessário para armazenar o token de acesso pessoal do Thoth de forma criptografada no banco de dados do OMP.

## Instalação

1. Baixe a versão mais recente do pacote de instalação (`thoth.tar.gz`) na [página de lançamentos](https://github.com/thoth-pub/thoth-omp-plugin/releases).

2. Acesse a área de administração do seu site OMP através do Dashboard. Navegue até `Configurações` > `Site` > `Plugins` > `Enviar um novo plugin` e selecione o arquivo `thoth.tar.gz`.

3. Clique em 'Salvar' para instalar o plugin.

## Uso

### Configuração

Após habilitar o plugin, vá nas configurações do plugin e preencha:

- **Token de acesso pessoal**: Um token de acesso pessoal válido do Thoth para autenticar as requisições da API.
- **API Thoth personalizada**: Marque esta opção para usar uma API Thoth personalizada em vez da oficial.
- **URL da API Thoth**: A URL da API Thoth personalizada, necessária apenas quando a opção de API personalizada estiver habilitada.

<img src="/docs/images/plugin_settings.png" alt="Formulário de configuração do plugin com token de acesso pessoal, API personalizada e URL" width="700">

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

### Acessando Registros de Livros no Thoth

Após o registro dos metadados, um link para o livro no Thoth aparecerá no topo do fluxo de trabalho de publicação.

<img src="/docs/images/view_button.png" alt="Link para o registro do livro no Thoth" width="700">

### Envio e Hospedagem de Arquivos no Thoth

O plugin permite enviar ao Thoth arquivos de publicação, a capa e um vídeo em destaque. Essas funcionalidades ficam
disponíveis depois que o livro é registrado no Thoth e exigem que o usuário configurado no Thoth tenha a permissão
`cdnWrite`.

#### Arquivos de Publicação

No fluxo da publicação, abra a grade de formatos de publicação e use **Enviar para Thoth** no formato desejado.
A ação aparece abaixo dos detalhes do formato e acima dos arquivos associados a ele.

<img src="/docs/images/publication_file_upload_action.png" alt="Ação Enviar para Thoth na grade de formatos de publicação" width="700">

No formulário de envio, selecione um arquivo e indique se ele pertence à monografia ou a um capítulo. A monografia
ou o capítulo selecionado precisa ter DOI. Os arquivos podem ter até 50 MB.

<img src="/docs/images/publication_file_upload_form.png" alt="Formulário de envio para o Thoth com o arquivo e o componente da publicação relacionado" width="700">

Após o envio, o botão **Visualizar**, na coluna **Arquivos no Thoth**, abre a lista dos arquivos da monografia e dos
capítulos hospedados pelo Thoth. Cada nome de arquivo é um link para o arquivo identificado pelo respectivo DOI.

<img src="/docs/images/publication_file_view_action.png" alt="Botão Visualizar na coluna Arquivos no Thoth" width="700">

<img src="/docs/images/publication_file_view_form.png" alt="Lista de arquivos da monografia e dos capítulos hospedados pelo Thoth" width="700">

Na página pública do livro, os arquivos hospedados pelo Thoth são exibidos junto aos arquivos dos formatos de
publicação do OMP.

<img src="/docs/images/publication_file_landing_page.png" alt="Página pública do livro exibindo arquivos hospedados pelo Thoth junto aos arquivos dos formatos de publicação do OMP" width="700">

#### Capa

No formulário de entrada de catálogo, envie a capa do livro e selecione **Hospedar o arquivo da imagem de capa na
Thoth**, abaixo do campo de capa. Depois que a publicação for salva e sincronizada, a página pública do livro usará a
capa hospedada pelo Thoth. Desmarcar essa opção faz com que o plugin deixe de usar a capa hospedada anteriormente.

<img src="/docs/images/cover_upload.png" alt="Opção de hospedagem do arquivo da capa no Thoth abaixo do campo de imagem da capa" width="700">

#### Vídeo em Destaque

Abra a aba **Marketing** no fluxo da publicação e selecione **Vídeo em destaque**, abaixo de **Datas de publicação**.
Informe um título e envie um arquivo MP4, WebM ou MOV. O arquivo será hospedado pelo Thoth e exibido com controles de
reprodução na página pública do livro, depois das informações e da sinopse. Cada livro pode ter um vídeo em destaque.

<img src="/docs/images/feature_video_form.png" alt="Formulário do vídeo em destaque com título e arquivo de vídeo enviado" width="700">

<img src="/docs/images/feature_video_landing_page.png" alt="Player do vídeo em destaque na página pública do livro" width="700">

### Registro em Massa

Na página de gestão do Thoth, você pode enviar em massa uma seleção de títulos do OMP para o Thoth.

<img src="/docs/images/bulk_register_page.png" alt="Página de gestão do Thoth com registro em massa" width="700">

### Orientações

- Apenas tags HTML básicas são preservadas em campos de texto: `<strong>`, `<mark>`, `<em>`, `<i>`, `<u>`, `<sup>`, `<sub>`, `<ul>`, `<ol>` e `<li>`. Todas as outras tags serão removidas.
- O ISBN deve estar devidamente formatado como ISBN-13, por exemplo `978-3-16-148410-0`.
- Para evitar atribuição incorreta de afiliações no Thoth, use o [plugin ROR](https://github.com/withanage/ror) para preencher as afiliações no OMP.

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
| Subject           |                    |   | Subject(Type: esquema detectado ou Keyword) |        |             |
| Keyword           |                    |   | Subject(Type: Keyword) |                     |             |
| Citation          |                    |   | Reference              |                     |             |

### Assuntos e palavras-chave

O OMP oferece duas formas complementares de descrever uma publicação:

- **Palavras-chave** são termos livres que ajudam os leitores a encontrar a publicação. Toda palavra-chave é
  enviada à Thoth como palavra-chave e nunca é tratada como uma classificação personalizada.
- **Assuntos** podem ser termos descritivos ou códigos de sistemas de classificação reconhecidos, como LCC,
  BISAC, BIC e Thema. Quando a classificação pode ser confirmada, a Thoth registra o assunto com o sistema
  correspondente.
- Quando um assunto não pode ser associado com segurança a um sistema de classificação, ele é mantido como
  palavra-chave, em vez de ser descartado ou receber uma classificação incerta.
- Assuntos associados a outro vocabulário claramente identificado são registrados como uma classificação
  personalizada.

Para disponibilizar o campo Assuntos, acesse **Configurações > Fluxo de trabalho > Submissão > Metadados**,
selecione **Habilitar metadados de assunto** e salve as alterações. Na mesma configuração, escolha se o campo
ficará disponível apenas para a equipe editorial, será solicitado aos autores ou será obrigatório durante a
submissão.

O editor pode informar somente o código, como `GTK` ou `EDU000000`. Também pode identificar o sistema de
classificação usando um prefixo, como `THEMA:GTK` ou `BISAC:EDU000000`.

</details>

## Créditos

Este plugin foi idealizado e patrocinado pelo [Thoth](https://thoth.pub/).

Desenvolvido por [Lepidus Tecnologia](https://github.com/lepidus).

## Licença

Este plugin está licenciado sob a Licença Pública Geral GNU v3.0 - [Veja o arquivo de licença.](/LICENSE)

Copyright (c) 2024-2026 Lepidus Tecnologia

Copyright (c) 2024-2026 Thoth Open Metadata

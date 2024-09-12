[English](/README.md) | [Español](/docs/README-es.md) | **Português Brasileiro**

# Plugin Thoth OMP

Integração em progresso do OMP com o [Thoth](https://thoth.pub/) para comunicação e sincronização dos dados dos livros entre as duas plataformas.

## Compatibilidade

Este plugin é compatível com as seguintes aplicações PKP:

- OMP 3.3.0-x

## Requisitos

### Requisitos da Editora

1. **api_key_secret**

A instância do OMP deve ter o `api_key_secret` configurado. Você pode entrar em contato com o administrador do sistema para configurar isso (consulte [este post](https://forum.pkp.sfu.ca/t/how-to-generate-a-api-key-secret-code-in-ojs-3/72008)).

Isso é necessário para usar as credenciais da API fornecidas, que são armazenadas criptografadas no banco de dados do OMP.

## Instalação

1. Baixe a versão mais recente do pacote de instalação (`thoth.tar.gz`) na [página de lançamentos](https://github.com/lepidus/thoth-omp-plugin/releases).

2. Acesse a área de administração do seu site OMP através do Dashboard. Navegue até `Configurações` > `Site` > `Plugins` > `Enviar um novo plugin` e selecione o arquivo `thoth.tar.gz`.

3. Clique em 'Salvar' para instalar o plugin.

## Uso

### Configuração

Para configurar o plugin:

- **E-mail** e **Senha**: Insira as credenciais de uma conta do Thoth para conectar com a API.
- **Ambiente de Teste**: Marque esta opção se você estiver usando uma instância local da API do Thoth para fins de teste.

### Gerenciamento de Monografias

- **Monografias Não Publicadas**: Registre os metadados no Thoth durante o processo de publicação, selecionando a opção para registrar metadados no modal de publicação e escolhendo uma editora.

- **Monografias Publicadas**: Registre os metadados para monografias publicadas usando o botão 'Registrar' ao lado do status de publicação.

### Atualização de Metadados

Para atualizar os metadados no Thoth, despublique a monografia, edite os dados e as alterações serão atualizadas automaticamente no Thoth.

### Acessando Registros de Livros no Thoth

Após a publicação dos metadados, um link para o livro no Thoth aparecerá no topo da publicação.

## Créditos

Este plugin foi idealizado e patrocinado pelo [Thoth](https://thoth.pub/).

Desenvolvido por [Lepidus Tecnologia](https://github.com/lepidus).

## Licença

Este plugin está licenciado sob a Licença Pública Geral GNU v3.0 - [Veja o arquivo de licença.](/LICENSE)

Copyright (c) 2024 Lepidus Tecnologia

Copyright (c) 2024 Thoth
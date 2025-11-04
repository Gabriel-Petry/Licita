Projeto LicitAcoes
📖 Sobre o Projeto
O Projeto LicitAções é uma aplicação web desenvolvida em PHP para auxiliar no gerenciamento e acompanhamento de processos de licitação pública.

O sistema permite o cadastro de demandas, a montagem de processos, a geração de documentos (Relatórios, Editais, DFDs) e o acompanhamento de status através de um dashboard e de um construtor visual de fluxogramas.

✨ Funcionalidades Principais
O sistema é dividido nos seguintes módulos e funcionalidades:

- Controle de Acesso

- Sistema de login e logout (login.php, logout.php).

- Cadastro de novos usuários (cadastrar_usuario.php).

- Verificação de permissões de acesso.

- Dashboard Principal (dashboard.php)

- Visão geral com gráficos e estatísticas.

- Consumo de dados via api_dashboard.php.

- Gerenciamento e Cadastros

- Demandas: Cadastro e acompanhamento de novas demandas.

- Licitações: Gerenciamento dos processos licitatórios.

- Contratos: Controle de contratos vigentes.

- Fornecedores: Cadastro e consulta de fornecedores.

- Atas: Gerenciamento de atas.

- Plano Anual (PCA): Controle do Plano Anual de Contratação.

- Homologadas: Listagem de licitações homologadas.

- Contratações Diretas: Módulo para contratações diretas.

- Ferramentas de Processo

- Montagem de Processo (montagem.php): Funcionalidade central para "montar" um processo de licitação, selecionando itens e etapas.

- Fluxograma (fluxograma.php): Ferramenta visual para criar, salvar, carregar e deletar fluxogramas, permitindo modelar o andamento dos processos.

- Geração de Documentos

- Geração de Relatórios (provavelmente em .xlsx via gerar_relatorio.php).

- Geração de Editais (em .docx via gerar_edital.php).

- Geração de DFD (Documento de Formalização da Demanda) (via gerar_dfd.php).

- Segurança

- Implementação de proteção contra CSRF (Cross-Site Request Forgery) em formulários (includes/csrf.php).

🛠️ Tecnologias Utilizadas
Este projeto é construído com uma arquitetura PHP tradicional (sem um framework MVC moderno) e utiliza bibliotecas de mercado para funcionalidades específicas.

- Backend
PHP

- MySQL (inferido a partir do includes/db.php)

- Composer (para gerenciamento de dependências PHP)

- Bibliotecas PHP (Principais)
O projeto utiliza o composer.json para gerenciar as seguintes dependências principais:

phpoffice/phpword: Para criação e manipulação de arquivos .docx (usado em gerar_edital.php).

phpoffice/phpspreadsheet: Para criação e manipulação de planilhas Excel (usado em gerar_relatorio.php).

dompdf/dompdf: Para geração de arquivos PDF a partir de HTML.

- Frontend
HTML5

- CSS3 (Modularizado em base.css, layout.css, components.css, etc.)

- JavaScript (Vanilla): Usado para interatividade, chamadas AJAX (Fetch API) para os endpoints api_*.php e para a lógica dos módulos de fluxograma e montagem.

- npm: package.json indica o uso de pacotes Node.js, possivelmente para ferramentas de desenvolvimento ou bibliotecas frontend.

📁 Estrutura do Projeto
A estrutura de pastas principal é organizada da seguinte forma:

/
├── api_*.php           # Endpoints da API (ex: dashboard, fluxograma)
├── css/                 # Arquivos de estilo CSS
├── img/                 # Imagens estáticas (logo, cabeçalho, etc.)
├── includes/            # Módulos principais de backend
│   ├── auth.php         # Lógica de autenticação
│   ├── config.php       # Configurações gerais
│   ├── csrf.php         # Lógica de proteção CSRF
│   ├── db.php           # Conexão com banco de dados
│   └── layout.php       # Template de layout (header/footer)
├── js/                  # Scripts JavaScript para as páginas
├── pages/               # Arquivos de página (controladores e visualização)
│   ├── login.php        # Página de login
│   ├── dashboard.php    # Página principal
│   ├── licitacoes.php   # CRUD de licitações
│   ├── fluxograma.php   # Construtor de fluxograma
│   ├── montagem.php     # Módulo de montagem de processo
│   └── gerar_*.php      # Scripts de geração de documentos
├── vendor/              # Dependências do Composer
├── index.php            # Ponto de entrada principal da aplicação
├── composer.json        # Dependências do PHP
└── package.json         # Dependências do Node.js

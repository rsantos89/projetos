# Diário Escolar Municipal

Diário de classe digital + sistema de matrículas para redes municipais de ensino (Educação Infantil, Ensino Fundamental I e II), com suporte a **turmas multisseriadas**.

Feito para rodar em **qualquer hospedagem cPanel**: PHP puro + MySQL/MariaDB, sem Composer, Node ou frameworks. Todo o front-end (CSS + JS) tem ~14 KB, sem dependências externas.

## Funcionalidades

- **Acesso**: login com Google (OAuth 2.0 — apenas e-mails cadastrados entram, com restrição opcional a um domínio Google Workspace) e login por senha como reserva.
- **Perfis**: Secretaria Municipal (toda a rede), Gestão Escolar (sua escola) e Professor(a) (somente suas turmas/componentes).
- **Cadastros**: escolas, anos letivos (bimestres/trimestres/semestres, média, nota máxima, frequência mínima, dias letivos, fechamento de períodos), etapas, componentes curriculares e matriz por etapa, usuários, configurações e log de auditoria.
- **Turmas**: turno, sala, vagas, uma ou várias etapas (**multisseriada**), lotação de professores por componente ou polivalente.
- **Alunos**: identificação, documentos (CPF, NIS, certidão, SUS, INEP), filiação/responsável, endereço com busca de CEP, zona, NEE, transporte escolar, Bolsa Família, saúde, uso de imagem; alerta de possível duplicidade.
- **Matrículas**: nova matrícula, transferência recebida, remanejamento, transferência dentro da rede (levando notas e pareceres), abandono, transferência para fora, cancelamento, falecimento, reativação, troca de etapa na multisseriada, controle de vagas e **rematrícula em lote** para o ano seguinte.
- **Diário**: frequência (diária ou por componente, com falta justificada), registro de aulas/conteúdos, notas por período com recuperação, recuperação final, pareceres descritivos (Educação Infantil) e ocorrências. Períodos fechados ficam bloqueados para professores.
- **Fechamento**: cálculo automático de média, frequência e situação (aprovado/reprovado), com confirmação pela gestão.
- **Relatórios para impressão/PDF**: lista de alunos, diário de classe mensal (frequência + conteúdos), boletim, ata de resultados finais, declaração de matrícula/escolaridade, ficha individual, quantitativo de matrículas da rede e alunos infrequentes.
- **Painel** com indicadores da rede/escola e turmas sem registro de aula.

## Requisitos

- PHP 8.0 ou superior com extensões `pdo_mysql` e `curl` (padrão no cPanel)
- MySQL 5.7+ ou MariaDB 10.3+

## Instalação no cPanel

1. **Banco de dados** – em *cPanel → Bancos de dados MySQL*: crie um banco, um usuário e adicione o usuário ao banco com *Todos os privilégios*.
2. **PHP** – em *Selecionar versão do PHP* (ou *MultiPHP Manager*), escolha PHP 8.0 ou superior.
3. **Arquivos** – envie o conteúdo deste repositório (ZIP) pelo *Gerenciador de Arquivos* para `public_html/` (ou uma subpasta/subdomínio, ex.: `diario.prefeitura.gov.br`) e extraia.
4. **Instalador** – acesse `https://SEU-DOMINIO/install/`, informe os dados do banco, do município e do administrador.
5. **Segurança** – após a instalação, **apague a pasta `install/`**. O arquivo `config.php` é criado com permissão restrita e bloqueado pelo `.htaccess`.
6. Ative o SSL gratuito (*SSL/TLS Status → AutoSSL*) — obrigatório para o login com Google.

## Login com Google

1. Em <https://console.cloud.google.com/> crie um projeto → *APIs e serviços → Tela de consentimento OAuth* (tipo *Externo* ou *Interno*, se usar Google Workspace).
2. *Credenciais → Criar credenciais → ID do cliente OAuth → Aplicativo da Web*.
3. Em **URIs de redirecionamento autorizados**, adicione: `https://SEU-DOMINIO/caminho/login-google.php`
4. No sistema, entre como Secretaria Municipal → *Cadastros → Configurações* e cole o *Client ID* e o *Client Secret*. Opcionalmente informe o domínio permitido (ex.: `educacao.municipio.gov.br`).
5. Cadastre os usuários com o e-mail Google de cada um. Só e-mails cadastrados e ativos conseguem entrar.

## Primeiros passos

1. *Cadastros → Escolas*: cadastre as escolas da rede.
2. *Cadastros → Anos letivos*: confira datas dos períodos, média e frequência mínima.
3. *Cadastros → Etapas/Componentes*: ajuste a matriz curricular, se necessário.
4. *Usuários*: cadastre gestores (vinculados à escola) e professores.
5. *Turmas*: crie as turmas (marque várias etapas para multisseriada) e faça a lotação dos professores.
6. *Alunos / Matrículas*: cadastre e matricule os alunos.

## Estrutura

```
index.php          front controller (rotas ?r=modulo/acao)
login-google.php   callback OAuth
app/core.php       banco (PDO), sessão, CSRF, permissões, helpers
app/diario.php     regras do diário (períodos, frequência, médias)
app/pages/         telas por módulo
app/views/         layouts (sistema, login, impressão)
assets/            CSS/JS/ícone
install/           instalador + schema.sql + seed.sql
```

## Segurança

Consultas com prepared statements (PDO), token CSRF em todos os formulários, senhas com `password_hash`, sessão com cookie `HttpOnly`/`SameSite`, cabeçalhos de segurança, auditoria de ações e bloqueio de acesso direto a `app/`, `config.php` e arquivos `.sql`.

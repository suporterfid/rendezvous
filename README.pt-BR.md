🇧🇷 [Leia em Português](README.pt-BR.md) (esta página) | 🇺🇸 [English](README.md)

# Rendezvous 📅

> **Plataforma Multi-Tenant de Código Aberto para Unificação de Calendários (Calendar Mash-up)**  
> Agrega eventos de múltiplas fontes (feeds ICS assinados, CalDAV somente leitura, APIs OAuth 2.0 Graph e convites de reunião encaminhados por e-mail via IMAP) em um único calendário unificado por tenant e republica como um feed assinável `webcal://`.

---

> 🍵 **Parte da Iniciativa IROH** (*It Runs On Hostinger*)  
> Este projeto é um software de código aberto e auto-hospedado construído para hospedagem compartilhada tradicional.  
> Saiba mais no hub central: [suporterfid/iroh](https://github.com/suporterfid/iroh) | [Manifesto](https://github.com/suporterfid/iroh/blob/main/MANIFESTO.md) | [Índice de Projetos](https://github.com/suporterfid/iroh/blob/main/PROJECTS.md) | [Especificação de Marca](https://github.com/suporterfid/iroh/blob/main/BRANDING.md)

---

## 🌟 Visão Geral

O **Rendezvous** é uma plataforma auto-hospedada e leve de consolidação de calendários, projetada para rodar perfeitamente em hospedagens web compartilhadas tradicionais (como Hostinger, cPanel, Apache/Nginx e PHP 8.2+). Ele resolve um problema corporativo muito comum: quando uma organização restringe a federação ou delegação nativa de calendários, os usuários podem simplesmente encaminhar e-mails de convite de reunião para uma caixa de entrada dedicada. O Rendezvous analisa os anexos MIME `text/calendar` via IMAP, normaliza os eventos, mescla-os com feeds externos e gera um feed `webcal://` limpo e assinável.

Como aplicação central da **Iniciativa IROH** ("It Runs On Hostinger"), o Rendezvous abraça a filosofia de que softwares sofisticados não exigem clusters VPS caros, setups complexos de Kubernetes ou daemons pesados em memória. Com SQLite ou MySQL, uma tarefa cron de 1 minuto e PHP padrão, o Rendezvous oferece agregação de calendários de nível empresarial com overhead operacional mínimo e zero custo mensal de infraestrutura.

---

## 🍵 Parte da Iniciativa IROH

O Rendezvous faz parte da **Iniciativa IROH** — um ecossistema de ferramentas auto-hospedadas e leves adaptadas para ambientes de hospedagem web compartilhada tradicional.

- **Meta-Repositório Central:** [suporterfid/iroh](https://github.com/suporterfid/iroh)
- **Manifesto:** Leia nossa filosofia central em [MANIFESTO.md](https://github.com/suporterfid/iroh/blob/main/MANIFESTO.md).
- **Índice de Projetos:** Descubra todos os projetos irmãos em [PROJECTS.md](https://github.com/suporterfid/iroh/blob/main/PROJECTS.md).
- **Identidade Visual e Marca:** Explore as diretrizes de design visual em [BRANDING.md](https://github.com/suporterfid/iroh/blob/main/BRANDING.md).

---

## 🌟 Principais Funcionalidades

- **📧 Ingestão de Convites de Reunião Encaminhados por E-mail:** Polling em caixas IMAP, extração de anexos `.ics` / `text/calendar` via `sabre/vobject` e reconciliação automática de atualizações de reuniões.
- **🔄 Reconciliação de Eventos e Idempotência:** Desduplicação de eventos baseada nos números `UID` + `SEQUENCE`. Trata `METHOD:CANCEL` sem deletar o histórico e atualiza status de participantes em `METHOD:REPLY`.
- **🌐 Conformidade com Outlook 365 e Google Workspace:** Exporta feeds `webcal://` limpos formatados como um único bloco `VCALENDAR` com cabeçalhos `X-WR-CALNAME` e `X-WR-TIMEZONE`.
- **🛡️ Segurança Multi-Tenant e Proteção SSRF:** Isolamento estrito de banco de dados por `tenant_id`, criptografia de segredos em repouso via AES-256, redação automática de logs e proteção SSRF bloqueando faixas de IP privadas (`127.0.0.1`, `10.x`, `192.168.x`, `169.254.169.254`).
- **📱 Interface Responsiva e i18n Duplo:** Frontend moderno em Vue 3 + Tailwind CSS suportando visualizações Mês, Semana, Dia e Agenda com internacionalização completa em Inglês (`en`) e Português do Brasil (`pt-BR`).
- **🔌 Conexões do Portfólio:** Pontos de integração opcionais com GrandpaSSOn (SSO / PKCE), TaskConnect (Webhooks HMAC auxiliares) e Jotter (Notas de Reunião em Markdown via MCP JSON-RPC).
- **🚀 Ambiente Local 100% Dockerizado:** Ciclo de desenvolvimento local completo com PHP 8.2-FPM, Nginx, MySQL 8, Mailpit (dublê de testes IMAP/SMTP) e servidor dev Vite em Node 20.

---

## 🛠️ Stack Tecnológica e Requisitos

### Requisitos do Sistema
- **Runtime PHP:** PHP 8.2+ com PDO (`pdo_sqlite`, `pdo_mysql`, `imap`, `mbstring`, `zip`).
- **Suporte Duplo a Banco de Dados:** 
  - **SQLite:** Instalações pessoais zero-config e testes locais single-tenant.
  - **MySQL 8 / MariaDB:** Implantações multi-tenant em produção em hospedagem compartilhada.
- **Servidor Web:** Apache (com `mod_rewrite`), LiteSpeed ou Nginx.
- **Execução em Segundo Plano:** Cron do sistema padrão de 1 minuto (sem necessidade de daemons ou Supervisors).

### Desenvolvimento Containerizado com Docker (DX Local)
O Rendezvous possui um ambiente de desenvolvimento local 100% containerizado (`compose.yaml`):
- `web`: Servidor web Nginx
- `php`: Runtime PHP 8.2-FPM com módulos PDO & IMAP
- `frontend`: Servidor dev Node 20 / Vite
- `db`: Banco de dados MySQL 8
- `mailpit`: Dublê de testes IMAP/SMTP para simulação local de ingestão de e-mails

### Paridade Multiplataforma Windows e Linux
Todas as operações via CLI oferecem simetria completa entre Windows PowerShell (`.\scripts\rv.ps1`) e Linux/macOS Bash (`./scripts/rv.sh`):

| Ação | Windows PowerShell | Linux / macOS Bash |
| :--- | :--- | :--- |
| **Iniciar Containers** | `.\scripts\rv.ps1 up` | `./scripts/rv.sh up` |
| **Parar Containers** | `.\scripts\rv.ps1 down` | `./scripts/rv.sh down` |
| **Executar Suíte de Testes** | `.\scripts\rv.ps1 test` | `./scripts/rv.sh test` |
| **Comandos Artisan** | `.\scripts\rv.ps1 artisan ...` | `./scripts/rv.sh artisan ...` |
| **Empacotar ZIP de Release** | `.\scripts\rv.ps1 release` | `./scripts/rv.sh release` |

---

## 🎨 Frontend e Sistema de Design da UI

O Rendezvous cumpre rigorosamente a especificação de identidade visual da IROH inspirada no Notion e focada em conteúdo:
- **Contrato Semântico de Tokens CSS:** Construído com variáveis CSS (`:root`, `[data-theme='light']`, `[data-theme='dark']`) garantindo troca de temas perfeita sem valores de cor hardcoded.
- **Tipografia e Layout:** Utiliza tipografia `Inter`, navegação estruturada por barra lateral, breakpoints de 480/768/1024/1280px e uma medida de 1200px para exibições de dados (grade de calendário).
- **Acessibilidade e Contraste:** Conforme normas WCAG 2.2 AA com estados de foco explícitos, tamanhos mínimos de alvo ao toque (44x44px) e indicadores visuais de status que nunca dependem exclusivamente de cor.
- **Especificação de Marca:** Para detalhes sobre regras de identidade visual, consulte [BRANDING.md](https://github.com/suporterfid/iroh/blob/main/BRANDING.md) no repositório principal da IROH.

---

## 📦 Instalação em Hospedagem Compartilhada (Hostinger)

Implantar o Rendezvous na Hostinger ou em qualquer hospedagem compartilhada cPanel não exige pipelines remotos de CI/CD:

1. **Empacotar a Aplicação Localmente:**
   Dentro do seu ambiente Docker, execute:
   ```bash
   # Windows PowerShell
   .\scripts\rv.ps1 release

   # Linux / macOS
   ./scripts/rv.sh release
   ```
   Isso gera o arquivo `dist_release/rendezvous-release.zip` contendo os estáticos do frontend pré-compilados, dependências PHP de produção e regras de reescrita `.htaccess`.

2. **Enviar para a Hospedagem:**
   - Envie o arquivo `rendezvous-release.zip` para seu servidor web via FTP, SFTP ou Gerenciador de Arquivos da Hostinger.
   - Extraia o conteúdo no diretório de destino (ex.: `/public_html/` ou pasta de subdomínio `/public_html/calendar/`).

3. **Configurar o Ambiente (`.env`):**
   - Copie `.env.example` para `.env`.
   - Atualize as credenciais do banco de dados (`DB_CONNECTION=mysql`, `DB_HOST=localhost`, `DB_DATABASE=...`, `DB_USERNAME=...`, `DB_PASSWORD=...`).
   - Configure `APP_URL` com a URL do seu domínio.

4. **Executar Migrações de Banco de Dados:**
   Execute as migrações via SSH ou terminal web:
   ```bash
   php artisan migrate --force
   ```

5. **Configurar Tarefa Cron:**
   Adicione uma tarefa cron de 1 minuto no hPanel da Hostinger ou cPanel:
   ```cron
   * * * * * /usr/bin/php /path/to/public_html/artisan calendar:ingest-mailboxes >/dev/null 2>&1
   */10 * * * * /usr/bin/php /path/to/public_html/artisan calendar:refresh-feeds >/dev/null 2>&1
   ```

---

## 💻 Uso e Comandos CLI

Após instalado, gerencie a plataforma utilizando os comandos Artisan integrados ou os scripts dos containers:

```bash
# Ingerir convites de reunião a partir de caixas IMAP
php artisan calendar:ingest-mailboxes

# Atualizar feeds ICS/CalDAV externos assinados
php artisan calendar:refresh-feeds

# Executar suíte de testes automatizados no Docker
.\scripts\rv.ps1 test
```

### Endpoints Disponíveis
- **Aplicação Web:** `http://localhost:8080`
- **API de Check de Saúde:** `http://localhost:8080/api/v1/platform/health`
- **Interface do Mailpit (Dublê IMAP/SMTP):** `http://localhost:8025`

---

## 🔐 Autenticação e Single Sign-On (SSO)

O Rendezvous integra-se perfeitamente com o **GrandpaSSOn** como seu Provedor de Identidade e Single Sign-On central:
- **Protocolos de Autenticação:** OpenID Connect (OIDC), OAuth2 Authorization Code Flow com PKCE e tokens JWT assinados.
- **Contexto Multi-Tenant:** Herda automaticamente a participação em tenants e declarações de autorização a partir dos tokens do GrandpaSSOn.
- **Hub Central de SSO:** Saiba mais ou implante serviços de identidade em [suporterfid/grandpasson-ce](https://github.com/suporterfid/grandpasson-ce).

---

## 🔗 Integração com Outros Projetos IROH

O Rendezvous colabora com outras ferramentas no ecossistema da Iniciativa IROH através de conexões desacopladas e opcionais:

* 🔐 **[grandpasson](https://github.com/suporterfid/grandpasson-ce)** — Provedor de Identidade Single Sign-On (SSO) central para autenticação multi-tenant perfeita.
* 📝 **[jotter](https://github.com/suporterfid/jotter)** — Base de conhecimento em Markdown auto-hospedada; conecta notas de reunião a eventos de calendário via MCP JSON-RPC somente leitura.
* 📄 **[ConvertDocToMd-PHP](https://github.com/suporterfid/ConvertDocToMd-PHP)** — Conversor leve em PHP de DOC/DOCX/PDF para Markdown para pré-processamento de anexos.
* ⚡ **[taskconnect](https://github.com/suporterfid/taskconnect)** — Agendador de tarefas HTTP multi-tenant para disparos auxiliares via cron e webhooks HMAC.
* 🟢 **[statusconnect](https://github.com/suporterfid/statusconnect)** — Monitoramento de uptime e página pública de status para acompanhamento de disponibilidade da plataforma.
* 🔢 **[tallymark](https://github.com/suporterfid/tallymark)** — Contador minimalista de eventos para acompanhamento de métricas de ingestão de convites.

---

## 🤝 Contribuição

Contribuições são muito bem-vindas! Por favor, revise nosso guia local [`CONTRIBUTING.md`](CONTRIBUTING.md) antes de enviar pull requests.

A governança global da iniciativa, diretrizes arquiteturais e regras de comunidade são coordenadas no meta-repositório principal: [suporterfid/iroh CONTRIBUTING.md](https://github.com/suporterfid/iroh/blob/main/CONTRIBUTING.md).

---

## 📜 Licença

O Rendezvous é um software de código aberto licenciado sob a [Licença MIT](LICENSE).

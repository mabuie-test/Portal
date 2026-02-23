# Crash Portal PHP 8.x (MVC) - Manual Deposit + Provably Fair

Plataforma web modular para LAMP/LEMP com jogos crash, carteira interna, depósito manual por transferência externa e auditoria completa, com `composer.json` incluído no projeto.

## Estrutura

- `src/` aplicação MVC e serviços
- `public/` front controller, SSE fallback, assets públicos
- `config/` configurações dos 10 jogos
- `migrations/` SQL idempotente MySQL/MariaDB
- `tests/` PHPUnit (Unit, Integration, E2E)
- `scripts/` workers, websocket, reconciliação e empacotamento
- `docs/` API, CSV reconciliação, templates legais
- `logs/` logs operacionais

## Instalação

```bash
cd portal
composer install
cp .env.example .env
```

Configurar `.env` com credenciais DB, `APP_KEY` e `MASTER_SEED_KEY`.

## Chave mestra de seed

Gerar fora do repositório:
```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```
Guardar em cofre seguro (Vault/KMS/HSM). Nunca commitar.

## Base de dados e migration

```bash
mysql -u root -p crash_portal < migrations/001_init.sql
```

## Executar aplicação

```bash
composer serve
```

## WebSocket (Ratchet) e fallback

- Endpoint WS planejado em `scripts/start_websocket.php`
- Fallback SSE em `public/sse.php`
- Long polling pode ser adicionado seguindo mesma API de eventos.

## Workers, filas e motor de rounds

```bash
composer rounds
```

Esse processo cria rounds concorrentes por jogo ativo, persiste `rounds` e `provably_proofs` para auditoria.

## Workers e filas

```bash
composer worker
```

Recomendado em produção via `supervisord` com restart automático e backoff.

## Testes

```bash
composer test
```

## Teste de carga simplificado

Exemplo `wrk`:
```bash
wrk -t4 -c40 -d30s http://localhost:8080/api/round/preview?game=aviator
```

Exemplo multi-processo PHP:
```bash
php -r 'for($i=0;$i<100;$i++){echo file_get_contents("http://localhost:8080/api/round/preview?game=rocket");}'
```

## Reconciliação CSV

```bash
php scripts/reconcile_csv.php storage/reconciliation/sample_statement.csv
```

## Melhorias recentes de jogos

- Implementado **Duelo de Dados** com dado azul e branco, apostas por soma/comparação/paridade e prova justa por seed/hmac.
- Implementada **Roda da Sorte** com odds na roda (inclui x0.5 até x5.0), prova justa por seed/hmac e animação dedicada.
- Aplicada aposta mínima global de **5 MTS** para jogos monetários.
- Painel administrativo inicial com endpoints para utilizadores, submissões pendentes, verificação/rejeição de depósitos, alternância de jogos e relatório financeiro.

- Interface visual com cards de jogos e imagens (`aviator.svg`, `coin.svg`) + feed SSE contínuo de rounds em execução.
- Painel de aposta estilo profissional com saldo, seleção rápida de stake (chips), ações ½/2x/limpar e botões de auto-cashout.
- Lobby com seleção de jogo e abertura em página dedicada (`/game.html?game=...`), com vetor/arte animável e áudio temático por jogo.
- Novo mini-jogo **Cara ou Coroa justo** com animação da moeda e retorno de prova (`seed/hash/hmac`).

## Conta e perfil

- Conta do utilizador com registo (nome, email único, telefone único, data de nascimento 18+), login e recuperação de senha por email.
- ID de jogador fixo em formato `00001` gerado no registo e exibido no perfil; suporte a avatar de utilizador.
- Perfil com preferências não críticas e endpoints para histórico de apostas e levantamentos.

## Segurança implementada

- Argon2id para passwords
- Token CSRF em formulários
- Prepared statements (PDO)
- Sessões seguras (httponly, strict mode)
- CSP no front controller
- Logs de auditoria admin (`audit_logs`)
- Criptografia de `server_seed_plain` com chave mestra
- RBAC com `ROLE_USER`, `ROLE_SUPPORT`, `ROLE_ADMIN`
- Base para MFA TOTP em `users.totp_secret`

## Fluxo de depósito manual

1. Utilizador transfere via M-Pesa/Paybill/banco fora do site.
2. Submete referência, telefone, montante e comprovativo.
3. Estado fica `pending` em `payment_submissions`.
4. SUPPORT/ADMIN valida manualmente.
5. Crédito atómico: cria `transactions` + atualiza `wallets`.
6. Ação registrada em `audit_logs`.

## Operação admin (manual não técnico)

- Ver painel de submissões pendentes.
- Abrir comprovativo anexado.
- Comparar com extrato CSV reconciliado.
- Aprovar em lote quando confiança alta.
- Rejeitar com justificativa obrigatória.
- Monitorar dashboard de filas/jobs e segurança.

## Compliance / legal

- Implementar KYC/AML conforme jurisdição local.
- Apostas com dinheiro real podem exigir licença.
- Consultar assessoria legal antes de operação comercial.

## Backup e restore MySQL

Backup:
```bash
mysqldump -u root -p crash_portal > backup.sql
```
Restore:
```bash
mysql -u root -p crash_portal < backup.sql
```

## Deploy (resumo)

1. Provisionar servidor PHP 8.2+, Nginx/Apache, MariaDB/MySQL.
2. Configurar HTTPS/TLS com Let's Encrypt + HSTS.
3. Definir permissões restritas em `.env`, `logs/`, `storage/`.
4. Executar migrations.
5. Subir web app, websocket e workers.
6. Configurar cron:
   - rotação de seeds
   - limpeza de logs
   - reconciliações agendadas
7. Validar checklist de pré-produção e segurança.

## Rollback e contingência

- Deploy blue/green com versão anterior pronta.
- Em falha, apontar tráfego para versão anterior.
- Reprocessar jobs pendentes da fila.
- Restaurar backup DB mais recente quando necessário.

## Empacotamento ZIP

```bash
composer package
```

Gera um pacote em `var/dist/` (não versionado), pronto para distribuição.

## Templates e documentação adicional

- `docs/api/curl_examples.md`
- `docs/api/csv_format.md`
- `docs/legal/terms_template.md`
- `docs/legal/privacy_template.md`
- `docs/legal/how_it_works.md`

# Exemplos de API (curl)

## Registo
```bash
curl -X POST http://localhost:8080/api/register \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.com","password":"Segura@123","full_name":"Nome"}'
```

## Obter CSRF
```bash
curl http://localhost:8080/api/csrf
```

## Listar jogos ativos
```bash
curl http://localhost:8080/api/games
```

## Criar round
```bash
curl -X POST http://localhost:8080/api/rounds \
  -H 'Content-Type: application/json' \
  -d '{"game":"aviator","nonce":25,"client_seed":"cliente-a"}'
```

## Submeter depósito manual
```bash
curl -X POST http://localhost:8080/api/deposits/manual \
  -H 'Content-Type: application/json' \
  -d '{"csrf_token":"TOKEN","user_id":1,"amount":1500,"phone":"25884XXXXXXX","reference":"MP12345"}'
```

## Criar aposta
```bash
curl -X POST http://localhost:8080/api/bets \
  -H 'Content-Type: application/json' \
  -d '{"round_id":1001,"user_id":1,"amount":50,"auto_cashout":2.0}'
```

## Cashout
```bash
curl -X POST http://localhost:8080/api/bets/cashout \
  -H 'Content-Type: application/json' \
  -d '{"bet_id":2001,"multiplier":2.35}'
```

## Consultar prova de um round
```bash
curl 'http://localhost:8080/api/rounds/proof?round_id=1001'
```

## Listar rounds de um jogo
```bash
curl 'http://localhost:8080/api/rounds?game_id=1'
```


## Jogar cara ou coroa
```bash
curl -X POST http://localhost:8080/api/coinflip/play \
  -H 'Content-Type: application/json' \
  -d '{"user_id":1,"amount":50,"choice":"cara","client_seed":"abc"}'
```

## Criar conta (18+)
```bash
curl -X POST http://localhost:8080/api/account/register \
  -H 'Content-Type: application/json' \
  -d '{"full_name":"Joao Silva","email":"joao@example.com","phone":"258840000000","birth_date":"2000-06-20","password":"Segura@123"}'
```

## Login
```bash
curl -X POST http://localhost:8080/api/account/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"joao@example.com","password":"Segura@123"}'
```

## Recuperação de senha por email
```bash
curl -X POST http://localhost:8080/api/account/password/request-reset \
  -H 'Content-Type: application/json' \
  -d '{"email":"joao@example.com"}'
```

## Perfil, histórico de apostas e levantamentos
```bash
curl 'http://localhost:8080/api/account/profile?user_id=1'
curl 'http://localhost:8080/api/account/bets/history?user_id=1'
curl 'http://localhost:8080/api/account/withdrawals/history?user_id=1'
```


## Jogar roda da sorte (min 5 MTS)
```bash
curl -X POST http://localhost:8080/api/wheel/play \
  -H 'Content-Type: application/json' \
  -d '{"user_id":1,"amount":25,"client_seed":"xyz"}'
```

## Admin: relatório financeiro
```bash
curl http://localhost:8080/api/admin/reports/financial
```


## Jogar duelo de dados (min 5 MTS)
```bash
curl -X POST http://localhost:8080/api/dice-duel/play \
  -H 'Content-Type: application/json' \
  -d '{"user_id":1,"amount":30,"bet_type":"compare","selection":"blue_gt_white"}'
```

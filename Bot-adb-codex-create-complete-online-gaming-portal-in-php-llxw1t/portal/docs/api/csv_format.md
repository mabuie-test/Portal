# Formato CSV de reconciliação

Cabeçalhos esperados:

```csv
date,reference,phone,amount,currency,provider,status
2026-01-10 11:00,MP123,258840000000,1500.00,MZN,m-pesa,success
```

Executar:
```bash
php scripts/reconcile_csv.php storage/reconciliation/sample_statement.csv
```

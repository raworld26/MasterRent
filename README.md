# MasterRent - Fase 1 (Sviluppo Tradizionale)

Questa è la **Fase 1** del progetto MasterRent. In questa fase il codice è stato sviluppato con un approccio tradizionale e manuale, basato su PHP procedurale e architettura base.

## Caratteristiche della Fase 1
- Sviluppo in PHP procedurale.
- Logica e configurazioni situate principalmente nella cartella `includes/`.
- Database utilizzato: `masterrent`.

## Come Avviare (Fase 1)
Importare gli script SQL in ordine numerico e avviare il server PHP integrato:

```bash
# Import SQL
sql/000_database.sql
sql/001_auth_schema.sql
sql/002_auth_seed.sql
sql/003_laquila_geo_schema.sql
sql/004_laquila_geo_seed.sql
sql/005_laquila_geo_services.sql
sql/006_properties_schema.sql
sql/007_properties_seed.sql
sql/008_engagement_schema.sql
sql/009_engagement_seed.sql
sql/010_my_house_flow.sql

# Avvio server
git checkout phase1
cd C:\Users\Ospite\Desktop\MasterRent
php -S 127.0.0.1:8000 -t public
```
Il portale è quindi raggiungibile su **http://127.0.0.1:8000/**.
### Account Demo
Puoi accedere con i seguenti account dimostrativi (Password per tutti: `Admin123!`):
   - **Admin:** `admin@masterrent.it`
   - **OdO (Offerente):** `odo@masterrent.it`
   - **Studente:** `studente@masterrent.it`

*Per la documentazione completa e il confronto con la Fase 2, torna sul branch `main` e consulta la cartella `docs/`.*

# MasterRent - Fase 2 (Sviluppo Assistito da LLM)

Questa è la **Fase 2** del progetto MasterRent. In questa fase il codice della Fase 1 è stato rifattorizzato, riorganizzato e ampliato tramite l'utilizzo intensivo di modelli linguistici di grandi dimensioni (LLM).

## Caratteristiche della Fase 2
- Architettura migliorata con un pattern MVC semplificato.
- Separazione della logica di accesso ai dati (vedi cartella `src/Repository`).
- Adozione di un Design System coerente e moderno.
- Database aggiornato e rinominato in: `uniaffitti`.

## Come Avviare (Fase 2)
Importare gli script SQL in ordine numerico e avviare il server PHP integrato:

```bash
# Import SQL
sql/00_database.sql
sql/01_auth_schema.sql
sql/02_geo_schema.sql
sql/03_properties_schema.sql
sql/04_auth_seed.sql
sql/05_geo_seed.sql
sql/06_demo_seed.sql
sql/07_engagement_schema.sql
sql/08_demo_engagement.sql
sql/09_my_house_flow.sql

# Avvio server
git checkout phase2
cd C:\Users\Ospite\Desktop\MasterRent
php -S 127.0.0.1:8000 -t public
```
Il portale è quindi raggiungibile su **http://127.0.0.1:8000/**.

### Account Demo
Puoi accedere con i seguenti account dimostrativi (Password per tutti: `Admin123!`):
   - **Admin:** `admin@masterrent.it`
   - **OdO (Offerente):** `odo@masterrent.it`
   - **Studente:** `studente@masterrent.it`

*Per la documentazione completa, il diario di sviluppo LLM e la relazione comparativa, torna sul branch `main` e consulta la cartella `docs/`.*
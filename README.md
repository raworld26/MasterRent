# MasterRent

MasterRent è un portale web dedicato alla gestione di affitti universitari nella città di L'Aquila. Questo progetto è stato sviluppato in due fasi distinte, documentate all'interno di questo repository.

## Struttura del Progetto: Le Due Fasi

Il progetto è suddiviso in due branch principali, che rappresentano l'evoluzione dello sviluppo:

- **Fase 1 (`phase1`)**: Sviluppo tradizionale/manuale. Realizzato in PHP procedurale con logica mista a presentazione (directory `includes/`). Il database utilizzato in questa fase è `masterrent`.
- **Fase 2 (`phase2`)**: Sviluppo assistito da LLM (Large Language Models). Il codice è stato rifattorizzato seguendo un pattern MVC semplificato (directory `src/Repository`), introducendo un design system strutturato. Il database utilizzato è `uniaffitti`.

## Come Avviare il Progetto

**Prerequisiti generali:**
- Web Server (es. XAMPP, MAMP, o PHP built-in server)
- PHP 8.2 o superiore
- Database MySQL o MariaDB

### Avviare la Fase 1
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


### Avviare la Fase 2
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
Per entrambe le fasi puoi accedere con i seguenti account dimostrativi (password per tutti: `Admin123!`):
- **Admin**: `admin@masterrent.it`
- **OdO (Offerente)**: `odo@masterrent.it`
- **Studente**: `studente@masterrent.it`

## Documentazione

Nella cartella `docs/` di questo branch (`main`) troverai tutta la documentazione di progetto, tra cui:
- `DocTW.pdf` - Relazione e documentazione ufficiale del progetto
- `RELAZIONE_COMPARATIVA.md` - Confronto tra la Fase 1 e la Fase 2
- `DIARIO_SVILUPPO.md` - Diario di sviluppo
- `ER_DIAGRAM` - Diagrammi Entità-Relazione (formati png e md)
- `LOG_PROMPT.md` - Log dei prompt utilizzati e ragionamenti con gli LLM
- `prompt_screenshots/` - Screenshots delle sessioni LLM

---
**Team e Contesto:** Progetto sviluppato per il corso universitario Tecnologie Web.
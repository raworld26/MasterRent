# MasteRent - Fase 1

Versione Fase 1 del progetto Tecnologie del Web.

Questa branch mantiene volutamente un front-end semplice: CSS unico, tabelle
HTML e form base. La Fase 2 moderna e assistita da LLM e' conservata su
`phase2`.

## Stack

- PHP 8.x
- MySQL/MariaDB
- HTML/CSS
- JavaScript vanilla
- `template2.inc.php`

Nessun framework.

## Import database

Con XAMPP avviato, importare gli script in questo ordine:

```text
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
sql/010_phase1_completion.sql
```

Il database usato e' `masterrent`.

## Utenti demo

Password per tutti: `Admin123!`

| Ruolo | Email |
| --- | --- |
| Admin | `admin@uniaffitti.local` |
| Proprietario | `odo@uniaffitti.local` |
| Proprietario | `laura@uniaffitti.local` |
| Studente | `studente@uniaffitti.local` |
| Studente | `giulia@uniaffitti.local` |
| Studente | `luca@uniaffitti.local` |

## Esecuzione locale affiancata

Usare due working copy e due porte diverse:

- Fase 1: `C:\Users\marin\Desktop\MasterRentPRIVATE`, branch `phase1`, database `masterrent`, URL `http://127.0.0.1:8000/`.
- Fase 2: `C:\Users\marin\Desktop\MasterRentPHASE2`, branch `phase2`, database `uniaffitti`, URL `http://127.0.0.1:8002/`.

Comandi manuali equivalenti:

```powershell
cd C:\Users\marin\Desktop\MasterRentPRIVATE
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public

cd C:\Users\marin\Desktop\MasterRentPHASE2
C:\xampp\php\php.exe -S 127.0.0.1:8002 -t public
```

## Deliverable

- Applicazione completa e installabile localmente.
- Database `masterrent` con 17 tabelle.
- Diagramma ER elettronico: `docs/ER_DIAGRAM.md`.

## Fette funzionali

- Autenticazione e sessioni.
- Profilo account con aggiornamento dati e cambio password.
- Modello users-groups-services.
- CRUD admin per utenti, gruppi, servizi.
- CRUD admin per annunci/alloggi.
- CRUD admin per immagini annuncio.
- CRUD admin per stanze/posti letto.
- CRUD admin per distanze tra annunci e poli universitari.
- CRUD admin per richieste visita.
- CRUD admin per accessori stanza.
- CRUD admin per recensioni studenti.
- CRUD admin per quartieri e poli didattici.
- Area proprietario per inserimento immobili e stanze.
- Engagement: preferiti, richieste visita, thread messaggi e recensioni demo.
- Completamento flusso Fase 1: registrazione pubblica, stati richiesta estesi,
  pagamento caparra simulato, prenotazione stanza e strumenti proprietario.

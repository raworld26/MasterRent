# MasterRent - Diario di sviluppo

Corso: Tecnologie del Web
Gruppo: Ramondo Mattia (291659), Odoardi Davide (292216), Marinucci Alessandro
(261682)

> Documentazione tecnica completa: [`LIBRO_DOCUMENTAZIONE_MasteRent.md`](LIBRO_DOCUMENTAZIONE_MasteRent.md) (cap. 29).

Questo diario consolida il materiale presente nella repository, il log Git e i
documenti di Fase 1/Fase 2. La cronologia e' ricostruita dai commit, dagli SQL,
dai file di progetto e dai documenti gia' presenti. Dove un dettaglio deriva da
ricostruzione del gruppo e non da prova automatica, viene trattato come nota di
diario.

## Sintesi delle fasi

| Fase | Branch | Metodo | Database | Struttura prevalente |
| --- | --- | --- | --- | --- |
| Fase 1 | `phase1` | Sviluppo tradizionale/manuale | `masterrent` | `includes/`, `public/`, `templates/`, CSS unico |
| Fase 2 | `phase2` | Sviluppo assistito da LLM | `uniaffitti` | `src/Repository`, template separati, CSS modulare, JS vanilla |

## Diario cronologico Fase 1

| Data / periodo | Attivita | Evidenza repository | Verifiche / note |
| --- | --- | --- | --- |
| 2026-05-12 | Inizializzazione progetto | Commit `e005e3a` | Struttura iniziale |
| 2026-05-14 | Organizzazione progetto, database/bootstrap/login | Commit `ae52f5a`, `19c03a6` | Avvio stack PHP/MySQL |
| 2026-05-23 | Pannello admin e area proprietario | Commit `e52faf4` | Prime aree riservate |
| 2026-07-03 | Baseline Fase 1 | Commit `7f7e9ae` | Costruzione base manuale |
| 2026-07-03 | Credenziali demo e semplificazione frontend | Commit `1df426f`, `68a0379` | UI tradizionale, credenziali demo |
| 2026-07-04 | Flussi e catalogo | Commit `1ec2aa9`, `2e6508c`, `fdb4c4f` | Ricerca, annunci, dati demo, gestione |
| 2026-07-05 | Moduli admin e diagramma Fase 1 | Commit `4a3cbef`, `2b303de` | Backend esteso e primo ER |
| 2026-07-07 | Allineamento funzionale a Fase 2 | Commit `c1bb65a`, `2fb2a89`, `63f13c8`, `e5ea6fe`, `a47e027`, `1919fcd` | Admin, routing, engagement, distanze manuali, vincolo una sola casa attuale |
| 2026-07-07 | Deliverable documentali | Commit `11aaeaa` | ER, relazione, diario, log dei commit |

### Attivita Fase 1

- progettazione dello schema dati e del modello `users - groups - services`;
- creazione degli script SQL `000`-`010`;
- implementazione autenticazione, registrazione, sessioni, CSRF e flash;
- sviluppo funzioni dominio in `includes/catalog.php`,
  `includes/engagement.php`, `includes/admin_data.php`;
- sviluppo frontend pubblico: home, ricerca, dettaglio stanza;
- sviluppo area studente, proprietario e admin;
- integrazione preferiti, richieste visita, messaggi, caparra simulata,
  recensioni e sezione "La mia casa";
- mantenimento di una UI piu' semplice e tradizionale.

### Cosa ha funzionato in Fase 1

- controllo diretto del dominio;
- comprensione delle relazioni tra schema, controller, template e permessi;
- maggiore trasparenza del codice procedurale;
- capacita' di verificare successivamente l'output degli LLM nella Fase 2.

### Cosa non ha funzionato / limiti Fase 1

- maggiore ripetizione nei CRUD;
- UI meno curata;
- codice meno separato rispetto al pattern Repository;
- piu' lavoro manuale per aggiornare permessi, query e template in modo
  coordinato.

## Diario cronologico Fase 2

La Fase 2 e' documentata nel worktree `C:\Users\Ospite\Desktop\MasterRentPhase2`
e nei file storici `DIARIO_SVILUPPO.md`, `PROMPT_LOG.md`, `SECURITY_AUDIT.md`,
`STATO_PROGETTO.md`, `docs/DESIGN_NOTES.md` e `docs/IMAGE_SOURCES.md`.

| Data / periodo | Attivita | Strumenti | Evidenza |
| --- | --- | --- | --- |
| 2026-06-30 | Fondamenta Fase 2, bootstrap, repository base, template frontend/backend, login/registrazione | Claude Code CLI, LLM | `DIARIO_SVILUPPO.md` Fase 2, screenshot PowerShell |
| 2026-07-01 | Foto reali/locali, seed demo, rifiniture UI, verifica end-to-end | Claude/Codex | Diario Fase 2, `docs/IMAGE_SOURCES.md` |
| 2026-07-01 | UI/UX avanzata: card, galleria, lightbox, toast, ricerca sticky/mobile | LLM + test manuale | `public/assets/js/`, `public/assets/css/` in `phase2` |
| 2026-07-02 | Redesign con design system a token, dark mode, stepper, componenti | Claude Code, Lovable come riferimento design | `docs/DESIGN_NOTES.md`, screenshot Claude |
| 2026-07-05 | Verifica requisiti, diagramma ER, materiali comparativi, admin annunci | LLM + revisione | Commit `15b722c`, `b313e01` |
| 2026-07-07 | Vincoli finali: una sola casa attuale, restituzione caparra, mappa quartieri dinamica, libera casa | Codex, Antigravity/Gemini | Commit `7248b2b`, `6efd9ac`, `d919f66`, screenshot Antigravity/Codex |

### Attivita Fase 2

- ricostruzione su database separato `uniaffitti`;
- introduzione di `src/Repository`;
- separazione template frontend/backend;
- design system CSS con token, componenti e dark mode;
- JavaScript vanilla per preferiti, ricerca, lightbox, toast e validazione;
- miglioramento flusso caparra/prenotazione con stepper e feedback UI;
- hardening upload immagini e permessi;
- audit sicurezza e documentazione.

### Strumenti usati

| Strumento | Uso documentato |
| --- | --- |
| Codex | Modifiche codice/UI, home, immagini, fix visuali, flusso "La mia casa" |
| Claude Code | Build a fette, repository, CRUD, restyling, verifiche |
| Lovable | Direzione/progettazione estetica della Fase 2, secondo log/documenti |
| Antigravity/Gemini | Correzioni dominio annunci, quartieri, vincoli su casa attuale |
| ChatGPT web | Generazione di prompt tecnici poi usati in Codex |

### Cosa ha funzionato in Fase 2

- generazione rapida di strutture ripetitive;
- maggiore coerenza visuale grazie a token/componenti;
- velocita' nel produrre varianti UI e correzioni;
- supporto nella stesura di checklist, audit e documentazione;
- possibilita' di confrontare velocemente Fase 1 e Fase 2.

### Cosa non ha funzionato / problemi LLM

- perdita di contesto su parti del progetto;
- confusione tra database `masterrent` e `uniaffitti`;
- esaurimento token su richieste troppo ampie;
- necessita' di spezzare i task;
- necessita' di revisione manuale su permessi, routing, nomi storici e scope.

### Correzioni manuali effettuate

- controllo dei riferimenti al database corretto per ciascuna fase;
- verifica che le route usassero i servizi corretti;
- controllo CSRF sulle POST state-changing;
- verifica dei flussi studente/proprietario/admin;
- correzioni a UI in dark mode, card, overlay, upload e menu;
- pulizia di codice orfano e documenti storici.

## Verifiche e test svolti

Verifiche documentate o ricostruite dai file:

- import SQL in ordine per Fase 1 e Fase 2;
- login con utenti demo per admin, proprietario e studente;
- navigazione home, ricerca e dettaglio stanza;
- creazione/gestione annunci e immagini;
- richiesta visita, approvazione/rifiuto, thread messaggi;
- pagamento caparra simulata e passaggio stanza a `reserved`;
- sezione "La mia casa" e rilascio stanza;
- recensioni solo dopo rapporto concluso;
- CRUD admin per utenti, gruppi, servizi, geografia, accessori, immobili,
  stanze e recensioni;
- `php -l` riportato nei documenti Fase 2 per i file toccati;
- verifica UI con screenshot e browser locale nelle sessioni LLM.

## Collegamenti agli screenshot e al prompt log

- Prompt log completo: [`LOG_PROMPT.md`](LOG_PROMPT.md)
- Indice screenshot: [`SCREENSHOT_INDEX.md`](SCREENSHOT_INDEX.md)
- Cartella screenshot: [`prompt_screenshots/`](prompt_screenshots/)
- Commit log reale: [`COMMIT_LOG.md`](COMMIT_LOG.md)

## Lezioni apprese

1. La Fase 1 ha dato al gruppo la base tecnica per valutare il codice generato
   nella Fase 2.
2. Gli LLM hanno accelerato refactoring, UI e documentazione, ma non hanno
   eliminato la necessita' di progettazione e test.
3. I task grandi vanno spezzati e controllati con verifiche concrete.
4. Il confronto tra branch, SQL e log Git e' essenziale per non documentare
   funzionalita' non presenti.

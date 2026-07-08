# MasterRent - Relazione comparativa

Corso: Tecnologie del Web
Gruppo: Ramondo Mattia (291659), Odoardi Davide (292216), Marinucci Alessandro
(261682)

> Documentazione tecnica completa e illustrata: [`LIBRO_DOCUMENTAZIONE_MasteRent.md`](LIBRO_DOCUMENTAZIONE_MasteRent.md).

## 1. Executive Summary

MasterRent e' un portale web per affitti universitari a L'Aquila. Il progetto e'
stato sviluppato in due fasi:

- **Fase 1**, branch `phase1`: sviluppo tradizionale/manuale, PHP procedurale,
  database `masterrent`, cartella `includes/`, template semplici e CSS unico;
- **Fase 2**, branch `phase2`: sviluppo assistito da LLM, database
  `uniaffitti`, repository layer in `src/Repository`, design system, JavaScript
  vanilla e UI piu' moderna.

Il confronto mostra un risultato pragmatico: la Fase 1 ha costruito la
comprensione del dominio e delle regole; la Fase 2 ha accelerato
riorganizzazione, rifinitura UI, documentazione e correzioni, ma ha richiesto
controllo umano costante. La Fase 2 non e' stata una sostituzione del lavoro
umano: e' stata efficace perche' il gruppo aveva gia' implementato e capito la
base manuale.

## 2. Descrizione dell'applicazione

L'applicazione gestisce il ciclo di ricerca e prenotazione di stanze per
studenti universitari:

- lo studente cerca stanze per zona, polo, prezzo, tipologia e accessori;
- puo' salvare preferiti, richiedere una visita, scrivere al proprietario e
  pagare una caparra simulata;
- il proprietario pubblica immobili e stanze, carica immagini, gestisce
  distanze e richieste;
- l'amministratore gestisce utenti, gruppi, servizi, quartieri, poli, accessori,
  annunci, stanze, prenotazioni e recensioni.

Il requisito `users - groups - services` e' implementato con le tabelle
`users`, `user_groups`, `services`, `users_has_groups` e
`services_has_groups`. L'autorizzazione avviene tramite `require_service()`, non
tramite un solo campo ruolo.

Il database corrente contiene 18 tabelle in entrambe le fasi. Il modello ER
finale e' documentato in [`ER_DIAGRAM.md`](ER_DIAGRAM.md).

## 3. Confronto del processo di sviluppo

| Dimensione | Fase 1 | Fase 2 |
| --- | --- | --- |
| Approccio | Manuale/tradizionale | Assistito da LLM |
| Obiettivo | Costruire una prima versione completa e comprensibile | Ricostruire/migliorare mantenendo parita' funzionale |
| Organizzazione codice | `includes/`, funzioni procedurali | `src/Repository`, helper, componenti |
| UI | Essenziale, form e pagine server-side | Design system, dark mode, interazioni vanilla |
| Rischio principale | Lentezza e ripetizione manuale | Output apparentemente corretto ma da verificare |
| Verifica | Test manuali sui flussi | Test manuali + audit + correzioni guidate |

La Fase 1 ha richiesto di progettare direttamente schema, permessi,
autenticazione, CRUD e flussi. Questa fase ha reso esplicite le relazioni tra
SQL, controller, template e permessi.

La Fase 2 ha usato strumenti LLM documentati in `LOG_PROMPT.md`: Codex, Claude
Code, Antigravity/Gemini, Lovable e ChatGPT web come supporto alla redazione di
prompt. Gli strumenti sono stati usati per refactoring, UI, completamento
funzionalita, prompt engineering e correzione di bug. La modalita' e' rimasta
iterativa: leggere, generare/modificare, testare, correggere.

## 4. Analisi della qualità del codice

### Fase 1

La qualita' della Fase 1 e' coerente con un'applicazione didattica
tradizionale:

- accesso dati e logica in `includes/catalog.php`, `includes/engagement.php` e
  `includes/admin_data.php`;
- autenticazione e permessi in `includes/auth.php` e `includes/permissions.php`;
- sicurezza condivisa in `includes/security.php`;
- controller espliciti in `public/`;
- template in `templates/`;
- CSS in `public/assets/css/style.css`.

Il codice e' diretto e leggibile, ma meno separato: molte operazioni sono
raccolte in funzioni procedurali o nei controller.

### Fase 2

La Fase 2 migliora soprattutto struttura e riuso:

- `src/Repository/BookingRepository.php` incapsula creazione richiesta,
  approvazione, pagamento caparra e rilascio stanza;
- `src/Repository/RoomRepository.php` incapsula ricerca e CRUD stanze;
- `src/Repository/UserRepository.php`, `GroupRepository.php`,
  `ServiceRepository.php` coprono il modello autorizzativo;
- `src/components.php` e i partial in `templates/frontend/_components/`
  riducono duplicazioni UI;
- `public/assets/css/tokens.css` e i file in `components/` separano design token
  e componenti;
- `public/assets/js/` contiene funzioni vanilla per preferiti, filtri, lightbox,
  toast e validazione.

### Sicurezza

In entrambe le fasi risultano presenti:

- hash password con bcrypt;
- query preparate PDO;
- escaping output con `htmlspecialchars`;
- CSRF sulle POST principali;
- protezione rotte con login e servizi;
- soft-delete utenti tramite `deleted_at`;
- transazioni su flussi critici.

La Fase 2 documenta un audit piu' esplicito in `SECURITY_AUDIT.md` e rafforza
upload immagini, logout POST, controlli IDOR e validazioni.

## 5. Confronto delle funzionalità

| Funzionalita | Fase 1 | Fase 2 | Valutazione |
| --- | --- | --- | --- |
| Login/registrazione | Presente | Presente | Parita' |
| Users-groups-services | Presente | Presente | Parita' |
| Ricerca stanze | Filtri server-side | Filtri + UI interattiva | Migliorata in Fase 2 |
| Dettaglio stanza | Informazioni, richiesta, recensioni | Galleria, stepper, pannello prenotazione | Migliorata in Fase 2 |
| Preferiti | Form/server-side | Endpoint/toggle JS e pagina dedicata | Parita' funzionale, UI migliore |
| Richiesta visita | Presente | Presente | Parita' |
| Messaggi/thread | Presente | Presente | Parita' |
| Caparra simulata | Presente nel flusso allineato | Presente con stepper UI | Parita' funzionale |
| La mia casa | Presente | Presente | Parita' |
| Area proprietario | CRUD e gestione richieste | Gestione piu' integrata | Migliorata in Fase 2 |
| Area admin | CRUD completi | CRUD + UI backend piu' ordinata | Migliorata in Fase 2 |
| Recensioni | Regola ex-inquilino/moderazione | Regola ex-inquilino/moderazione | Parita' |
| Dark mode | Assente | Presente | Aggiunta in Fase 2 |

Le differenze principali non sono nel dominio, ma nell'organizzazione e nella
presentazione: `includes/` contro `src/Repository`, CSS unico contro design
system, form server-side contro JS vanilla progressivo.

## 6. Analisi dell'interazione con gli LLM

Il log prompt consolidato distingue prompt documentati da screenshot e prompt
ricostruiti a memoria.

Strumenti documentati:

- Codex: correzioni UI, contenuti, immagini, flusso "La mia casa" e rifiniture;
- Claude Code: costruzione a fette, repository, CRUD, restyling e verifiche;
- Antigravity/Gemini: correzioni dominio annunci, quartieri e vincoli;
- Lovable: supporto alla direzione estetica;
- ChatGPT web: generazione di prompt tecnici poi incollati in Codex.

Problemi riscontrati:

- perdita di contesto su informazioni di progetto;
- confusione tra database `masterrent` e `uniaffitti`;
- esaurimento token su task lunghi;
- necessita' di spezzare le richieste;
- necessita' di test manuale e revisione umana dopo ogni output.

Esempi concreti dalla repo:

- la Fase 2 introduce `src/Repository`, ma la logica e' stata confrontata con
  `includes/engagement.php` e `includes/catalog.php` della Fase 1;
- il flusso caparra e' stato verificato nelle funzioni/repository che aggiornano
  `bookings`, `booking_status_history` e `rooms.status`;
- il redesign e' documentato da screenshot Claude/Codex e dai file CSS a token;
- i prompt correttivi documentano errori di contesto e tabelle obsolete.

## 7. Impegno e produttività

Il diario e la relazione storica indicano una stima del gruppo: Fase 1 circa una
settimana, Fase 2 circa tre giorni. Questa e' una stima di lavoro, non una
misurazione automatica.

Gli LLM hanno dato vantaggio su:

- generazione di CRUD ripetitivi;
- riorganizzazione in repository;
- produzione/raffinamento CSS;
- generazione di componenti UI;
- checklist e audit;
- documentazione e prompt log.

Hanno introdotto costo su:

- revisione completa dell'output;
- controllo permessi e ownership;
- correzione dei riferimenti a database/nomi storici;
- verifica che la Fase 2 non riducesse lo scope della Fase 1;
- gestione di output incompleti per limite token.

Il guadagno di produttivita' e' stato reale ma condizionato: senza test manuale,
il rischio era accettare codice solo apparentemente corretto.

## 8. Riflessione critica

La Fase 1 ha avuto valore formativo centrale. Scrivere manualmente schema SQL,
permessi, autenticazione, CRUD e flussi ha permesso al gruppo di capire dove
controllare l'output degli LLM.

La Fase 2 ha mostrato che l'LLM e' utile quando il gruppo sa formulare vincoli
precisi:

- stack obbligatorio: PHP, MySQL/MariaDB, HTML/CSS, JS vanilla, `template2`;
- nessun framework;
- parita' funzionale con Fase 1;
- database separati;
- permessi basati su services;
- nessun dato di pagamento reale.

Il punto critico e' stato distinguere una UI convincente da una soluzione
realmente conforme ai requisiti. L'esempio piu' chiaro e' la necessita' di
verificare manualmente routing, permessi, prenotazioni e database: il modello
poteva generare codice plausibile ma non sempre coerente con lo stato reale
della repository.

La conclusione metodologica e' che lo sviluppo assistito funziona meglio come
workflow ibrido: il gruppo progetta, vincola e valida; l'LLM accelera parti
ripetitive o complesse da riscrivere, ma non sostituisce la responsabilita'
tecnica.

## 9. Conclusioni

MasterRent dimostra due risultati:

1. lo sviluppo manuale e' piu' lento, ma crea comprensione e controllo;
2. lo sviluppo assistito da LLM e' piu' veloce su refactoring, UI e
   documentazione, ma richiede verifica rigorosa.

Nel progetto, la Fase 2 e' stata efficace perche' fondata sulla Fase 1. Senza la
base manuale, sarebbe stato piu' difficile riconoscere errori di contesto,
riduzioni di scope o incoerenze tra codice, SQL e documentazione.

Il messaggio finale e' quindi operativo: l'LLM e' un acceleratore, non un
sostituto delle competenze Web. Il valore si ottiene quando il gruppo conosce il
dominio, impone vincoli, testa i flussi e corregge criticamente l'output.

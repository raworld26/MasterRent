# MasterRent - Log prompt

> Parte del corpus documentale di MasterRent. Documentazione completa: [`LIBRO_DOCUMENTAZIONE_MasteRent.md`](LIBRO_DOCUMENTAZIONE_MasteRent.md) (cap. 30). Indice immagini: [`SCREENSHOT_INDEX.md`](SCREENSHOT_INDEX.md).

Questo documento consolida il materiale gia' presente in repository. Non
cancella il contenuto esistente: lo riorganizza separando prompt documentati da
screenshot e prompt ricostruiti a memoria.

Screenshot indicizzati in [`SCREENSHOT_INDEX.md`](SCREENSHOT_INDEX.md). Le
immagini sono in [`prompt_screenshots/`](prompt_screenshots/).

## Strumenti e modelli indicati nei log

| Strumento | Modello/ambiente indicato | Uso |
| --- | --- | --- |
| Codex | GPT 5.5, secondo screenshot/log del gruppo | Modifiche codice, UI, immagini, branding, flussi |
| Claude Code CLI | Opus 4.8, secondo screenshot/log del gruppo | Build a fette, repository, CRUD, verifiche |
| Claude Code app | Fable 5, secondo screenshot/log del gruppo | Restyling frontend e design system |
| Antigravity | Gemini 3.1 Pro, secondo screenshot/log del gruppo | Correzioni dominio, quartieri, vincoli |
| ChatGPT web | GPT, modello non sempre specificato | Meta-uso: generazione prompt per Codex |
| Lovable | Non indicato nei file come modello | Direzione estetica/prototipazione UI |

## A. Prompt documentati da screenshot - Claude Code CLI

### A1 - Contesto iniziale Fase 2

Screenshot:

- [`powershell_HlZTT3VuUB.png`](prompt_screenshots/powershell_HlZTT3VuUB.png)
- [`powershell_D2bILoG1Tc.png`](prompt_screenshots/powershell_D2bILoG1Tc.png)
- [`powershell_TFOcWYopQQ.png`](prompt_screenshots/powershell_TFOcWYopQQ.png)
- [`powershell_DQPnaiwG6o.png`](prompt_screenshots/powershell_DQPnaiwG6o.png)

Scopo:

- leggere specifica e repository;
- ricostruire la Fase 2 su branch separato;
- rispettare stack PHP/MySQL/HTML/CSS/JS vanilla e `template2.inc.php`;
- mantenere parita' funzionale con Fase 1;
- introdurre repository layer e template frontend/backend.

Esito:

- piano a fette;
- decisioni su design, registrazione, upload e architettura;
- creazione struttura `src/`, `src/Repository`, `templates/frontend`,
  `templates/backend`;
- database separato `uniaffitti`.

Correzioni successive:

- fix di path/base URL su Windows;
- verifica di import DB e login admin;
- separazione del database `masterrent`/`uniaffitti`.

### A2 - Prosecuzione build

Screenshot:

- [`powershell_WzJBcLCuBJ.png`](prompt_screenshots/powershell_WzJBcLCuBJ.png)

Scopo:

- completare il progetto e renderlo funzionante;
- popolare dati;
- chiudere CRUD, ricerca, richieste, messaggi e recensioni.

Esito:

- avanzamento fette funzionali;
- engagement schema;
- flussi visit request, approvazione, caparra e messaggi;
- dati demo e verifiche.

Correzioni successive:

- controllo dei bug Windows e del calcolo `BASE_URL`;
- reimport DB pulito quando necessario;
- verifica del flusso end-to-end.

## B. Prompt documentati da screenshot - Claude Code app

### B1 - Restyling completo

Screenshot:

- [`claude_cUuWHAnKEu.png`](prompt_screenshots/claude_cUuWHAnKEu.png)
- [`claude_JPXqlGmB0T.png`](prompt_screenshots/claude_JPXqlGmB0T.png)
- [`claude_uoPpgugehP.png`](prompt_screenshots/claude_uoPpgugehP.png)

Scopo:

- trasformare l'interfaccia in un marketplace moderno per affitti universitari;
- migliorare card, home, ricerca, dettaglio stanza, login, backend;
- produrre design system coerente;
- verificare flussi e rischi.

Esito:

- CSS modulare a token;
- componenti badge, card, form, table, toast, skeleton, stepper, lightbox;
- dark mode;
- template frontend/backend piu' curati;
- `docs/DESIGN_NOTES.md` nella Fase 2.

Correzioni successive:

- adeguamenti a contrasto dark mode;
- fix responsive;
- controllo di copertura CSS e pagine principali.

## C. Prompt documentati da screenshot - Codex

| ID | Screenshot | Scopo | Esito / correzioni |
| --- | --- | --- | --- |
| C1 | [`Codex_BFV9YJ8fd8.png`](prompt_screenshots/Codex_BFV9YJ8fd8.png) | Home page e branding | Revisione home e naming storico |
| C2 | [`Codex_nAxnjpJLYG.png`](prompt_screenshots/Codex_nAxnjpJLYG.png) | Popolare annunci con foto locali | Foto da `Case/` e seed/asset coerenti |
| C3 | [`Codex_7NeLtS5nJo.png`](prompt_screenshots/Codex_7NeLtS5nJo.png) | Upload foto, skeleton, centratura e dark mode | Fix UI puntuali |
| C4 | [`Codex_Arjul3TTx8.png`](prompt_screenshots/Codex_Arjul3TTx8.png) | Rimuovere immagine di sfondo | Home piu' pulita |
| C5 | [`Codex_yHf5DpxZto.png`](prompt_screenshots/Codex_yHf5DpxZto.png) | Sovrapposizione icone/menu | Fix z-index/layout |
| C6 | [`Codex_H4udwhhuKo.png`](prompt_screenshots/Codex_H4udwhhuKo.png), [`Codex_Eocmo1555O.png`](prompt_screenshots/Codex_Eocmo1555O.png) | Flusso "La mia casa" e dominio affitti | Sezione casa attuale, disdetta/rilascio |
| C7 | [`Codex_fmoOyhJ4Ix.png`](prompt_screenshots/Codex_fmoOyhJ4Ix.png) | Sessione lunga di lavoro/verifica | Rifiniture e verifica manuale |

Correzioni successive:

- normalizzazione naming in documentazione;
- controllo manuale di route, permessi e DB;
- mantenimento parita' funzionale con Fase 1.

## D. Prompt documentati da screenshot - Antigravity/Gemini

| ID | Screenshot | Scopo | Esito / correzioni |
| --- | --- | --- | --- |
| D1 | [`Antigravity_TefBbmnw0V.png`](prompt_screenshots/Antigravity_TefBbmnw0V.png) | Considerare le case come stanze per universitari, prezzi 200-350 euro | Revisione contenuti annunci |
| D2 | [`Antigravity_eQ6t6u0USo.png`](prompt_screenshots/Antigravity_eQ6t6u0USo.png) | Rivedere titolo/descrizione annuncio | Titoli piu' coerenti |
| D3 | [`Antigravity_Z4yIEYmkB4.png`](prompt_screenshots/Antigravity_Z4yIEYmkB4.png) | Togliere via dal titolo | Indirizzo separato dal titolo |
| D4 | [`Antigravity_KOvvVR4Rt1.png`](prompt_screenshots/Antigravity_KOvvVR4Rt1.png) | Aggiornare quartieri | Lista zone piu' aderente a L'Aquila |
| D5 | [`Antigravity_7lnRxDfmrK.png`](prompt_screenshots/Antigravity_7lnRxDfmrK.png) | Una sola casa attuale per studente | Vincolo su prenotazione/caparra se gia' presente casa attiva |

Correzioni successive:

- verifica dei valori nei seed;
- controllo della funzione/Repository per casa attiva;
- test dei flussi prenotazione e disdetta.

## E. Prompt documentati da screenshot - ChatGPT web

ChatGPT web e' stato usato come strumento di meta-prompting: il gruppo chiedeva
un prompt tecnico, poi lo usava in Codex.

| ID | Screenshot | Scopo | Esito |
| --- | --- | --- | --- |
| E1 | [`chrome_6k8SVhV3jq.png`](prompt_screenshots/chrome_6k8SVhV3jq.png) | Prompt per "La mia casa" e rilascio stanza | Prompt operativo per Codex |
| E2 | [`chrome_GK6MpyS4Yt.png`](prompt_screenshots/chrome_GK6MpyS4Yt.png) | Prompt per distanze opzionali e prezzo in creazione | Prompt operativo per Codex |
| E3 | [`chrome_hot5gZwr3g.png`](prompt_screenshots/chrome_hot5gZwr3g.png) | Prompt per recensioni solo ex-inquilini | Prompt operativo per Codex |
| E4 | [`chrome_WE6822dLZe.png`](prompt_screenshots/chrome_WE6822dLZe.png) | Descrizione per camera doppia | Contenuto testuale per annuncio |

## F. Prompt ricostruiti a memoria

I prompt seguenti erano gia' separati nel materiale esistente come
ricostruzioni a memoria. Non hanno screenshot dedicato e vanno quindi citati
come non verificabili direttamente dalle immagini.

### F1 - Costruzione a fette

Categorie:

- CRUD admin per utenti, gruppi e servizi;
- registrazione studente/proprietario;
- ricerca con filtri;
- dettaglio stanza e distanze;
- flusso richiesta visita;
- caparra simulata atomica;
- sezione "La mia casa";
- controllo CSRF sulle POST.

Esito:

- costruzione progressiva dei moduli;
- allineamento con requisiti del corso;
- necessita' di test manuali dopo ogni fetta.

### F2 - Rifiniture frontend e contenuti

Categorie:

- preferiti nel dettaglio stanza;
- contrasti dark mode;
- card responsive;
- pagina crediti immagini;
- recensioni e contatori in home.

Esito:

- miglioramento della UI;
- correzioni puntuali dopo verifica visuale.

### F3 - Correzioni dominio e regole

Categorie:

- prezzi coerenti per stanze universitarie;
- recensione solo dopo soggiorno;
- proprietario non deve prenotare/recensire i propri annunci.

Esito:

- regole verificate in codice e flussi;
- necessita' di controllo manuale su permessi e ownership.

### F4 - Correzioni dopo problemi LLM

Problemi documentati:

- database sbagliato (`masterrent` vs `uniaffitti`);
- risposta interrotta per limite token;
- riferimento a tabelle obsolete (`booking_requests`) invece del modello
  corrente (`bookings`, `booking_status_history`, `messages`).

Mitigazione:

- task piu' piccoli;
- prompt correttivi espliciti;
- confronto con SQL reale;
- test manuale prima di accettare modifiche.

## Osservazioni finali

Il log mostra un workflow multi-strumento. Gli LLM sono stati utili per
accelerare refactoring, UI, documentazione e correzioni, ma i problemi di
contesto hanno richiesto revisione umana continua. La Fase 1 manuale e' stata
la base per riconoscere errori e controllare che la Fase 2 restasse conforme al
dominio e alla specifica.

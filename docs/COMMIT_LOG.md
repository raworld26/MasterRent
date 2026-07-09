# MasterRent - Log commit reale

Documento generato dal log Git reale della repository. Non riscrive la storia e
non attribuisce commit individuali se Git non lo mostra.

Comandi usati come base:

```powershell
git branch --all --verbose --no-abbrev
git log --all --date=iso-strict --pretty=format:"%h %H %ad %an %D %s" --decorate
git branch --all --contains <hash>
```

## Panoramica branch

| Branch/ref | Commit tip | Stato osservato |
| --- | --- | --- |
| `main` / `origin/main` | `9478d1e` | base condivisa storica, "Fix frontend images and dark CTA" |
| `phase1` / `origin/phase1` | `40e6229` | branch corrente, Fase 1 tradizionale con documentazione finale gia' avviata |
| `phase2` / `origin/phase2` | `d919f66` | worktree separato `C:\Users\Ospite\Desktop\MasterRentPhase2`, Fase 2 assistita da LLM |
| `refs/stash` | `f9ab738` | stash tecnico su `main`, non conteggiato come commit di consegna |

## Commit principali

| Hash | Data | Autore Git | Branch/ref contenente | Messaggio |
| --- | --- | --- | --- | --- |
| `e005e3a` | 2026-05-12 | `odoardy` | base comune | Initial commit |
| `ae52f5a` | 2026-05-14 | `odoardy` | base comune | Organizzazione struttura progetto |
| `19c03a6` | 2026-05-14 | `odoardy` | base comune | Modificati database.php, bootstrap.php, login.php |
| `e52faf4` | 2026-05-23 | `Mattia` | base comune | Pannello admin e area proprietario |
| `4f68a02` | 2026-06-30 | `Mattia` | `main`, `phase2` | Revisione |
| `8b640c3` | 2026-07-01 | `Mattia` | `main`, `phase2` | interfaccia |
| `6c5df5b` | 2026-07-01 | `Mattia` | `main`, `phase2` | Restyling |
| `b14f77e` | 2026-07-01 | `Mattia` | `main`, `phase2` | Barra di ricerca |
| `a14448c` | 2026-07-02 | `Mattia` | `main`, `phase2` | Restyling marketplace e nuovi annunci |
| `29bfa09` | 2026-07-02 | `master` | `main`, `phase2` | Polish frontend form controls |
| `8725d4a` | 2026-07-02 | `master` | `main`, `phase2` | Equalize landlord listing cards |
| `e46a04b` | 2026-07-02 | `Mattia` | `main`, `phase2` | Polish MasteRent frontend |
| `914c467` | 2026-07-02 | `Mattia` | `main`, `phase2` | Case aggiunte |
| `5e4964e` | 2026-07-02 | `Mattia` | `main`, `phase2` | Mappa |
| `9741eca` | 2026-07-02 | `Mattia` | `main`, `phase2` | feat: aggiunte stime distanze universitarie e stato progetto |
| `9478d1e` | 2026-07-03 | `master` | `main`, `phase2` | Fix frontend images and dark CTA |
| `7f7e9ae` | 2026-07-03 | `master` | `phase1` | Build phase1 baseline |
| `1df426f` | 2026-07-03 | `master` | `phase1` | Align phase1 demo credentials |
| `68a0379` | 2026-07-03 | `master` | `phase1` | Simplify phase1 frontend |
| `1ec2aa9` | 2026-07-04 | `master` | `phase1` | Improve phase1 flows and frontend |
| `2e6508c` | 2026-07-04 | `master` | `phase1` | Sync phase1 listings with phase2 |
| `fdb4c4f` | 2026-07-04 | `master` | `phase1` | Completa gestione fase 1 |
| `4a3cbef` | 2026-07-05 | `master` | `phase1` | Completa moduli admin fase 1 |
| `2b283ec` | 2026-07-05 | `master` | `phase2` | Restrict favorites to student accounts |
| `15b722c` | 2026-07-05 | `master` | `phase2` | Complete phase 2 delivery requirements |
| `2b303de` | 2026-07-05 | `master` | `phase1` | Add phase 1 delivery diagram |
| `b313e01` | 2026-07-05 | `master` | `phase2` | Expand comparative report materials |
| `ce273a7` | 2026-07-07 | `master` | `phase1` | Polish landlord request actions |
| `c1bb65a` | 2026-07-07 | `Mattia` | `phase1` | feat: completo allineamento phase1 a phase2 (admin, routing, engagement) |
| `2fb2a89` | 2026-07-07 | `Mattia` | `phase1` | Fix syntax errors and missing variables in admin modules |
| `7248b2b` | 2026-07-07 | `Mattia` | `phase2` | Continua lavoro Codex: distanze automatiche fase 2 |
| `63f13c8` | 2026-07-07 | `Mattia` | `phase1` | Distanze manuali fase 1 |
| `e5ea6fe` | 2026-07-07 | `Mattia` | `phase1` | Impedisci prenotazione o versamento caparra se si ha gia' una casa attuale |
| `6efd9ac` | 2026-07-07 | `Mattia` | `phase2` | Impedisci prenotazione o versamento caparra se si ha gia' una casa attuale |
| `a47e027` | 2026-07-07 | `Mattia` | `phase1` | Implementazione richieste: restituzione caparra, libera casa e fix visualizzazione admin |
| `d919f66` | 2026-07-07 | `Mattia` | `phase2` | Implementazione richieste: restituzione caparra, mappa quartieri dinamica, libera casa e fix vari |
| `1919fcd` | 2026-07-07 | `Mattia` | `phase1` | Housekeeping: rimuovi codice orfano e morto, fix link admin recensioni |
| `11aaeaa` | 2026-07-07 | `Mattia` | `phase1` | docs: aggiorna ER e aggiungi deliverable finali (relazione, diario, piano commit) |
| `05e3183` | 2026-07-07 | `Mattia` | `phase1` | docs: aggiungi log prompt autentico (LOG_PROMPT.md) con screenshot delle sessioni LLM |
| `40e6229` | 2026-07-07 | `Mattia` | `phase1` | docs: estendi LOG_PROMPT con prompt ricostruiti a memoria (sez. F) |

## Sintesi per fase

Fase 1:

- parte da commit base comune di maggio 2026;
- la linea `phase1` entra in modo esplicito da `7f7e9ae`;
- include costruzione baseline, credenziali demo, frontend semplice, gestione,
  moduli admin, allineamento funzionale e documentazione.

Fase 2:

- condivide la storia iniziale fino a `9478d1e`;
- prosegue con interventi su UI, immagini, mappa, preferiti, requisito consegna,
  materiali comparativi e correzioni finali;
- tip corrente: `d919f66`.

## Nota metodologica

Il log reale mostra autori Git tecnici (`odoardy`, `Mattia`, `master`). Non
permette di attribuire in modo affidabile ogni commit ai tre componenti del
gruppo. Se il lavoro e' stato svolto in mob programming o con account condivisi,
va dichiarato cosi' nella consegna.

Il file [`PIANO_COMMIT.md`](PIANO_COMMIT.md), se usato, deve essere letto come
piano/ricostruzione organizzativa, non come log Git reale. Il log reale e'
quello riportato in questa pagina.

## Riferimenti tecnici non di consegna

`git log --all` mostra anche commit collegati a `refs/stash`:

- `f9ab738`: `On main: local db-port workaround + pdf`;
- `8cdec13`: `index on main: 9478d1e Fix frontend images and dark CTA`;
- `05f6a69`: `untracked files on main: 9478d1e Fix frontend images and dark CTA`.

Sono riferimenti tecnici dello stash e non devono essere presentati come commit
di sviluppo applicativo.

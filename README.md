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
1. Spostati sul branch dedicato:
   ```bash
   git checkout phase1
   ```
2. Crea un database MySQL chiamato `masterrent`.
3. Importa gli script SQL presenti nella cartella `sql/` in ordine sequenziale (da `000` a `010`). Assicurati di notare la porta del tuo DB (di default `3306`, su alcuni sistemi XAMPP `3307`).
4. Imposta la *Document Root* del tuo web server sulla cartella `public/` (oppure avvia il server PHP locale puntando alla directory `public/`).

### Avviare la Fase 2
1. Spostati sul branch dedicato:
   ```bash
   git checkout phase2
   ```
2. Crea un database MySQL chiamato `uniaffitti`.
3. Importa gli script SQL presenti nella cartella `sql/` in ordine sequenziale (da `00` a `09`).
4. Imposta la *Document Root* del tuo web server sulla cartella `public/` (oppure avvia il server PHP locale puntando alla directory `public/`).

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
- `ER_DIAGRAM` - Diagrammi Entità-Relazione (formato mmd, png, svg)
- `LOG_PROMPT.md` - Log dei prompt utilizzati e ragionamenti con gli LLM
- `prompt_screenshots/` - Screenshots delle sessioni LLM

---
**Team e Contesto:** Progetto sviluppato per il corso universitario Tecnologie Web.
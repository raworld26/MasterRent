# MasterRent - Fase 2 (Sviluppo Assistito da LLM)

> Progetto universitario di Tecnologie Web, realizzato in team e valutato **28/30**.


Questa è la **Fase 2** del progetto MasterRent. In questa fase il codice della Fase 1 è stato rifattorizzato, riorganizzato e ampliato tramite l'utilizzo intensivo di modelli linguistici di grandi dimensioni (LLM).

## Caratteristiche della Fase 2
- Architettura migliorata con un pattern MVC semplificato.
- Separazione della logica di accesso ai dati (vedi cartella `src/Repository`).
- Adozione di un Design System coerente e moderno.
- Database aggiornato e rinominato in: `uniaffitti`.

## Come Avviare (Fase 2)
1. Assicurati di avere un server web (es. XAMPP, MAMP) con PHP 8.2 e MariaDB/MySQL.
2. Crea un database chiamato `uniaffitti`.
3. Importa gli script SQL che trovi in `sql/` in ordine sequenziale (da `00` a `09`).
4. Punta la *Document Root* del tuo web server (o usa `php -S 127.0.0.1:8000 -t public`) alla cartella `public/` del progetto. Il portale sarà raggiungibile su **http://127.0.0.1:8000/**.
5. Accedi utilizzando gli account demo (Password per tutti: `Admin123!`):
   - **Admin:** `admin@uniaffitti.local`
   - **OdO (Offerente):** `odo@uniaffitti.local`
   - **Studente:** `studente@uniaffitti.local`

*Per la documentazione completa, il diario di sviluppo LLM e la relazione comparativa, torna sul branch `main` e consulta la cartella `docs/`.*

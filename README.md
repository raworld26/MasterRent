# MasterRent - Fase 1 (Sviluppo Tradizionale)

Questa è la **Fase 1** del progetto MasterRent. In questa fase il codice è stato sviluppato con un approccio tradizionale e manuale, basato su PHP procedurale e architettura base.

## Caratteristiche della Fase 1
- Sviluppo in PHP procedurale.
- Logica e configurazioni situate principalmente nella cartella `includes/`.
- Database utilizzato: `masterrent`.

## Come Avviare (Fase 1)
1. Assicurati di avere un server web (es. XAMPP, MAMP) con PHP 8.2 e MariaDB/MySQL.
2. Crea un database chiamato `masterrent`.
3. Importa gli script SQL che trovi in `sql/` in ordine sequenziale (da `000` a `010`). Fai attenzione alla porta del database (es. `3306` o `3307` a seconda della tua installazione locale).
4. Punta la *Document Root* del tuo web server (o usa `php -S localhost:8000`) alla cartella `public/` del progetto.
5. Accedi utilizzando gli account demo (Password per tutti: `Admin123!`):
   - **Admin:** `admin@uniaffitti.local`
   - **OdO (Offerente):** `odo@uniaffitti.local`
   - **Studente:** `studente@uniaffitti.local`

*Per la documentazione completa e il confronto con la Fase 2, torna sul branch `main` e consulta la cartella `docs/`.*

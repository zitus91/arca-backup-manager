# Changelog

Tutte le modifiche rilevanti a questo progetto vengono documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/it/1.1.0/)
e questo progetto aderisce al [Semantic Versioning](https://semver.org/lang/it/).

---

## [1.1.0] - 2026-03-01

### Aggiunto

#### Ripristino su Host Remoto
- **Selezione target**: possibilità di scegliere tra ripristino sullo stesso host di origine o su un host remoto diverso.
- **Configurazione remota MySQL**: host, porta, username e password per ripristinare database MySQL su server remoti.
- **Configurazione remota MongoDB**: host, porta, username, password e database di autenticazione per ripristinare database MongoDB su server remoti.
- **Configurazione remota Filesystem (SSH/rsync)**: host SSH, porta, utente e percorso chiave privata per trasferire file su server remoti via rsync.

#### Nomi Target Personalizzati
- **Nomi database editabili**: ogni database selezionato per il ripristino ha un campo di input per specificare il nome target (default: `<nome>_restored_<timestamp>`).
- **Percorsi filesystem editabili**: ogni percorso selezionato per il ripristino ha un campo di input per specificare il percorso di destinazione.
- **Reset nomi**: pulsante per reimpostare tutti i nomi ai valori predefiniti con suffisso `_restored_<timestamp>`.

#### Override (Sovrascrittura)
- **Opzione override**: toggle per sovrascrivere database/directory esistenti invece di crearne di nuovi.
- **Avvisi multipli**: pannello di avviso a 3 punti con spiegazione chiara dei rischi della sovrascrittura.
- **Drop automatico**: `DROP DATABASE IF EXISTS` per MySQL, `db.dropDatabase()` per MongoDB, `rm -rf` per filesystem prima del ripristino quando override è attivo.
- **Avviso nome uguale**: alert aggiuntivo quando il nome target coincide con l'originale e l'override è attivo.

#### Disclaimer Interattivo
- **Riepilogo dinamico**: disclaimer nel modale che mostra in tempo reale le mappature esatte (originale → target) per ogni database e percorso selezionato.
- **Tag contestuali**: badge `OVERRIDE`, `DROP IF EXISTS`, `RM IF EXISTS` visualizzati accanto alle mappature quando l'override è attivo.
- **Dettagli host remoto live**: quando il target è "host remoto", il disclaimer mostra i dettagli di connessione (user@host:porta) per MySQL, MongoDB e SSH aggiornati in tempo reale.
- **Colori adattivi**: il pannello di riepilogo cambia colore (warning → error) in base allo stato dell'override.
- **Binding Livewire live**: tutti i campi del modale (selezione target, nomi personalizzati, override, configurazione remota) utilizzano `wire:model.live` per aggiornamenti istantanei.

#### Step di Conferma
- **Doppia conferma**: step aggiuntivo di conferma prima dell'esecuzione che mostra target, override e dettagli completi.
- **Conferma con override**: quando l'override è attivo, la conferma mostra avvisi rossi prominenti.

### Modificato

- **`RestoreLog` model**: aggiunti campi `restore_target`, `remote_host_config` (encrypted), `custom_names`, `override_existing` a fillable e casts.
- **`ProcessRestoreJob`**: legge e utilizza le nuove configurazioni (target remoto, nomi custom, override) per ogni database/percorso da ripristinare.
- **`MysqlRestoreService::restore()`**: accetta parametri opzionali `$targetDbName` e `$overrideExisting`; aggiunto metodo `dropDatabaseIfExists()`.
- **`MongodbRestoreService::restore()`**: accetta parametri opzionali `$targetDbName` e `$overrideExisting`; aggiunto metodo `dropDatabaseIfExists()` via `mongosh`.
- **`FilesystemRestoreService::restore()`**: accetta parametri opzionali `$targetPath` e `$overrideExisting`; aggiunto metodo `restoreRemote()` con rsync over SSH.
- **`RestoreIndex` Livewire component**: nuove proprietà e metodi per gestire target, configurazione remota, nomi custom e override.
- **Modale di ripristino (Blade)**: completamente riscritto con sezioni per selezione target, configurazione remota, nomi editabili, toggle override e disclaimer dinamico. Modale allargato a `max-w-3xl` con scroll interno.
- **Traduzioni EN/IT**: aggiunte ~40 nuove chiavi per tutte le label, descrizioni, avvisi e messaggi di validazione della funzionalità di ripristino remoto.

### Database

- **Nuova migration** `2026_03_01_000001_add_remote_restore_columns_to_restore_logs_table.php`:
  - `restore_target` (string, default `same_host`)
  - `remote_host_config` (json, nullable) — criptato nel model
  - `custom_names` (json, nullable)
  - `override_existing` (boolean, default `false`)

---

## [1.0.0] - 2026-02-25

### Aggiunto

- Release iniziale di Backup Manager.
- **Backup MySQL**: dump completi con `mysqldump` (single transaction, routines, triggers).
- **Backup MongoDB**: dump con `mongodump`, supporto autenticazione e collections specifiche.
- **Backup Filesystem**: archiviazione di directory con pattern di esclusione.
- **Storage Destinations**: supporto locale, Amazon S3 (compatibile MinIO, DigitalOcean Spaces), FTP/SFTP.
- **Scheduling**: pianificazione manuale, oraria, giornaliera, settimanale, mensile, CRON custom con calcolo automatico del prossimo run.
- **Retention policy**: configurabile per job.
- **Ripristino**: selettivo (solo database, solo file, o completo) con suffisso `_restored_<timestamp>` non distruttivo.
- **Dashboard real-time**: statistiche, grafici successi/fallimenti, salute dei job.
- **Audit Log**: log completo di tutte le operazioni.
- **Notifiche WebSocket**: aggiornamenti live via Laravel Reverb.
- **Notifiche email**: configurabili per successo/fallimento.
- **Gestione utenti**: autenticazione, profilo, utenti multipli.
- **UI responsive**: Tailwind CSS 4 + DaisyUI 5.
- **Internazionalizzazione**: supporto italiano e inglese.
- **Docker**: Dockerfile e docker-compose.yml per deploy containerizzato.
- **Testing**: suite di test con Pest.

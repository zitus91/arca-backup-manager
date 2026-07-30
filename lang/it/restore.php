<?php

return [
    'title' => 'Restore',
    'subtitle' => 'Sfoglia i backup disponibili e ripristina database o file con suffisso "_restored".',

    // Tabs & sections
    'history' => 'Storico Restore',
    'available_backups' => 'Backup Disponibili',

    // Filters
    'filter_job' => 'Job Backup',
    'filter_date_from' => 'Da',
    'filter_date_to' => 'A',
    'all' => 'Tutti',

    // Table columns
    'col_job' => 'Job',
    'col_source' => 'Sorgente',
    'col_destination' => 'Destinazione',
    'col_types' => 'Tipi',
    'col_date' => 'Data Backup',
    'col_file' => 'File',
    'col_size' => 'Dimensione',
    'col_actions' => 'Azioni',
    'col_backup' => 'Backup',
    'col_type' => 'Tipo Restore',
    'col_status' => 'Stato',
    'col_restored_to' => 'Ripristinato In',
    'col_started' => 'Inizio',
    'col_duration' => 'Durata',
    'col_user' => 'Utente',

    // Restore types
    'type_full' => 'Completo',
    'type_db_only' => 'Solo Database',
    'type_files_only' => 'Solo File',
    'type_full_desc' => 'Ripristina DB e file',
    'type_db_only_desc' => 'Ripristina solo i database',
    'type_files_only_desc' => 'Ripristina solo i file',

    // Status
    'status_pending' => 'In Attesa',
    'status_running' => 'In Corso',
    'status_success' => 'Completato',
    'status_failed' => 'Fallito',

    // Buttons
    'restore_btn' => 'Ripristina',
    'cancel' => 'Annulla',
    'continue' => 'Continua',
    'back' => 'Indietro',
    'execute' => 'Esegui Restore',
    'close' => 'Chiudi',
    'detail' => 'Dettagli',

    // Modal
    'modal_title' => 'Ripristina Backup',
    'info_job' => 'Job Backup',
    'info_source' => 'Sorgente',
    'info_date' => 'Data Backup',
    'info_size' => 'Dimensione File',
    'backup_contains' => 'Contenuto del Backup',
    'select_type' => 'Cosa vuoi ripristinare?',

    // Granular selection
    'select_items_hint' => 'Seleziona gli elementi da ripristinare',
    'no_items_selected' => 'Seleziona almeno un database o un percorso da ripristinare.',
    'confirm_databases' => 'Database da ripristinare',
    'confirm_paths' => 'Percorsi da ripristinare',

    // Restore target
    'restore_target' => 'Destinazione Restore',
    'target_same_host' => 'Stesso Host',
    'target_same_host_desc' => 'Ripristina sullo stesso server di origine',
    'target_remote_host' => 'Host Remoto',
    'target_remote_host_desc' => 'Ripristina su un server diverso',
    'target_known_host' => 'Host Noto',
    'target_known_host_desc' => 'Usa le credenziali di un host registrato',
    'known_host_select' => 'Seleziona Host',
    'known_host_required' => 'Seleziona un host registrato su cui ripristinare.',
    'known_host_summary' => 'Credenziali prese dall\'host selezionato',

    // Remote config
    'remote_config' => 'Configurazione Host Remoto',
    'remote_mysql_config' => 'Connessione MySQL',
    'remote_postgres_config' => 'Connessione PostgreSQL',
    'remote_mongodb_config' => 'Connessione MongoDB',
    'remote_filesystem_config' => 'Connessione SSH (Filesystem)',
    'remote_host' => 'Host',
    'remote_port' => 'Porta',
    'remote_username' => 'Username',
    'remote_password' => 'Password',
    'remote_auth_database' => 'Database Auth',
    'remote_ssh_host' => 'Host SSH',
    'remote_ssh_port' => 'Porta SSH',
    'remote_ssh_user' => 'Utente SSH',
    'remote_ssh_key_path' => 'Percorso Chiave SSH',

    // Custom names
    'reset_names' => 'Ripristina nomi predefiniti',
    'custom_name_empty' => 'Il nome destinazione non può essere vuoto per gli elementi selezionati.',

    // Override
    'override_existing' => 'Sovrascrivi dati esistenti',
    'override_existing_desc' => 'Se un database o una directory con il nome destinazione esiste già, verrà completamente sostituito.',
    'override_warning_title' => 'Pericolo - Operazione Irreversibile',
    'override_warning_1' => 'I database esistenti con lo stesso nome verranno ELIMINATI e tutti i dati saranno persi permanentemente.',
    'override_warning_2' => 'Le directory esistenti verranno completamente cancellate prima del ripristino.',
    'override_warning_3' => 'Questa operazione NON PUÒ essere annullata. Assicurati di avere un backup dei dati attuali.',
    'override_same_name_warning' => 'Il database originale verrà sovrascritto!',
    'override_confirm_title' => 'AVVISO CRITICO',
    'override_confirm_desc' => 'Hai abilitato la modalità sovrascrittura. I database e/o le directory esistenti con i nomi destinazione verranno ELIMINATI PERMANENTEMENTE prima del restore. Questa azione è IRREVERSIBILE.',

    // Confirmation additions
    'confirm_target' => 'Destinazione Restore',
    'confirm_target_same' => 'Stesso Host (Server Originale)',
    'confirm_target_remote' => 'Host Remoto',
    'confirm_target_known' => 'Host Noto',
    'confirm_backup_source' => 'Sorgente del backup',
    'confirm_backup_storage' => 'Archiviato su',
    'confirm_override' => 'Modalità Sovrascrittura',
    'confirm_override_yes' => 'SÌ - I dati esistenti verranno sostituiti',
    'confirm_override_no' => 'No - Restore sicuro',

    // Validation
    'remote_mysql_required' => 'Inserisci i dettagli di connessione MySQL dell\'host remoto.',
    'remote_postgres_required' => 'Inserisci i dettagli di connessione PostgreSQL dell\'host remoto.',
    'remote_mongodb_required' => 'Inserisci i dettagli di connessione MongoDB dell\'host remoto.',
    'remote_filesystem_required' => 'Inserisci i dettagli di connessione SSH per il restore del filesystem.',

    // Warnings
    'warning_title' => 'Importante',
    'warning_db_header' => 'Database',
    'warning_fs_header' => 'Filesystem',
    'warning_override_tag' => 'Sovrascrittura!',
    'warning_drop_if_exists_tag' => 'Drop se esiste',
    'warning_rm_if_exists_tag' => 'Elimina se esiste',
    'warning_remote_target' => 'Il restore verrà eseguito sull\'host remoto configurato.',
    'warning_same_target' => 'Il restore verrà eseguito sullo stesso host di origine.',
    'warning_override_active' => '⚠ Modalità sovrascrittura attiva: i dati esistenti con lo stesso nome destinazione verranno distrutti permanentemente prima del restore.',

    // Confirmation
    'confirm_title' => 'Conferma Restore',
    'confirm_desc' => 'Sei sicuro di voler procedere con il ripristino? Questa operazione non può essere annullata.',

    // Detail modal
    'detail_title' => 'Dettagli Restore',
    'detail_finished' => 'Terminato',
    'detail_db' => 'Database Ripristinato',
    'detail_path' => 'Percorso Ripristinato',
    'detail_error' => 'Errore',
    'detail_meta' => 'Metadati',

    // Messages
    'restore_started' => 'Restore avviato. Riceverai una notifica al completamento.',
    'backup_not_available' => 'Questo backup non è disponibile per il restore.',
    'no_backups' => 'Nessun backup completato disponibile per il restore.',

    // Toast
    'toast_started' => 'Restore in corso...',
    'toast_success' => 'Restore completato con successo!',
    'toast_failed' => 'Restore fallito.',
];

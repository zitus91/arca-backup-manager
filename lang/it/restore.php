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

    // Warnings
    'warning_title' => 'Importante',
    'warning_desc' => 'I database verranno ripristinati con suffisso "_restored_TIMESTAMP" (es. mydb → mydb_restored_20260227_143000). I file verranno ripristinati nel percorso originale con lo stesso suffisso (es. /data/app → /data/app_restored_20260227_143000).',

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

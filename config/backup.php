<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Package Process Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum seconds allowed for packaging a backup into a single archive
    | and for extracting it again during restore. Large filesystem sources
    | may need this raised. Bounded in practice by the queue worker timeout.
    |
    */

    'process_timeout' => (int) env('BACKUP_PROCESS_TIMEOUT', 3600),

];

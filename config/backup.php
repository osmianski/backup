<?php

// The included file should contain an array of operations to run,
// each configuring an `App\Backup\Operation` instance.
// Typical setup is below.

return [
    [
        'key' => 'compress_dir',
        'source' => '~/projects/{project_name}',
        'target' => '~/backups/projects/{project_name}.tar.gz',
    ],
//    [
//        'key' => 'upload',
//        'source' => '~/{dir_name:archive|backup|Documents|programs|projects}',
//        'target' => 'drive:/dell23/{dir_name}',
//    ],
];

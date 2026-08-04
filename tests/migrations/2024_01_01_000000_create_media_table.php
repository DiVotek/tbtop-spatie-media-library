<?php

// spatie/laravel-medialibrary ships its migration as a .php.stub, which the
// Laravel migrator's file finder does not recognize as a single-file path
// (see Migrator::getMigrationFiles). Requiring it here gives testbench a
// real .php entry point without duplicating the schema.
return require __DIR__.'/../../vendor/spatie/laravel-medialibrary/database/migrations/create_media_table.php.stub';

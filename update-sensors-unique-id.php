<?php

/**
 * Script pour exécuter la migration et mettre à jour les capteurs existants
 * Usage: php update-sensors-unique-id.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "=== Mise à jour des capteurs avec unique_id ===\n\n";

try {
    // Exécuter la migration
    echo "1. Exécution de la migration...\n";
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();
    echo "✓ Migration exécutée avec succès\n\n";

    // Vérifier les capteurs
    echo "2. Vérification des capteurs...\n";
    $sensors = DB::table('sensors')->get();

    if ($sensors->isEmpty()) {
        echo "⚠ Aucun capteur trouvé dans la base de données\n";
    } else {
        echo "Capteurs trouvés:\n";
        foreach ($sensors as $sensor) {
            echo "  - ID: {$sensor->id}, Nom: {$sensor->name}, unique_id: {$sensor->unique_id}, Topic: {$sensor->mqtt_topic}\n";
        }
    }

    echo "\n✓ Mise à jour terminée avec succès!\n";

} catch (\Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test Configuration Queue ===\n\n";

// 1. Vérifier la configuration
echo "Configuration:\n";
echo "  QUEUE_CONNECTION: " . env('QUEUE_CONNECTION') . "\n";
echo "  BROADCAST_CONNECTION: " . env('BROADCAST_CONNECTION') . "\n";
echo "  DB_CONNECTION: " . env('DB_CONNECTION') . "\n\n";

// 2. Vérifier si la table jobs existe
echo "Vérification des tables:\n";
try {
    $jobsCount = DB::table('jobs')->count();
    echo "  ✅ Table 'jobs' existe ({$jobsCount} jobs en attente)\n";
} catch (\Exception $e) {
    echo "  ❌ Table 'jobs' n'existe pas ou erreur: " . $e->getMessage() . "\n";
    echo "  → Exécutez: php artisan migrate\n\n";
    exit(1);
}

try {
    $failedCount = DB::table('failed_jobs')->count();
    echo "  ✅ Table 'failed_jobs' existe ({$failedCount} jobs échoués)\n";
} catch (\Exception $e) {
    echo "  ❌ Table 'failed_jobs' n'existe pas\n";
}

echo "\n";

// 3. Afficher les jobs en attente
if ($jobsCount > 0) {
    echo "Jobs en attente:\n";
    $jobs = DB::table('jobs')->orderBy('id', 'desc')->limit(10)->get();
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $displayName = $payload['displayName'] ?? 'Unknown';
        echo "  - Job #{$job->id}: {$displayName}\n";
        echo "    Queue: {$job->queue}\n";
        echo "    Tentatives: {$job->attempts}\n";
        echo "    Créé: " . date('Y-m-d H:i:s', $job->created_at) . "\n\n";
    }
} else {
    echo "Aucun job en attente.\n\n";
}

// 4. Afficher les jobs échoués
if ($failedCount > 0) {
    echo "Jobs échoués récents:\n";
    $failed = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->limit(3)->get();
    foreach ($failed as $job) {
        echo "  - Job #{$job->id}: {$job->uuid}\n";
        echo "    Queue: {$job->queue}\n";
        echo "    Échoué le: {$job->failed_at}\n";
        echo "    Erreur: " . substr($job->exception, 0, 200) . "...\n\n";
    }
}

// 5. Instructions
echo "=== Instructions ===\n\n";
if ($jobsCount > 0) {
    echo "Des jobs sont en attente. Pour les traiter:\n";
    echo "  php artisan queue:work\n\n";
    echo "Ou pour traiter un seul job (test):\n";
    echo "  php artisan queue:work --once\n\n";
} else {
    echo "✅ Tout est configuré correctement!\n\n";
    echo "Pour tester le système complet:\n";
    echo "  1. Terminal 1: php artisan mqtt:listen\n";
    echo "  2. Terminal 2: php artisan queue:work\n";
    echo "  3. Terminal 3: php artisan reverb:start\n";
    echo "  4. MQTTX: Publier un message de test\n";
}

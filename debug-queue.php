<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Debug Queue System ===\n\n";

// 1. Config
echo "1. Configuration:\n";
echo "   QUEUE_CONNECTION: " . config('queue.default') . "\n";
echo "   BROADCAST_CONNECTION: " . config('broadcasting.default') . "\n";
echo "   DB_CONNECTION: " . config('database.default') . "\n\n";

// 2. Vérifier que la table jobs existe
echo "2. Vérification table 'jobs':\n";
try {
    $exists = DB::getSchemaBuilder()->hasTable('jobs');
    if ($exists) {
        echo "   ✅ Table 'jobs' existe\n";

        $jobsCount = DB::table('jobs')->count();
        echo "   Jobs en attente: {$jobsCount}\n";

        if ($jobsCount > 0) {
            echo "\n   📋 Détails des jobs:\n";
            $jobs = DB::table('jobs')->orderBy('id', 'asc')->get();
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                echo "   ---\n";
                echo "   ID: {$job->id}\n";
                echo "   Queue: {$job->queue}\n";
                echo "   Tentatives: {$job->attempts}\n";
                echo "   Job Class: {$payload['displayName']}\n";
                echo "   Disponible à: " . date('Y-m-d H:i:s', $job->available_at) . "\n";
                echo "   Créé le: " . date('Y-m-d H:i:s', $job->created_at) . "\n";

                // Décoder la commande complète
                if (isset($payload['data']['commandName'])) {
                    echo "   Command: {$payload['data']['commandName']}\n";
                }
            }
        }
    } else {
        echo "   ❌ Table 'jobs' n'existe PAS\n";
        echo "   → Exécutez: php artisan migrate\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Vérifier les failed jobs
echo "3. Vérification jobs échoués:\n";
try {
    $exists = DB::getSchemaBuilder()->hasTable('failed_jobs');
    if ($exists) {
        $failedCount = DB::table('failed_jobs')->count();
        echo "   Jobs échoués: {$failedCount}\n";

        if ($failedCount > 0) {
            echo "\n   ⚠️ Jobs échoués récents:\n";
            $failed = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->limit(3)->get();
            foreach ($failed as $job) {
                echo "   ---\n";
                echo "   UUID: {$job->uuid}\n";
                echo "   Queue: {$job->queue}\n";
                echo "   Échoué le: {$job->failed_at}\n";
                echo "   Exception: " . substr($job->exception, 0, 300) . "...\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Vérifier les sensor_events
echo "4. Vérification événements capteurs:\n";
try {
    $eventsCount = DB::table('sensor_events')->count();
    echo "   Total événements: {$eventsCount}\n";

    if ($eventsCount > 0) {
        $lastEvent = DB::table('sensor_events')
            ->orderBy('created_at', 'desc')
            ->first();
        echo "   Dernier événement:\n";
        echo "   - ID: {$lastEvent->id}\n";
        echo "   - Sensor ID: {$lastEvent->sensor_id}\n";
        echo "   - Status: {$lastEvent->status}\n";
        echo "   - Créé le: {$lastEvent->created_at}\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Test de création d'événement et de broadcast
echo "5. Test création événement + broadcast:\n";
try {
    // Trouver un capteur
    $sensor = DB::table('sensors')->first();

    if ($sensor) {
        echo "   Création d'un événement test...\n";

        $sensorEvent = \App\Models\SensorEvent::create([
            'sensor_id' => $sensor->id,
            'status' => 'open',
            'detected_at' => now(),
        ]);

        echo "   ✅ Événement créé (ID: {$sensorEvent->id})\n";

        echo "   Déclenchement du broadcast...\n";
        event(new \App\Events\SensorEventCreated($sensorEvent));

        echo "   ✅ Event déclenché\n";

        // Attendre un peu
        sleep(1);

        // Vérifier si un job a été créé
        $newJobsCount = DB::table('jobs')->count();
        echo "   Jobs en attente maintenant: {$newJobsCount}\n";

        if ($newJobsCount > 0) {
            echo "   ✅ Un job a été créé dans la queue!\n";
            echo "\n";
            echo "   👉 Exécutez maintenant: php artisan queue:work --once\n";
        } else {
            echo "   ⚠️ Aucun job créé. Le broadcast est peut-être synchrone.\n";
            echo "   Vérifiez que SensorEventCreated implements ShouldBroadcast\n";
        }

    } else {
        echo "   ⚠️ Aucun capteur trouvé en base\n";
        echo "   → Exécutez: php artisan db:seed\n";
    }

} catch (\Exception $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo "   " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fin du diagnostic ===\n";

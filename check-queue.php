<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Diagnostic Queue ===\n\n";

// Vérifier la connexion queue
echo "QUEUE_CONNECTION: " . env('QUEUE_CONNECTION') . "\n";
echo "BROADCAST_CONNECTION: " . env('BROADCAST_CONNECTION') . "\n\n";

// Vérifier si la table jobs existe
try {
    $jobsCount = DB::table('jobs')->count();
    echo "✅ Table 'jobs' existe\n";
    echo "Nombre de jobs en attente: {$jobsCount}\n\n";

    if ($jobsCount > 0) {
        echo "Jobs en attente:\n";
        $jobs = DB::table('jobs')->orderBy('id', 'desc')->limit(5)->get();
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            echo "  - Job #{$job->id}: {$payload['displayName']} (tentatives: {$job->attempts})\n";
            echo "    Queue: {$job->queue}\n";
            echo "    Créé le: " . date('Y-m-d H:i:s', $job->created_at) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Erreur table 'jobs': " . $e->getMessage() . "\n";
}

echo "\n";

// Vérifier les failed jobs
try {
    $failedCount = DB::table('failed_jobs')->count();
    echo "Jobs échoués: {$failedCount}\n";

    if ($failedCount > 0) {
        echo "\nJobs échoués:\n";
        $failed = DB::table('failed_jobs')->orderBy('id', 'desc')->limit(3)->get();
        foreach ($failed as $job) {
            echo "  - Job #{$job->id}: {$job->uuid}\n";
            echo "    Queue: {$job->queue}\n";
            echo "    Exception: " . substr($job->exception, 0, 200) . "...\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Erreur table 'failed_jobs': " . $e->getMessage() . "\n";
}

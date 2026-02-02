<?php

namespace App\Console\Commands;

use App\Services\OracleService;
use Illuminate\Console\Command;

class TestOracleConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oracle:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Teste la connexion à la base de données Oracle';

    /**
     * Execute the console command.
     */
    public function handle(OracleService $oracleService)
    {
        $this->info('🔍 Test de la connexion Oracle...');
        $this->newLine();

        // Test de connexion
        $this->info('1. Test de connexion...');
        $result = $oracleService->testConnection();
        
        if ($result['success']) {
            $this->info('   ✅ Connexion réussie !');
            $data = $result['data'];
            if (isset($data['message'])) {
                $this->line('   📝 Message: ' . $data['message']);
            }
            if (isset($data['result'])) {
                $this->line('   📊 Résultat: ' . $data['result']);
            }
        } else {
            $this->error('   ❌ Échec de la connexion');
            $this->error('   Erreur: ' . ($result['error'] ?? 'Inconnue'));
            $this->error('   Message: ' . ($result['message'] ?? 'Aucun message'));
            return 1;
        }

        $this->newLine();

        // Liste des tables
        $this->info('2. Récupération de la liste des tables...');
        $tablesResult = $oracleService->getTables();
        
        if ($tablesResult['success']) {
            $tables = $tablesResult['data']['tables'] ?? [];
            $count = $tablesResult['data']['count'] ?? count($tables);
            $this->info('   ✅ ' . $count . ' table(s) trouvée(s)');
            
            if (count($tables) > 0) {
                if (count($tables) <= 10) {
                    foreach ($tables as $table) {
                        $this->line('   - ' . $table);
                    }
                } else {
                    $this->line('   (Afficher les 10 premières tables)');
                    foreach (array_slice($tables, 0, 10) as $table) {
                        $this->line('   - ' . $table);
                    }
                    $this->line('   ... et ' . (count($tables) - 10) . ' autres');
                }
            } else {
                $this->warn('   ⚠️  Aucune table retournée');
                $this->line('   💡 Astuce: Le service Python doit être redémarré pour prendre en compte les modifications');
                $this->line('   💡 Exécutez: cd python-service && ./redemarrer.sh');
            }
        } else {
            $this->warn('   ⚠️  Impossible de récupérer les tables');
            $this->line('   Erreur: ' . ($tablesResult['error'] ?? 'Inconnue'));
            $this->line('   Message: ' . ($tablesResult['message'] ?? 'Aucun message'));
        }

        $this->newLine();
        $this->info('✅ Test terminé avec succès !');
        
        return 0;
    }
}

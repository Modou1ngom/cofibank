<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Territory;
use App\Services\OracleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAgenciesFromOracle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agencies:sync-from-oracle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les agences depuis Oracle vers la base de données Laravel';

    protected $oracleService;

    public function __construct(OracleService $oracleService)
    {
        parent::__construct();
        $this->oracleService = $oracleService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Début de la synchronisation des agences depuis Oracle...');

        try {
            // Récupérer les données clients depuis Oracle
            $result = $this->oracleService->getClientsData('month', null, date('n'), date('Y'));

            if (!$result['success']) {
                $this->error('❌ Erreur lors de la récupération des données Oracle: ' . ($result['message'] ?? 'Erreur inconnue'));
                return 1;
            }

            $data = $result['data'];
            $actualData = $data;
            if (isset($data['data']) && is_array($data['data'])) {
                $actualData = $data['data'];
            }

            // Debug: Afficher la structure des données
            $this->info("🔍 Structure des données reçues:");
            $this->info("   - Clés principales: " . implode(', ', array_keys($actualData)));
            if (isset($actualData['hierarchicalData'])) {
                $this->info("   - Clés hierarchicalData: " . implode(', ', array_keys($actualData['hierarchicalData'])));
                if (isset($actualData['hierarchicalData']['TERRITOIRE'])) {
                    $territories = $actualData['hierarchicalData']['TERRITOIRE'];
                    $this->info("   - Territoires trouvés: " . implode(', ', array_keys($territories)));
                    foreach ($territories as $territoryKey => $territory) {
                        $agenciesCount = 0;
                        if (isset($territory['agencies']) && is_array($territory['agencies'])) {
                            $agenciesCount = count($territory['agencies']);
                        } elseif (isset($territory['data']) && is_array($territory['data'])) {
                            $agenciesCount = count($territory['data']);
                        }
                        $this->info("     * {$territoryKey}: {$agenciesCount} agence(s)");
                    }
                }
            }

            // Extraire toutes les agences uniques
            $agencies = $this->extractAgencies($actualData);
            
            $this->info("📊 " . count($agencies) . " agence(s) unique(s) trouvée(s)");

            // Synchroniser les agences
            $synced = 0;
            $updated = 0;
            $errors = 0;

            foreach ($agencies as $agencyData) {
                try {
                    $agency = $this->syncAgency($agencyData);
                    if ($agency->wasRecentlyCreated) {
                        $synced++;
                    } else {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->warn("⚠️ Erreur pour l'agence {$agencyData['code']}: " . $e->getMessage());
                    Log::error('Erreur synchronisation agence', [
                        'agency' => $agencyData,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->info("✅ Synchronisation terminée:");
            $this->info("   - {$synced} agence(s) créée(s)");
            $this->info("   - {$updated} agence(s) mise(s) à jour");
            if ($errors > 0) {
                $this->warn("   - {$errors} erreur(s)");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la synchronisation: ' . $e->getMessage());
            Log::error('Erreur synchronisation agences Oracle', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Extrait toutes les agences uniques depuis les données Oracle
     */
    private function extractAgencies($data, &$agencies = [], $contextTerritory = null, $depth = 0): array
    {
        if (!is_array($data)) {
            return $agencies;
        }

        // Limiter la profondeur pour éviter les boucles infinies
        if ($depth > 10) {
            return $agencies;
        }

        // Détecter le territoire dans le contexte
        $currentTerritory = $contextTerritory;
        if (isset($data['TERRITOIRE']) && is_string($data['TERRITOIRE'])) {
            $currentTerritory = $this->normalizeTerritoryCode($data['TERRITOIRE']);
        }

        // Vérifier si c'est une agence (a un code et un nom)
        // Essayer plusieurs variantes de noms de champs
        $code = $data['CODE_AGENCE'] ?? $data['code'] ?? $data['AGENCE'] ?? $data['code_agence'] ?? $data['CodeAgence'] ?? null;
        $name = $data['NOM_AGENCE'] ?? $data['name'] ?? $data['AGENCE'] ?? $data['nom_agence'] ?? $data['NomAgence'] ?? $data['LIBELLE_AGENCE'] ?? $data['libelle_agence'] ?? null;
        
        // Si on n'a pas de nom mais qu'on a un code, essayer de trouver le nom ailleurs
        if ($code && !$name) {
            // Chercher dans les clés qui pourraient contenir le nom
            foreach (['NOM_AGENCE', 'name', 'AGENCE', 'nom_agence', 'NomAgence', 'LIBELLE_AGENCE', 'libelle_agence', 'label', 'Label'] as $nameKey) {
                if (isset($data[$nameKey]) && !empty($data[$nameKey])) {
                    $name = $data[$nameKey];
                    break;
                }
            }
        }

        // Si on est dans une structure de territoire, extraire le code du territoire
        foreach (['territoire_dakar_ville', 'territoire_dakar_banlieue', 'territoire_province_centre_sud', 'territoire_province_nord'] as $territoryKey) {
            if (isset($data[$territoryKey])) {
                $territoryMap = [
                    'territoire_dakar_ville' => 'DAKAR_VILLE',
                    'territoire_dakar_banlieue' => 'DAKAR_BANLIEUE',
                    'territoire_province_centre_sud' => 'PROVINCE_CENTRE_SUD',
                    'territoire_province_nord' => 'PROVINCE_NORD',
                ];
                $currentTerritory = $territoryMap[$territoryKey] ?? $currentTerritory;
            }
        }

        // Si on a un code et un nom, c'est probablement une agence
        if ($code && $name && $code !== 'FILIALE' && strtoupper($code) !== 'FILIALE') {
            // Normaliser le code
            $code = strtoupper(trim($code));
            $name = trim($name);

            // Vérifier si l'agence n'existe pas déjà
            $key = $code;
            if (!isset($agencies[$key])) {
                $agencies[$key] = [
                    'code' => $code,
                    'name' => $name,
                    'territory_code' => $currentTerritory
                ];
                $this->info("  ✓ Agence trouvée: {$code} - {$name} (Territoire: " . ($currentTerritory ?? 'N/A') . ")");
            } elseif ($currentTerritory && !$agencies[$key]['territory_code']) {
                // Mettre à jour le territoire si on le trouve
                $agencies[$key]['territory_code'] = $currentTerritory;
            }
        }

        // Parcourir récursivement les structures spécifiques
        if (isset($data['hierarchicalData'])) {
            $this->extractAgencies($data['hierarchicalData'], $agencies, $currentTerritory, $depth + 1);
        }

        if (isset($data['TERRITOIRE']) && is_array($data['TERRITOIRE'])) {
            foreach ($data['TERRITOIRE'] as $territoryKey => $territoryData) {
                $territoryCode = $this->getTerritoryCodeFromKey($territoryKey);
                
                // Parcourir les agences dans agencies
                if (isset($territoryData['agencies']) && is_array($territoryData['agencies'])) {
                    foreach ($territoryData['agencies'] as $index => $agency) {
                        if (is_array($agency)) {
                            // Debug: Afficher les clés de la première agence
                            if ($index === 0) {
                                $this->info("  🔍 Exemple d'agence (clés): " . implode(', ', array_keys($agency)));
                            }
                            // Forcer l'extraction avec le territoire
                            $this->extractAgencyFromItem($agency, $agencies, $territoryCode);
                            $this->extractAgencies($agency, $agencies, $territoryCode, $depth + 1);
                        }
                    }
                }
                
                // Parcourir les agences dans data
                if (isset($territoryData['data']) && is_array($territoryData['data'])) {
                    foreach ($territoryData['data'] as $index => $agency) {
                        if (is_array($agency)) {
                            // Debug: Afficher les clés de la première agence
                            if ($index === 0 && !isset($territoryData['agencies'])) {
                                $this->info("  🔍 Exemple d'agence dans data (clés): " . implode(', ', array_keys($agency)));
                            }
                            // Forcer l'extraction avec le territoire
                            $this->extractAgencyFromItem($agency, $agencies, $territoryCode);
                            $this->extractAgencies($agency, $agencies, $territoryCode, $depth + 1);
                        }
                    }
                }
                
                // Parcourir récursivement le territoire
                $this->extractAgencies($territoryData, $agencies, $territoryCode, $depth + 1);
            }
        }

        if (isset($data['POINT SERVICES']) && is_array($data['POINT SERVICES'])) {
            // Parcourir service_points.data
            if (isset($data['POINT SERVICES']['service_points']['data']) && is_array($data['POINT SERVICES']['service_points']['data'])) {
                foreach ($data['POINT SERVICES']['service_points']['data'] as $servicePoint) {
                    $this->extractAgencies($servicePoint, $agencies, $currentTerritory, $depth + 1);
                }
            }
            
            // Parcourir chaque point de service
            foreach ($data['POINT SERVICES'] as $serviceKey => $servicePoint) {
                if ($serviceKey === 'service_points') continue;
                
                // Parcourir les agences dans service_points.agencies
                if (isset($servicePoint['service_points']['agencies']) && is_array($servicePoint['service_points']['agencies'])) {
                    foreach ($servicePoint['service_points']['agencies'] as $agency) {
                        $this->extractAgencies($agency, $agencies, $currentTerritory, $depth + 1);
                    }
                }
                
                // Parcourir les agences directement dans agencies
                if (isset($servicePoint['agencies']) && is_array($servicePoint['agencies'])) {
                    foreach ($servicePoint['agencies'] as $agency) {
                        $this->extractAgencies($agency, $agencies, $currentTerritory, $depth + 1);
                    }
                }
                
                // Parcourir récursivement
                $this->extractAgencies($servicePoint, $agencies, $currentTerritory, $depth + 1);
            }
        }

        // Parcourir récursivement toutes les autres structures
        foreach ($data as $key => $value) {
            if (is_array($value) && !in_array($key, ['hierarchicalData', 'TERRITOIRE', 'POINT SERVICES', 'totals', 'service_points'])) {
                // Si c'est un tableau numérique, parcourir chaque élément
                if (array_keys($value) === range(0, count($value) - 1)) {
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $this->extractAgencies($item, $agencies, $currentTerritory, $depth + 1);
                        }
                    }
                } else {
                    $this->extractAgencies($value, $agencies, $currentTerritory, $depth + 1);
                }
            }
        }

        return array_values($agencies);
    }

    /**
     * Extrait une agence depuis un item de données
     */
    private function extractAgencyFromItem($item, &$agencies, $territoryCode = null)
    {
        if (!is_array($item)) {
            return;
        }

        // Essayer plusieurs variantes de noms de champs
        $code = $item['CODE_AGENCE'] ?? $item['code'] ?? $item['AGENCE'] ?? $item['code_agence'] ?? $item['CodeAgence'] ?? null;
        $name = $item['NOM_AGENCE'] ?? $item['name'] ?? $item['AGENCE'] ?? $item['nom_agence'] ?? $item['NomAgence'] ?? $item['LIBELLE_AGENCE'] ?? $item['libelle_agence'] ?? null;

        // Si on a un nom mais pas de code, utiliser le nom comme code (normalisé)
        if ($name && !$code) {
            // Générer un code à partir du nom (en majuscules, sans accents, espaces remplacés par underscores)
            $code = strtoupper(trim($name));
            $code = preg_replace('/[^A-Z0-9_]/', '_', $code);
            $code = preg_replace('/_+/', '_', $code);
            $code = trim($code, '_');
        }

        if ($code && $name && $code !== 'FILIALE' && strtoupper($code) !== 'FILIALE') {
            $code = strtoupper(trim($code));
            $name = trim($name);

            // S'assurer que le code n'est pas vide après normalisation
            if (empty($code)) {
                return;
            }

            $key = $code;
            if (!isset($agencies[$key])) {
                $agencies[$key] = [
                    'code' => $code,
                    'name' => $name,
                    'territory_code' => $territoryCode
                ];
                $this->info("  ✓ Agence trouvée: {$code} - {$name} (Territoire: " . ($territoryCode ?? 'N/A') . ")");
            } elseif ($territoryCode && !$agencies[$key]['territory_code']) {
                $agencies[$key]['territory_code'] = $territoryCode;
            }
        }
    }

    /**
     * Obtient le code du territoire depuis la clé
     */
    private function getTerritoryCodeFromKey($key): ?string
    {
        $territoryMap = [
            'territoire_dakar_ville' => 'DAKAR_VILLE',
            'territoire_dakar_banlieue' => 'DAKAR_BANLIEUE',
            'territoire_province_centre_sud' => 'PROVINCE_CENTRE_SUD',
            'territoire_province_nord' => 'PROVINCE_NORD',
        ];

        return $territoryMap[$key] ?? null;
    }


    /**
     * Normalise le code du territoire
     */
    private function normalizeTerritoryCode($territory): ?string
    {
        $territory = strtoupper(trim($territory));
        
        $map = [
            'DAKAR_VILLE' => 'DAKAR_VILLE',
            'DAKAR_BANLIEUE' => 'DAKAR_BANLIEUE',
            'PROVINCE_CENTRE_SUD' => 'PROVINCE_CENTRE_SUD',
            'PROVINCE_NORD' => 'PROVINCE_NORD',
        ];

        return $map[$territory] ?? null;
    }

    /**
     * Synchronise une agence dans la base de données
     */
    private function syncAgency(array $agencyData): Agency
    {
        $territory = null;
        if ($agencyData['territory_code']) {
            $territory = Territory::where('code', $agencyData['territory_code'])->first();
        }

        return Agency::updateOrCreate(
            ['code' => $agencyData['code']],
            [
                'name' => $agencyData['name'],
                'territory_id' => $territory?->id,
                'is_active' => true,
            ]
        );
    }
}

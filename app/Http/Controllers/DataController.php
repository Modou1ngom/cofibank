<?php

namespace App\Http\Controllers;

use App\Services\OracleService;
use App\Models\Objective;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataController extends Controller
{
    /**
     * URL de base du service Python
     */
    private $pythonServiceUrl;
    
    /**
     * Service Oracle
     */
    protected $oracleService;

    public function __construct(OracleService $oracleService)
    {
        $this->pythonServiceUrl = env('PYTHON_SERVICE_URL', 'http://localhost:8001');
        $this->oracleService = $oracleService;
    }

    /**
     * Teste la connexion à Oracle
     */
    public function testOracleConnection(): JsonResponse
    {
        $result = $this->oracleService->testConnection();
        
        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => $result['error'],
            'message' => $result['message']
        ], 500);
    }

    /**
     * Récupère la liste des tables Oracle
     */
    public function getTables(): JsonResponse
    {
        $result = $this->oracleService->getTables();
        
        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => $result['error'],
            'message' => $result['message']
        ], 500);
    }

    /**
     * Exécute une requête SQL personnalisée
     */
    public function executeQuery(Request $request): JsonResponse
    {
        $request->validate([
            'sql' => 'required|string'
        ]);

        $result = $this->oracleService->query($request->input('sql'));

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => $result['error'],
            'message' => $result['message']
        ], 500);
    }

    /**
     * Récupère les données clients depuis Oracle
     */
    public function getClientsData(Request $request): JsonResponse
    {
        try {
            $period = $request->input('period', 'month');
            $zone = $request->input('zone');
            $month = $request->input('month');
            $year = $request->input('year');
            $date = $request->input('date'); // Pour la période "week"

            $result = $this->oracleService->getClientsData($period, $zone, $month ? (int)$month : null, $year ? (int)$year : null, $date);

            if ($result['success']) {
                $data = $result['data'];
                
                // Les données peuvent être dans $data directement ou dans $data['data']
                $actualData = $data;
                if (isset($data['data']) && is_array($data['data'])) {
                    $actualData = $data['data'];
                }
                
                // Fusionner les objectifs personnalisés avec les données Oracle
                // Utiliser l'année et le mois de la requête, ou les valeurs actuelles
                $mergeYear = $year ? (int)$year : (int)date('Y');
                $mergeMonth = null;
                
                // Déterminer le mois selon la période
                if ($period === 'month' && $month) {
                    $mergeMonth = (int)$month;
                } elseif ($period === 'week' && $date) {
                    // Extraire le mois de la date
                    $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
                    if ($dateObj) {
                        $mergeMonth = (int)$dateObj->format('n');
                    }
                } elseif ($period === 'month') {
                    $mergeMonth = (int)date('n');
                }
                
                Log::info('🔄 Début fusion des objectifs', [
                    'year' => $mergeYear,
                    'month' => $mergeMonth,
                    'period' => $period,
                    'request_year' => $year,
                    'request_month' => $month,
                    'data_structure' => [
                        'has_hierarchicalData' => isset($actualData['hierarchicalData']),
                        'has_territories' => isset($actualData['territories']),
                        'keys' => array_keys($actualData)
                    ]
                ]);
                
                $actualData = $this->mergeObjectivesWithData($actualData, 'CLIENT', $mergeYear, $mergeMonth);
                
                // Remettre les données fusionnées dans la structure originale
                if (isset($data['data'])) {
                    $data['data'] = $actualData;
                } else {
                    $data = $actualData;
                }
                
                Log::info('✅ Données fusionnées retournées au client');
                
                return response()->json($data);
            }

            Log::error('Erreur lors de la récupération des données clients', [
                'error' => $result['error'],
                'message' => $result['message']
            ]);

            return response()->json([
                'error' => $result['error'],
                'message' => $result['message']
            ], 500);
        } catch (\Exception $e) {
            Log::error('Exception lors de la récupération des données clients', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Erreur interne',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fusionne les objectifs personnalisés avec les données Oracle
     */
    private function mergeObjectivesWithData($data, $type, $year, $month = null)
    {
        try {
            // Récupérer tous les objectifs pour cette période depuis la base de données Laravel
            $objectivesQuery = Objective::where('type', $type)
                ->where('year', $year);
            
            if ($month) {
                $objectivesQuery->where(function($q) use ($month) {
                    $q->where(function($q2) use ($month) {
                        $q2->where('period', 'month')->where('month', $month);
                    })
                    ->orWhere('period', 'quarter')
                    ->orWhere('period', 'year');
                });
            } else {
                // Si pas de mois, prendre tous les objectifs de l'année
                $objectivesQuery->where(function($q) {
                    $q->where('period', 'year')
                      ->orWhere('period', 'quarter');
                });
            }
            
            $objectives = $objectivesQuery->get();
            
            Log::info('Objectifs trouvés pour fusion', [
                'count' => $objectives->count(),
                'type' => $type,
                'year' => $year,
                'month' => $month,
                'objectives' => $objectives->map(function($obj) {
                    return [
                        'id' => $obj->id,
                        'agency_code' => $obj->agency_code,
                        'agency_name' => $obj->agency_name,
                        'value' => $obj->value,
                        'category' => $obj->category
                    ];
                })->toArray()
            ]);
            
            // Créer un index pour recherche rapide par code et nom d'agence (exclure FILIALE)
            $objectivesByCode = [];
            $objectivesByName = [];
            foreach ($objectives as $objective) {
                // Ignorer les objectifs FILIALE dans l'index (ils seront traités séparément)
                if ($objective->category === 'FILIALE') {
                    continue;
                }
                $code = strtoupper(trim($objective->agency_code ?? ''));
                $name = strtoupper(trim($objective->agency_name ?? ''));
                if ($code) {
                    $objectivesByCode[$code] = $objective;
                }
                if ($name) {
                    $objectivesByName[$name] = $objective;
                }
            }
            
            Log::info('Index des objectifs créé', [
                'byCode' => count($objectivesByCode),
                'byName' => count($objectivesByName)
            ]);
            
            $mergedCount = 0;
            
            // Fonction pour normaliser les noms d'agences (supprimer accents, espaces multiples, etc.)
            $normalizeAgencyName = function($name) {
                $name = strtoupper(trim($name ?? ''));
                // Supprimer les accents
                $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
                // Remplacer les espaces multiples par un seul
                $name = preg_replace('/\s+/', ' ', $name);
                return trim($name);
            };
            
            // Créer aussi un index normalisé (exclure FILIALE)
            $objectivesByNormalizedCode = [];
            $objectivesByNormalizedName = [];
            foreach ($objectives as $objective) {
                // Ignorer les objectifs FILIALE dans l'index normalisé
                if ($objective->category === 'FILIALE') {
                    continue;
                }
                $code = $normalizeAgencyName($objective->agency_code);
                $name = $normalizeAgencyName($objective->agency_name);
                if ($code) {
                    $objectivesByNormalizedCode[$code] = $objective;
                }
                if ($name) {
                    $objectivesByNormalizedName[$name] = $objective;
                }
            }
            
            // Fonction récursive pour fusionner les objectifs dans la structure hiérarchique
            $mergeRecursive = function(&$item, $depth = 0, $contextCategory = null) use (&$mergeRecursive, &$mergedCount, $normalizeAgencyName, $objectivesByCode, $objectivesByName, $objectivesByNormalizedCode, $objectivesByNormalizedName, $type) {
                if (is_array($item)) {
                    foreach ($item as $key => &$value) {
                        if (is_array($value)) {
                            // Extraire tous les champs possibles pour le nom/code d'agence
                            $possibleNames = [
                                $value['name'] ?? null,
                                $value['AGENCE'] ?? null,
                                $value['NOM_AGENCE'] ?? null,
                                $value['NOM'] ?? null,
                                $value['LIBELLE'] ?? null,
                                $value['LIBELLE_AGENCE'] ?? null
                            ];
                            
                            $possibleCodes = [
                                $value['code'] ?? null,
                                $value['CODE_AGENCE'] ?? null,
                                $value['CODE'] ?? null,
                                $value['AGENCE'] ?? null // AGENCE peut être code ou nom
                            ];
                            
                            // Prendre le premier non vide
                            $agencyName = '';
                            foreach ($possibleNames as $name) {
                                if (!empty($name)) {
                                    $agencyName = strtoupper(trim($name));
                                    break;
                                }
                            }
                            
                            $agencyCode = '';
                            foreach ($possibleCodes as $code) {
                                if (!empty($code)) {
                                    $agencyCode = strtoupper(trim($code));
                                    break;
                                }
                            }
                            
                            // Si pas de code, utiliser le nom comme code
                            if (empty($agencyCode) && !empty($agencyName)) {
                                $agencyCode = $agencyName;
                            }
                            
                            // Normaliser aussi pour la recherche
                            $normalizedAgencyName = $normalizeAgencyName($agencyName);
                            $normalizedAgencyCode = $normalizeAgencyName($agencyCode);
                            
                            // Vérifier si c'est une agence (a un nom ou code)
                            $isAgency = ($agencyCode || $agencyName) && 
                                       !isset($value['agencies']) && // Pas un conteneur d'agences
                                       !isset($value['totals']) && // Pas un total
                                       !isset($value['service_points']); // Pas un point de service
                            
                            if ($isAgency && ($agencyCode || $agencyName)) {
                                // Log pour déboguer (seulement les premières agences)
                                if ($mergedCount < 5 && $depth < 3) {
                                    Log::debug('🔍 Recherche objectif pour agence', [
                                        'agency_code' => $agencyCode,
                                        'agency_name' => $agencyName,
                                        'normalized_code' => $normalizedAgencyCode,
                                        'normalized_name' => $normalizedAgencyName,
                                        'value_keys' => array_keys($value),
                                        'depth' => $depth
                                    ]);
                                }
                                // Chercher un objectif correspondant (plusieurs stratégies)
                                $objective = null;
                                
                                // 1. Recherche exacte par code
                                if ($agencyCode && isset($objectivesByCode[$agencyCode])) {
                                    $objective = $objectivesByCode[$agencyCode];
                                }
                                // 2. Recherche exacte par nom
                                elseif ($agencyName && isset($objectivesByName[$agencyName])) {
                                    $objective = $objectivesByName[$agencyName];
                                }
                                // 3. Recherche normalisée par code
                                elseif ($normalizedAgencyCode && isset($objectivesByNormalizedCode[$normalizedAgencyCode])) {
                                    $objective = $objectivesByNormalizedCode[$normalizedAgencyCode];
                                }
                                // 4. Recherche normalisée par nom
                                elseif ($normalizedAgencyName && isset($objectivesByNormalizedName[$normalizedAgencyName])) {
                                    $objective = $objectivesByNormalizedName[$normalizedAgencyName];
                                }
                                // 5. Recherche croisée (code comme nom)
                                elseif ($agencyCode && isset($objectivesByName[$agencyCode])) {
                                    $objective = $objectivesByName[$agencyCode];
                                }
                                // 6. Recherche croisée (nom comme code)
                                elseif ($agencyName && isset($objectivesByCode[$agencyName])) {
                                    $objective = $objectivesByCode[$agencyName];
                                }
                                // 7. Recherche partielle (contient) - plus flexible
                                else {
                                    // Recherche dans les codes normalisés
                                    foreach ($objectivesByNormalizedCode as $objCode => $obj) {
                                        if (!empty($normalizedAgencyCode) && !empty($objCode)) {
                                            // Vérifier si l'un contient l'autre (au moins 3 caractères communs)
                                            if (strlen($normalizedAgencyCode) >= 3 && strlen($objCode) >= 3) {
                                                if (stripos($normalizedAgencyCode, $objCode) !== false || 
                                                    stripos($objCode, $normalizedAgencyCode) !== false) {
                                                    $objective = $obj;
                                                    Log::info('Match partiel trouvé (code)', [
                                                        'agency_code' => $normalizedAgencyCode,
                                                        'objective_code' => $objCode
                                                    ]);
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    // Recherche dans les noms normalisés
                                    if (!$objective) {
                                        foreach ($objectivesByNormalizedName as $objName => $obj) {
                                            if (!empty($normalizedAgencyName) && !empty($objName)) {
                                                if (strlen($normalizedAgencyName) >= 3 && strlen($objName) >= 3) {
                                                    if (stripos($normalizedAgencyName, $objName) !== false || 
                                                        stripos($objName, $normalizedAgencyName) !== false) {
                                                        $objective = $obj;
                                                        Log::info('Match partiel trouvé (nom)', [
                                                            'agency_name' => $normalizedAgencyName,
                                                            'objective_name' => $objName
                                                        ]);
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                if ($objective) {
                                    // Fusionner l'objectif (priorité aux objectifs personnalisés)
                                    // Ne pas écraser un objectif existant s'il est déjà défini (sauf s'il est 0)
                                    $oldValue = $value['objectif'] ?? $value['OBJECTIF_CLIENT'] ?? $value['OBJECTIF_PRODUCTION'] ?? 0;
                                    $newValue = (int)$objective->value;
                                    
                                    // Récupérer NOMBRES et VOLUME si disponibles
                                    $newValueNombres = $objective->value_nombres !== null ? (int)$objective->value_nombres : null;
                                    $newValueVolume = $objective->value_volume !== null ? (int)$objective->value_volume : null;
                                    
                                    // Si l'ancienne valeur est 0 ou vide, utiliser la nouvelle valeur
                                    // Sinon, garder la valeur existante (priorité aux objectifs déjà fusionnés)
                                    if ($oldValue == 0 || $oldValue === null) {
                                    if ($type === 'CLIENT') {
                                            $value['OBJECTIF_CLIENT'] = $newValue;
                                            $value['objectif'] = $newValue;
                                        } elseif (in_array($type, ['PRODUCTION', 'PRODUCTION_VOLUME', 'ENCOURS_CREDIT', 'PRODUCTION_ENCOURS'])) {
                                            // Pour tous les types de production, utiliser OBJECTIF_PRODUCTION
                                            $value['OBJECTIF_PRODUCTION'] = $newValue;
                                            $value['objectif'] = $newValue;
                                    } else {
                                            // Par défaut, utiliser objectif
                                            $value['objectif'] = $newValue;
                                    }
                                    
                                    // Ajouter NOMBRES et VOLUME si disponibles
                                    if ($newValueNombres !== null) {
                                        $value['OBJECTIF_NOMBRES'] = $newValueNombres;
                                        $value['NOMBRES'] = $newValueNombres;
                                    }
                                    if ($newValueVolume !== null) {
                                        $value['OBJECTIF_VOLUME'] = $newValueVolume;
                                        $value['VOLUME'] = $newValueVolume;
                                    }
                                    
                                    $mergedCount++;
                                    Log::info('✅ Objectif fusionné avec succès', [
                                        'agency_code_oracle' => $agencyCode,
                                        'agency_name_oracle' => $agencyName,
                                        'objective_code' => strtoupper(trim($objective->agency_code ?? '')),
                                        'objective_name' => strtoupper(trim($objective->agency_name ?? '')),
                                            'objective_category' => $objective->category,
                                        'old_value' => $oldValue,
                                            'new_value' => $newValue,
                                            'new_value_nombres' => $newValueNombres,
                                            'new_value_volume' => $newValueVolume,
                                        'objective_id' => $objective->id,
                                            'depth' => $depth,
                                            'context_category' => $contextCategory
                                        ]);
                                    } else {
                                        Log::debug('⚠️ Objectif déjà défini, non écrasé', [
                                            'agency_code_oracle' => $agencyCode,
                                            'agency_name_oracle' => $agencyName,
                                            'existing_value' => $oldValue,
                                            'new_value' => $newValue,
                                            'objective_id' => $objective->id
                                    ]);
                                    }
                                } else {
                                    // Log pour déboguer les non-matchs (seulement pour les premières agences)
                                    if ($depth < 2 && count($objectivesByCode) > 0 && ($agencyCode || $agencyName)) {
                                        Log::debug('⚠️ Aucun objectif trouvé pour agence', [
                                            'agency_code_oracle' => $agencyCode,
                                            'agency_name_oracle' => $agencyName,
                                            'normalized_code' => $normalizedAgencyCode,
                                            'normalized_name' => $normalizedAgencyName,
                                            'available_objective_codes' => array_slice(array_keys($objectivesByCode), 0, 10),
                                            'available_objective_names' => array_slice(array_keys($objectivesByName), 0, 10),
                                            'value_keys' => array_keys($value),
                                            'value_sample' => [
                                                'name' => $value['name'] ?? null,
                                                'AGENCE' => $value['AGENCE'] ?? null,
                                                'CODE_AGENCE' => $value['CODE_AGENCE'] ?? null,
                                                'NOM_AGENCE' => $value['NOM_AGENCE'] ?? null
                                            ]
                                        ]);
                                    }
                                }
                            }
                            
                            // Récursion pour les structures imbriquées (agencies, service_points, etc.)
                            if (isset($value['agencies']) && is_array($value['agencies'])) {
                                foreach ($value['agencies'] as &$agency) {
                                    $mergeRecursive($agency, $depth + 1);
                                }
                            }
                            
                            // Récursion pour service_points.agencies (structure POINT SERVICES)
                            if (isset($value['service_points']) && is_array($value['service_points'])) {
                                if (isset($value['service_points']['agencies']) && is_array($value['service_points']['agencies'])) {
                                    foreach ($value['service_points']['agencies'] as &$agency) {
                                        $mergeRecursive($agency, $depth + 1);
                                    }
                                }
                            }
                            
                            // Récursion générale
                            $mergeRecursive($value, $depth + 1);
                        }
                    }
                }
            };
            
            // Vérifier d'abord si les données sont dans une clé 'data'
            // MAIS ne pas utiliser data.data si on a déjà hierarchicalData ou territories dans $data
            // Utiliser une référence pour que les modifications soient persistantes
            if (isset($data['data']) && is_array($data['data']) && !isset($data['hierarchicalData']) && !isset($data['territories'])) {
                $dataToMerge = &$data['data'];
                Log::info('📦 Données trouvées dans data.data, utilisation de cette structure');
            } elseif (isset($data['hierarchicalData']) || isset($data['territories'])) {
                Log::info('📦 Données ont hierarchicalData/territories, utilisation de la structure principale');
                $dataToMerge = &$data;
            } else {
                $dataToMerge = &$data;
            }
            
            // Fusionner dans hierarchicalData (structure principale)
            if (isset($dataToMerge['hierarchicalData'])) {
                Log::info('🔍 Fusion dans hierarchicalData', [
                    'has_hierarchicalData' => true,
                    'keys' => array_keys($dataToMerge['hierarchicalData'] ?? [])
                ]);
                
                // Parcourir spécifiquement TERRITOIRE et ses agences
                if (isset($dataToMerge['hierarchicalData']['TERRITOIRE'])) {
                    foreach ($dataToMerge['hierarchicalData']['TERRITOIRE'] as $territoryKey => &$territory) {
                        // Les agences peuvent être dans 'agencies' ou 'data'
                        // Utiliser une référence pour que les modifications soient persistantes
                        if (isset($territory['agencies'])) {
                            $agenciesList = &$territory['agencies'];
                        } elseif (isset($territory['data'])) {
                            $agenciesList = &$territory['data'];
                        } else {
                            $agenciesList = [];
                        }
                        
                        if (is_array($agenciesList) && count($agenciesList) > 0) {
                            Log::info('Parcours des agences du territoire', [
                                'territory' => $territoryKey,
                                'agencies_count' => count($agenciesList),
                                'has_agencies_key' => isset($territory['agencies']),
                                'has_data_key' => isset($territory['data'])
                            ]);
                            foreach ($agenciesList as &$agency) {
                                $mergeRecursive($agency, 1);
                            }
                            // Les modifications sont déjà dans $agenciesList car c'est une référence
                            // Pas besoin de remettre, les modifications sont déjà appliquées
                        }
                        // Aussi parcourir récursivement pour les autres structures
                        $mergeRecursive($territory, 1);
                    }
                }
                
                // Parcourir POINT SERVICES
                if (isset($dataToMerge['hierarchicalData']['POINT SERVICES'])) {
                    Log::info('🔍 Fusion dans POINT SERVICES', [
                        'keys' => array_keys($dataToMerge['hierarchicalData']['POINT SERVICES'])
                    ]);
                    
                    // Vérifier si POINT SERVICES a directement service_points.data (structure plate)
                    if (isset($dataToMerge['hierarchicalData']['POINT SERVICES']['service_points']['data']) && is_array($dataToMerge['hierarchicalData']['POINT SERVICES']['service_points']['data'])) {
                        Log::info('Parcours des points de service dans POINT SERVICES.service_points.data', [
                            'service_points_count' => count($dataToMerge['hierarchicalData']['POINT SERVICES']['service_points']['data'])
                        ]);
                        foreach ($dataToMerge['hierarchicalData']['POINT SERVICES']['service_points']['data'] as &$servicePoint) {
                            $mergeRecursive($servicePoint, 1, 'POINT SERVICES');
                        }
                    }
                    
                    // Parcourir les clés individuelles (structure hiérarchique)
                    foreach ($dataToMerge['hierarchicalData']['POINT SERVICES'] as $serviceKey => &$servicePoint) {
                        if ($serviceKey === 'service_points') {
                            // Déjà traité ci-dessus
                            continue;
                        }
                        if (isset($servicePoint['service_points']['agencies']) && is_array($servicePoint['service_points']['agencies'])) {
                            Log::info('Parcours des agences dans service_points.agencies', [
                                'service_key' => $serviceKey,
                                'agencies_count' => count($servicePoint['service_points']['agencies'])
                            ]);
                            foreach ($servicePoint['service_points']['agencies'] as &$agency) {
                                $mergeRecursive($agency, 1, 'POINT SERVICES');
                            }
                        }
                        if (isset($servicePoint['agencies']) && is_array($servicePoint['agencies'])) {
                            Log::info('Parcours des agences dans agencies', [
                                'service_key' => $serviceKey,
                                'agencies_count' => count($servicePoint['agencies'])
                            ]);
                            foreach ($servicePoint['agencies'] as &$agency) {
                                $mergeRecursive($agency, 1, 'POINT SERVICES');
                            }
                        }
                        $mergeRecursive($servicePoint, 1, 'POINT SERVICES');
                    }
                }
                
                // Récursion générale pour le reste
                $mergeRecursive($dataToMerge['hierarchicalData'], 0);
            }
            
            // Si pas de hierarchicalData, essayer une récursion générale
            if (!isset($dataToMerge['hierarchicalData'])) {
                Log::info('⚠️ Pas de hierarchicalData, récursion générale sur toute la structure');
                $mergeRecursive($dataToMerge, 0);
            }
            
            // Fusionner dans territories (structure alternative)
            if (isset($dataToMerge['territories'])) {
                Log::info('🔍 Fusion dans territories', [
                    'has_territories' => true,
                    'keys' => array_keys($dataToMerge['territories'] ?? [])
                ]);
                // Parcourir chaque territoire et fusionner les agences
                foreach ($dataToMerge['territories'] as $territoryKey => &$territory) {
                    if (isset($territory['agencies']) && is_array($territory['agencies'])) {
                        Log::info('Parcours des agences du territoire (territories)', [
                            'territory' => $territoryKey,
                            'agencies_count' => count($territory['agencies'])
                        ]);
                        foreach ($territory['agencies'] as &$agency) {
                            $mergeRecursive($agency, 1);
                        }
                    }
                    // Récursion générale sur le territoire
                    $mergeRecursive($territory, 1);
                }
            }
            
            // Fusionner dans les agences directement
            if (isset($dataToMerge['agencies'])) {
                Log::info('🔍 Fusion dans agencies', [
                    'has_agencies' => true,
                    'count' => count($dataToMerge['agencies'] ?? [])
                ]);
                $mergeRecursive($dataToMerge['agencies']);
            }
            
            // Remettre les données fusionnées dans la structure originale si nécessaire
            if (isset($data['data']) && is_array($data['data'])) {
                // Mettre à jour toutes les structures dans data['data']
                if (isset($dataToMerge['hierarchicalData'])) {
                    $data['data']['hierarchicalData'] = $dataToMerge['hierarchicalData'];
                }
                if (isset($dataToMerge['territories'])) {
                    $data['data']['territories'] = $dataToMerge['territories'];
                }
                // Mettre à jour aussi les autres clés si elles existent
                foreach ($dataToMerge as $key => $value) {
                    if ($key !== 'hierarchicalData' && $key !== 'territories') {
                        $data['data'][$key] = $value;
                    }
                }
            } else {
                // Si pas de structure data['data'], s'assurer que les modifications sont dans $data
                // $dataToMerge est déjà une référence à $data, mais pour les tableaux imbriqués,
                // on doit explicitement copier les modifications
                if (isset($dataToMerge['hierarchicalData'])) {
                    $data['hierarchicalData'] = $dataToMerge['hierarchicalData'];
                }
                if (isset($dataToMerge['territories'])) {
                    $data['territories'] = $dataToMerge['territories'];
                }
                // Mettre à jour aussi les autres clés
                foreach ($dataToMerge as $key => $value) {
                    if ($key !== 'hierarchicalData' && $key !== 'territories' && $key !== 'data') {
                        $data[$key] = $value;
                    }
                }
            }
            
            // Vérifier si LAMINE GUEYE a un objectif après fusion dans hierarchicalData
            if (isset($data['hierarchicalData']['TERRITOIRE']['territoire_dakar_ville']['data'])) {
                foreach ($data['hierarchicalData']['TERRITOIRE']['territoire_dakar_ville']['data'] as $agency) {
                    if (isset($agency['AGENCE']) && stripos($agency['AGENCE'], 'LAMINE GUEYE') !== false) {
                        Log::info('🔍 Vérification LAMINE GUEYE après fusion (hierarchicalData)', [
                            'AGENCE' => $agency['AGENCE'] ?? 'N/A',
                            'CODE_AGENCE' => $agency['CODE_AGENCE'] ?? 'N/A',
                            'OBJECTIF_PRODUCTION' => $agency['OBJECTIF_PRODUCTION'] ?? 'NON DÉFINI',
                            'objectif' => $agency['objectif'] ?? 'NON DÉFINI',
                            'all_keys' => array_keys($agency)
                        ]);
                        break;
                    }
                }
            }
            
            // Vérifier aussi dans territories
            if (isset($data['territories']['territoire_dakar_ville']['agencies'])) {
                foreach ($data['territories']['territoire_dakar_ville']['agencies'] as $agency) {
                    $agencyName = strtoupper($agency['name'] ?? $agency['AGENCE'] ?? '');
                    if (strpos($agencyName, 'LAMINE') !== false || strpos($agencyName, 'GUEYE') !== false) {
                        Log::info('🔍 Vérification LAMINE GUEYE après fusion (territories)', [
                            'name' => $agencyName,
                            'objectif' => $agency['objectif'] ?? 'NON DÉFINI',
                            'OBJECTIF_CLIENT' => $agency['OBJECTIF_CLIENT'] ?? 'NON DÉFINI',
                            'OBJECTIF_PRODUCTION' => $agency['OBJECTIF_PRODUCTION'] ?? 'NON DÉFINI',
                            'all_keys' => array_keys($agency)
                        ]);
                        break;
                    }
                }
            }
            
            Log::info('✅ Fusion terminée', [
                'objectifs_fusionnes' => $mergedCount,
                'objectifs_totaux' => $objectives->count(),
                'structure_keys' => array_keys($data ?? []),
                'has_hierarchicalData' => isset($dataToMerge['hierarchicalData']),
                'has_territories' => isset($dataToMerge['territories'])
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la fusion des objectifs', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $data;
    }

    /**
     * Récupère les données de production depuis Oracle via l'API Python
     */
    public function getProductionData(Request $request): JsonResponse
    {
        try {
            // Construire l'URL de l'API Python
            $apiUrl = $this->pythonServiceUrl . '/api/oracle/data/production';
            
            // Récupérer les paramètres de la requête
            $month = $request->input('month');
            $year = $request->input('year');
            $dateM_debut = $request->input('date_m_debut');
            $dateM_fin = $request->input('date_m_fin');
            
            // Construire les paramètres de requête
            $params = [];
            if ($month && $year) {
                // Mois spécifique
                $params['month'] = $month;
                $params['year'] = $year;
            } elseif ($year && !$month) {
                // Année complète (sans mois)
                $params['year'] = $year;
            } elseif ($dateM_debut && $dateM_fin) {
                // Dates personnalisées
                $params['date_m_debut'] = $dateM_debut;
                $params['date_m_fin'] = $dateM_fin;
            } else {
                // Utiliser le mois et l'année actuels par défaut
                $params['month'] = (int)date('n');
                $params['year'] = (int)date('Y');
            }
            
            // Faire l'appel à l'API Python (timeout de 5 minutes pour les calculs Oracle complexes)
            $response = Http::timeout(300)->get($apiUrl, $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Fusionner les objectifs PRODUCTION avec les données Oracle
                $mergeYear = $year ? (int)$year : (int)date('Y');
                $mergeMonth = $month ? (int)$month : null;
                
                // Si pas de mois mais année complète, ne pas filtrer par mois
                if (!$mergeMonth && $year) {
                    $mergeMonth = null;
                } elseif (!$mergeMonth) {
                    $mergeMonth = (int)date('n');
                }
                
                Log::info('🔄 Début fusion des objectifs PRODUCTION', [
                    'year' => $mergeYear,
                    'month' => $mergeMonth,
                    'data_structure' => [
                        'has_hierarchicalData' => isset($data['hierarchicalData']),
                        'has_territories' => isset($data['territories']),
                        'keys' => array_keys($data ?? [])
                    ],
                    'raw_data_keys' => array_keys($data ?? []),
                    'raw_data_sample' => isset($data['hierarchicalData']) ? array_keys($data['hierarchicalData']) : 'N/A',
                    'is_array' => is_array($data),
                    'is_numeric_array' => isset($data[0]) && is_array($data[0])
                ]);
                
                // Les données peuvent être dans $data directement ou dans $data['data']
                $actualData = $data;
                if (isset($data['hierarchicalData']) || isset($data['territories'])) {
                    // Les données sont dans une structure hiérarchique - fusionner directement
                    Log::info('📦 Données PRODUCTION dans structure hiérarchique (hierarchicalData/territories)', [
                        'has_hierarchicalData' => isset($data['hierarchicalData']),
                        'has_territories' => isset($data['territories']),
                        'hierarchicalData_keys' => isset($data['hierarchicalData']) ? array_keys($data['hierarchicalData']) : []
                    ]);
                    // Fusionner directement - la fonction retourne les données modifiées
                    $data = $this->mergeObjectivesWithData($data, 'PRODUCTION', $mergeYear, $mergeMonth);
                    
                    // Vérifier que la fusion a bien fonctionné pour AGENCE LAMINE GUEYE dans hierarchicalData
                    // Vérifier dans hierarchicalData.TERRITOIRE.territoire_dakar_ville.data
                    if (isset($data['hierarchicalData']['TERRITOIRE']['territoire_dakar_ville']['data'])) {
                        foreach ($data['hierarchicalData']['TERRITOIRE']['territoire_dakar_ville']['data'] as &$agency) {
                            if (isset($agency['AGENCE']) && stripos($agency['AGENCE'], 'LAMINE GUEYE') !== false) {
                                Log::info('🔍 AGENCE LAMINE GUEYE trouvée après fusion (hierarchicalData)', [
                                    'AGENCE' => $agency['AGENCE'] ?? 'N/A',
                                    'CODE_AGENCE' => $agency['CODE_AGENCE'] ?? 'N/A',
                                    'OBJECTIF_PRODUCTION' => $agency['OBJECTIF_PRODUCTION'] ?? 'NON DÉFINI',
                                    'objectif' => $agency['objectif'] ?? 'NON DÉFINI',
                                    'all_keys' => array_keys($agency)
                                ]);
                                break;
                            }
                        }
                    }
                    // Vérifier aussi dans territories
                    if (isset($data['territories']['territoire_dakar_ville']['data'])) {
                        foreach ($data['territories']['territoire_dakar_ville']['data'] as &$agency) {
                            if (isset($agency['AGENCE']) && stripos($agency['AGENCE'], 'LAMINE GUEYE') !== false) {
                                Log::info('🔍 AGENCE LAMINE GUEYE trouvée après fusion (territories)', [
                                    'AGENCE' => $agency['AGENCE'] ?? 'N/A',
                                    'CODE_AGENCE' => $agency['CODE_AGENCE'] ?? 'N/A',
                                    'OBJECTIF_PRODUCTION' => $agency['OBJECTIF_PRODUCTION'] ?? 'NON DÉFINI',
                                    'objectif' => $agency['objectif'] ?? 'NON DÉFINI'
                                ]);
                                break;
                            }
                        }
                    }
                    
                    // Les données sont déjà modifiées par référence dans $data
                    Log::info('✅ Données PRODUCTION fusionnées retournées au client (structure hiérarchique)');
                    return response()->json($data);
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    $actualData = $data['data'];
                    Log::info('📦 Données PRODUCTION trouvées dans data.data');
                } elseif (is_array($data) && isset($data[0]) && is_array($data[0]) && !isset($data['hierarchicalData']) && !isset($data['territories'])) {
                    // Les données sont directement un tableau d'agences (format plat)
                    Log::info('📦 Données PRODUCTION sont un tableau direct d\'agences', [
                        'count' => count($data),
                        'first_agency_sample' => isset($data[0]) ? array_keys($data[0]) : []
                    ]);
                    // Fusionner directement sur le tableau - la fonction modifie par référence
                    $actualData = $this->mergeObjectivesWithData($data, 'PRODUCTION', $mergeYear, $mergeMonth);
                    // Les données fusionnées sont déjà dans $data (modifiées par référence)
                    // Vérifier que la fusion a bien fonctionné pour AGENCE LAMINE GUEYE
                    $lamineGueyeFound = false;
                    foreach ($data as $index => $agency) {
                        if (isset($agency['AGENCE']) && stripos($agency['AGENCE'], 'LAMINE GUEYE') !== false) {
                            $lamineGueyeFound = true;
                            Log::info('🔍 AGENCE LAMINE GUEYE trouvée après fusion (tableau direct)', [
                                'index' => $index,
                                'AGENCE' => $agency['AGENCE'] ?? 'N/A',
                                'CODE_AGENCE' => $agency['CODE_AGENCE'] ?? 'N/A',
                                'OBJECTIF_PRODUCTION' => $agency['OBJECTIF_PRODUCTION'] ?? 'NON DÉFINI',
                                'objectif' => $agency['objectif'] ?? 'NON DÉFINI',
                                'all_keys' => array_keys($agency)
                            ]);
                            break;
                        }
                    }
                    if (!$lamineGueyeFound) {
                        Log::warning('⚠️ AGENCE LAMINE GUEYE non trouvée dans les données après fusion');
                    }
                    Log::info('✅ Données PRODUCTION fusionnées retournées au client (tableau direct)', [
                        'count_after_merge' => count($data)
                    ]);
                    // Retourner dans un format que le frontend peut traiter
                    // Le frontend attend soit hierarchicalData, soit territories, soit data
                    return response()->json(['data' => $data]);
                } else {
                    Log::info('📦 Données PRODUCTION dans data directement');
                }
                
                // Fusionner et récupérer le résultat
                $mergedData = $this->mergeObjectivesWithData($actualData, 'PRODUCTION', $mergeYear, $mergeMonth);
                
                // Remettre les données fusionnées dans la structure originale
                if (isset($data['data']) && is_array($data['data'])) {
                    $data['data'] = $mergedData;
                } else {
                    $data = $mergedData;
                }
                
                Log::info('✅ Données PRODUCTION fusionnées retournées au client');
                
                return response()->json($data);
            }
            
            // En cas d'erreur, retourner le message d'erreur
            $errorData = $response->json();
            Log::error('Erreur API Python Production', [
                'status' => $response->status(),
                'error' => $errorData
            ]);
            
            return response()->json([
                'error' => 'Erreur lors de la récupération des données',
                'detail' => $errorData['detail'] ?? $response->body(),
                'status' => $response->status()
            ], $response->status() ?: 500);
            
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'appel API Python Production', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Erreur de connexion au service Python',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Récupère les données de production en volume depuis Oracle via l'API Python
     */
    public function getProductionVolumeData(Request $request): JsonResponse
    {
        try {
            // Construire l'URL de l'API Python
            $apiUrl = $this->pythonServiceUrl . '/api/oracle/data/production-volume';
            
            // Récupérer les paramètres de la requête
            $month = $request->input('month');
            $year = $request->input('year');
            $dateM_debut = $request->input('date_m_debut');
            $dateM_fin = $request->input('date_m_fin');
            
            // Construire les paramètres de requête
            $params = [];
            if ($month && $year) {
                // Mois spécifique
                $params['month'] = $month;
                $params['year'] = $year;
            } elseif ($year && !$month) {
                // Année complète (sans mois)
                $params['year'] = $year;
            } elseif ($dateM_debut && $dateM_fin) {
                // Dates personnalisées
                $params['date_m_debut'] = $dateM_debut;
                $params['date_m_fin'] = $dateM_fin;
            } else {
                // Utiliser le mois et l'année actuels par défaut
                $params['month'] = (int)date('n');
                $params['year'] = (int)date('Y');
            }
            
            // Faire l'appel à l'API Python (timeout de 5 minutes pour les calculs Oracle complexes)
            $response = Http::timeout(300)->get($apiUrl, $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Fusionner les objectifs PRODUCTION_VOLUME avec les données Oracle
                $mergeYear = $year ? (int)$year : (int)date('Y');
                $mergeMonth = $month ? (int)$month : null;
                
                if (!$mergeMonth && $year) {
                    $mergeMonth = null;
                } elseif (!$mergeMonth) {
                    $mergeMonth = (int)date('n');
                }
                
                Log::info('🔄 Début fusion des objectifs PRODUCTION VOLUME', [
                    'year' => $mergeYear,
                    'month' => $mergeMonth
                ]);
                
                // Les données peuvent être dans $data directement ou dans $data['data']
                $actualData = $data;
                if (isset($data['hierarchicalData']) || isset($data['territories'])) {
                    // Les données sont dans une structure hiérarchique - fusionner directement
                    $data = $this->mergeObjectivesWithData($data, 'PRODUCTION_VOLUME', $mergeYear, $mergeMonth);
                    Log::info('✅ Données PRODUCTION VOLUME fusionnées retournées au client (structure hiérarchique)');
                    return response()->json($data);
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    $actualData = $data['data'];
                }
                
                $actualData = $this->mergeObjectivesWithData($actualData, 'PRODUCTION_VOLUME', $mergeYear, $mergeMonth);
                
                // Remettre les données fusionnées dans la structure originale
                if (isset($data['data'])) {
                    $data['data'] = $actualData;
                } else {
                    $data = $actualData;
                }
                
                Log::info('✅ Données PRODUCTION VOLUME fusionnées retournées au client');
                
                return response()->json($data);
            }
            
            // En cas d'erreur, retourner le message d'erreur
            $errorData = $response->json();
            Log::error('Erreur API Python Production Volume', [
                'status' => $response->status(),
                'error' => $errorData
            ]);
            
            return response()->json([
                'error' => 'Erreur lors de la récupération des données',
                'detail' => $errorData['detail'] ?? $response->body(),
                'status' => $response->status()
            ], $response->status() ?: 500);
            
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'appel API Python Production Volume', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Erreur de connexion au service Python',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Récupère les données d'évolution de l'encours crédit depuis Oracle via l'API Python
     */
    public function getEncoursCreditData(Request $request): JsonResponse
    {
        try {
            // Construire l'URL de l'API Python
            $apiUrl = $this->pythonServiceUrl . '/api/oracle/data/encours-credit';
            
            // Récupérer les paramètres de la requête
            $monthM = $request->input('month_m');
            $yearM = $request->input('year_m');
            $monthM1 = $request->input('month_m1');
            $yearM1 = $request->input('year_m1');
            
            // Construire les paramètres de requête
            $params = [];
            if ($monthM && $yearM) {
                $params['month_m'] = $monthM;
                $params['year_m'] = $yearM;
                if ($monthM1) {
                    $params['month_m1'] = $monthM1;
                }
                if ($yearM1) {
                    $params['year_m1'] = $yearM1;
                }
            } else {
                // Utiliser le mois et l'année actuels par défaut
                $params['month_m'] = (int)date('n');
                $params['year_m'] = (int)date('Y');
            }
            
            // Faire l'appel à l'API Python (timeout de 5 minutes pour les calculs Oracle complexes)
            $response = Http::timeout(300)->get($apiUrl, $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Fusionner les objectifs ENCOURS_CREDIT avec les données Oracle
                $mergeYear = $yearM ? (int)$yearM : (int)date('Y');
                $mergeMonth = $monthM ? (int)$monthM : null;
                
                if (!$mergeMonth) {
                    $mergeMonth = (int)date('n');
                }
                
                Log::info('🔄 Début fusion des objectifs ENCOURS_CREDIT', [
                    'year' => $mergeYear,
                    'month' => $mergeMonth
                ]);
                
                // Les données peuvent être dans $data directement ou dans $data['data']
                $actualData = $data;
                if (isset($data['hierarchicalData']) || isset($data['territories'])) {
                    // Les données sont dans une structure hiérarchique - fusionner directement
                    $data = $this->mergeObjectivesWithData($data, 'ENCOURS_CREDIT', $mergeYear, $mergeMonth);
                    Log::info('✅ Données ENCOURS_CREDIT fusionnées retournées au client (structure hiérarchique)');
                    return response()->json($data);
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    $actualData = $data['data'];
                }
                
                $actualData = $this->mergeObjectivesWithData($actualData, 'ENCOURS_CREDIT', $mergeYear, $mergeMonth);
                
                // Remettre les données fusionnées dans la structure originale
                if (isset($data['data'])) {
                    $data['data'] = $actualData;
                } else {
                    $data = $actualData;
                }
                
                Log::info('✅ Données ENCOURS CRÉDIT fusionnées retournées au client');
                
                return response()->json($data);
            }
            
            // En cas d'erreur, retourner le message d'erreur
            $errorData = $response->json();
            Log::error('Erreur API Python Encours Crédit', [
                'status' => $response->status(),
                'error' => $errorData
            ]);
            
            return response()->json([
                'error' => 'Erreur lors de la récupération des données',
                'detail' => $errorData['detail'] ?? $response->body(),
                'status' => $response->status()
            ], $response->status() ?: 500);
            
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'appel API Python Encours Crédit', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Erreur de connexion au service Python',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Récupère les données de collection depuis Oracle via l'API Python
     */
    public function getCollectionData(Request $request): JsonResponse
    {
        try {
            // Construire l'URL de l'API Python
            $apiUrl = $this->pythonServiceUrl . '/api/oracle/data/collection';
            
            // Récupérer les paramètres de la requête
            $period = $request->input('period', 'month');
            $zone = $request->input('zone');
            $month = $request->input('month');
            $year = $request->input('year');
            $date = $request->input('date'); // Pour la période "week"
            
            // Construire les paramètres de requête
            $params = ['period' => $period];
            if ($zone) {
                $params['zone'] = $zone;
            }
            if ($month) {
                $params['month'] = $month;
            }
            if ($year) {
                $params['year'] = $year;
            }
            if ($date) {
                $params['date'] = $date;
            }
            
            // Faire l'appel à l'API Python (timeout de 5 minutes pour les requêtes complexes)
            $response = Http::timeout(300)->get($apiUrl, $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('✅ Données collection récupérées avec succès');
                
                return response()->json($data);
            }
            
            // En cas d'erreur, retourner le message d'erreur
            $errorData = $response->json();
            Log::error('Erreur API Python Collection', [
                'status' => $response->status(),
                'error' => $errorData
            ]);
            
            return response()->json([
                'error' => 'Erreur lors de la récupération des données',
                'detail' => $errorData['detail'] ?? $response->body(),
                'status' => $response->status()
            ], $response->status() ?: 500);
            
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'appel API Python Collection', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Erreur de connexion au service Python',
                'detail' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Récupère les données d'une table spécifique
     */
    public function getTableData(Request $request, string $tableName): JsonResponse
    {
        $limit = $request->input('limit', 100);
        $offset = $request->input('offset', 0);

        $result = $this->oracleService->table($tableName, $limit, $offset);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => $result['error'],
            'message' => $result['message']
        ], 500);
    }
}


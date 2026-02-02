<?php

namespace App\Http\Controllers;

use App\Services\OracleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OracleController extends Controller
{
    protected $oracleService;

    public function __construct(OracleService $oracleService)
    {
        $this->oracleService = $oracleService;
    }

    /**
     * Teste la connexion Oracle
     */
    public function test(): JsonResponse
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
     * Récupère la liste des tables
     */
    public function tables(): JsonResponse
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
    public function query(Request $request): JsonResponse
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
     * Récupère les données d'une table
     */
    public function table(Request $request, string $tableName): JsonResponse
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

    /**
     * Récupère les données clients
     */
    public function clients(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');
        $zone = $request->input('zone');

        $result = $this->oracleService->getClientsData($period, $zone);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => $result['error'],
            'message' => $result['message']
        ], 500);
    }

    /**
     * Récupère les données de production
     */
    public function production(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');

        $result = $this->oracleService->getProductionData($period);

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => $result['error'],
            'message' => $result['message']
        ], 500);
    }
}


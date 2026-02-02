<?php

if (!function_exists('oracle')) {
    /**
     * Helper pour accéder facilement au service Oracle
     * 
     * @return \App\Services\OracleService
     */
    function oracle()
    {
        return app(\App\Services\OracleService::class);
    }
}


"""
Service pour la gestion des données Volume DAT
"""
import logging
from typing import Optional, Dict
from datetime import datetime
import calendar
from database.oracle import get_oracle_connection
from services.utils import get_territory_from_agency, get_territory_from_branch_code, get_territory_key, get_all_territories

logger = logging.getLogger(__name__)


def get_volume_dat_data(period: str = "month", zone: Optional[str] = None, 
                        month: Optional[int] = None, year: Optional[int] = None, date: Optional[str] = None):
    """
    Récupère les données Volume DAT depuis Oracle
    
    Args:
        period: Période d'analyse ("month", "year", "week")
        zone: Zone géographique (optionnel)
        month: Mois à analyser (1-12)
        year: Année à analyser
        date: Date pour la période semaine (format YYYY-MM-DD)
    
    Returns:
        Dictionnaire avec les données Volume DAT organisées par zones
    """
    logger.info(f"🔍 get_volume_dat_data appelé avec period={period}, zone={zone}, month={month}, year={year}, date={date}")
    
    # Utiliser le mois et l'année actuels si non fournis
    if not month or not year:
        now = datetime.now()
        month = month or now.month
        year = year or now.year
    
    # Calculer les dates M et M-1
    if period == "month":
        # Date de fin du mois M
        m_end = datetime(year, month, calendar.monthrange(year, month)[1])
        # Date de fin du mois M-1
        if month == 1:
            m1_end = datetime(year - 1, 12, calendar.monthrange(year - 1, 12)[1])
        else:
            m1_end = datetime(year, month - 1, calendar.monthrange(year, month - 1)[1])
    elif period == "year":
        # Date de fin de l'année M
        m_end = datetime(year, 12, 31)
        # Date de fin de l'année M-1
        m1_end = datetime(year - 1, 12, 31)
    elif period == "week":
        # Pour la semaine, utiliser la date fournie ou aujourd'hui
        if date:
            try:
                reference_date = datetime.strptime(date, "%Y-%m-%d")
            except (ValueError, TypeError):
                reference_date = datetime.now()
        else:
            reference_date = datetime.now()
        
        # Trouver le dimanche de la semaine (fin de semaine)
        from datetime import timedelta
        days_since_monday = reference_date.weekday()
        sunday = reference_date + timedelta(days=(6 - days_since_monday))
        m_end = sunday
        
        # Semaine précédente
        m1_end = sunday - timedelta(days=7)
    else:
        # Par défaut, utiliser le mois actuel
        now = datetime.now()
        m_end = datetime(now.year, now.month, calendar.monthrange(now.year, now.month)[1])
        if now.month == 1:
            m1_end = datetime(now.year - 1, 12, calendar.monthrange(now.year - 1, 12)[1])
        else:
            m1_end = datetime(now.year, now.month - 1, calendar.monthrange(now.year, now.month - 1)[1])
    
    # Formater les dates pour Oracle (DD/MM/YYYY)
    m_end_str = m_end.strftime("%d/%m/%Y")
    m1_end_str = m1_end.strftime("%d/%m/%Y")
    
    # Calculer les dates de début du mois M pour les dettes rattachées
    if period == "month":
        m_start = datetime(year, month, 1)
    elif period == "year":
        m_start = datetime(year, 1, 1)
    elif period == "week":
        # Pour la semaine, utiliser le lundi de la semaine
        from datetime import timedelta
        days_since_monday = reference_date.weekday()
        monday = reference_date - timedelta(days=days_since_monday)
        m_start = monday
    else:
        now = datetime.now()
        m_start = datetime(now.year, now.month, 1)
    
    m_start_str = m_start.strftime("%d/%m/%Y")
    
    logger.info(f"📅 Dates calculées: M début={m_start_str}, M fin={m_end_str}, M-1 fin={m1_end_str}")
    
    # Utiliser le pool de connexions et le cache
    from database.oracle_pool import get_pool
    from services.cache_service import get_cache, set_cache, generate_cache_key
    
    # Générer une clé de cache basée sur les paramètres
    cache_key = f"volume_dat:{generate_cache_key(period, zone, month, year, date)}"
    
    # Vérifier le cache
    cached_result = get_cache(cache_key)
    if cached_result is not None:
        logger.info("✅ Données Volume DAT récupérées depuis le cache")
        return cached_result
    
    pool = get_pool()
    with pool.get_connection_context() as conn:
        cursor = conn.cursor()
        
        # Optimisations Oracle
        cursor.arraysize = 1000
        cursor.prefetchrows = 1000
        
        try:
            logger.info("🔍 Exécution de la requête Volume DAT...")
            
            # Construire la requête SQL avec les dates dynamiques
            query = f"""
WITH Journal AS (
    SELECT
        TRN_REF_NO, AC_ENTRY_SR_NO, EVENT_SR_NO, EVENT, AC_BRANCH, AC_NO, AC_CCY, CATEGORY, DRCR_IND, TRN_CODE, FCY_AMOUNT, EXCH_RATE, LCY_AMOUNT, VALUE_DT AS TRN_DT, VALUE_DT, TXN_INIT_DATE, AMOUNT_TAG, RELATED_ACCOUNT, RELATED_CUSTOMER, RELATED_REFERENCE, MIS_HEAD, MIS_FLAG, INSTRUMENT_CODE, BANK_CODE, BALANCE_UPD, AUTH_STAT, MODULE, CUST_GL, DLY_HIST, FINANCIAL_CYCLE, PERIOD_CODE, BATCH_NO, USER_ID, CURR_NO, PRINT_STAT, AUTH_ID, GLMIS_VAL_UPD_FLAG, EXTERNAL_REF_NO, DONT_SHOWIN_STMT, IC_BAL_INCLUSION, AML_EXCEPTION, IB, GLMIS_UPDATE_FLAG, PRODUCT_ACCRUAL, ORIG_PNL_GL, STMT_DT, ENTRY_SEQ_NO, VIRTUAL_AC_NO, CLAIM_AMOUNT, GRP_REF_NO, SAVE_TIMESTAMP, AUTH_TIMESTAMP, PRODUCT_PROCESSOR, RELATED_AC_ENTRY_SR_NO, DONT_SHOWIN_STMT_FEE, ORG_SOURCE, ORG_SOURCE_REF, SOURCE_CODE
    FROM CFSFCUBS145.ACVW_ALL_AC_ENTRIES 
    WHERE MODULE = 'DE'
 
    UNION
 
    SELECT
        TRN_REF_NO, AC_ENTRY_SR_NO, EVENT_SR_NO, EVENT, AC_BRANCH, AC_NO, AC_CCY, CATEGORY, DRCR_IND, TRN_CODE, FCY_AMOUNT, EXCH_RATE, LCY_AMOUNT, TRN_DT, VALUE_DT, TXN_INIT_DATE, AMOUNT_TAG, RELATED_ACCOUNT, RELATED_CUSTOMER, RELATED_REFERENCE, MIS_HEAD, MIS_FLAG, INSTRUMENT_CODE, BANK_CODE, BALANCE_UPD, AUTH_STAT, MODULE, CUST_GL, DLY_HIST, FINANCIAL_CYCLE, PERIOD_CODE, BATCH_NO, USER_ID, CURR_NO, PRINT_STAT, AUTH_ID, GLMIS_VAL_UPD_FLAG, EXTERNAL_REF_NO, DONT_SHOWIN_STMT, IC_BAL_INCLUSION, AML_EXCEPTION, IB, GLMIS_UPDATE_FLAG, PRODUCT_ACCRUAL, ORIG_PNL_GL, STMT_DT, ENTRY_SEQ_NO, VIRTUAL_AC_NO, CLAIM_AMOUNT, GRP_REF_NO, SAVE_TIMESTAMP, AUTH_TIMESTAMP, PRODUCT_PROCESSOR, RELATED_AC_ENTRY_SR_NO, DONT_SHOWIN_STMT_FEE, ORG_SOURCE, ORG_SOURCE_REF, SOURCE_CODE
    FROM CFSFCUBS145.ACVW_ALL_AC_ENTRIES 
    WHERE MODULE <> 'DE'
),

JOURNAL AS (
    SELECT
        AC_ENTRY_SR_NO,
        AC_NO,
        AC_BRANCH,
        DRCR_IND,
        LCY_AMOUNT,
        AMOUNT_TAG,
        RELATED_ACCOUNT,
        TRN_CODE,
        TRN_DT,
        MODULE
    FROM Journal
),
 
COMPTE AS (
    SELECT 
        cpt.BRANCH_CODE,
        cpt.CUST_AC_NO,
        cpt.AC_DESC,
        cs.ACCOUNT_CLASS,
        cs.DESCRIPTION,
        cs.ACCOUNT_CODE
    FROM CFSFCUBS145.STTM_CUST_ACCOUNT cpt
    JOIN CFSFCUBS145.STTM_ACCOUNT_CLASS cs 
        ON cpt.ACCOUNT_CLASS = cs.ACCOUNT_CLASS
),
 
BRANCH AS (
    SELECT  
        BRANCH_CODE,
        BRANCH_NAME
    FROM CFSFCUBS145.STTM_BRANCH
),

-- DETTES RATTACHEES DAT
RESUL_DETTES_RAT_DAT AS (
    SELECT 
        ar.AC_BRANCH as CODE_AGENCE,
        b.BRANCH_NAME as AGENCE,
        SUM(ar.LCY_AMOUNT) as VOLUME_DETTES_RATTACHEES_DAT_M
    FROM Journal ar
    LEFT JOIN BRANCH b ON b.BRANCH_CODE = ar.AC_BRANCH
    WHERE ar.AMOUNT_TAG='IACR'
        AND ar.RELATED_ACCOUNT LIKE '252%'
        AND ar.DRCR_IND='D'
        AND ar.TRN_CODE='045'
        AND ar.AC_NO='602520000001'
        AND ar.TRN_DT BETWEEN TO_DATE('{m_start_str}','DD/MM/YYYY') AND TO_DATE('{m_end_str}','DD/MM/YYYY')
    GROUP BY ar.AC_BRANCH, b.BRANCH_NAME
),
 
-- DAT
DAT AS (
    SELECT 
        y.BRANCH_CODE,
        SUM(CASE WHEN a.DRCR_IND = 'C' AND a.TRN_DT <= TO_DATE('{m_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END)
      - SUM(CASE WHEN a.DRCR_IND = 'D' AND a.TRN_DT <= TO_DATE('{m_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END) AS M,
        SUM(CASE WHEN a.DRCR_IND = 'C' AND a.TRN_DT <= TO_DATE('{m1_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END)
      - SUM(CASE WHEN a.DRCR_IND = 'D' AND a.TRN_DT <= TO_DATE('{m1_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END) AS M_1
    FROM JOURNAL a
    JOIN COMPTE y ON a.AC_NO = y.CUST_AC_NO
    WHERE y.ACCOUNT_CODE = '252'
    GROUP BY y.BRANCH_CODE
)

SELECT 
    O.BRANCH_CODE,
    BR.BRANCH_NAME as AGENCE,
    O.M_1 as DAT_M_1,
    O.M as DAT_M,
    (O.M - O.M_1) as VARIATION_VOLUME_DA,
    NVL(ROUND(
        (((O.M - O.M_1)) / NULLIF(O.M_1, 0)) * 100, 
        2
    ), 0) AS "VARIATION_DAT%",
    NVL(D.DETTES_RATTACHEES_DAT_M, 0) as DETTES_RATTACHEES_DAT_M
FROM DAT O
LEFT JOIN BRANCH BR ON BR.BRANCH_CODE = O.BRANCH_CODE
LEFT JOIN (
    SELECT CODE_AGENCE, SUM(VOLUME_DETTES_RATTACHEES_DAT_M) as DETTES_RATTACHEES_DAT_M
    FROM RESUL_DETTES_RAT_DAT
    GROUP BY CODE_AGENCE
) D ON D.CODE_AGENCE = O.BRANCH_CODE
ORDER BY BR.BRANCH_NAME
"""
            
            logger.info(f"⏱️  Exécution de la requête Volume DAT (timeout: 5 minutes)")
            logger.info(f"📅 Dates utilisées pour dettes rattachées: {m_start_str} à {m_end_str}")
            cursor.execute(query)
            
            # Récupérer les résultats
            columns = [desc[0] for desc in cursor.description]
            data = []
            for row in cursor.fetchall():
                row_dict = dict(zip(columns, row))
                data.append(row_dict)
            
            logger.info(f"📊 {len(data)} lignes récupérées depuis Oracle")
            
            # Log des premières lignes pour déboguer
            if len(data) > 0:
                sample_row = data[0]
                logger.info(f"🔍 Exemple de données récupérées: BRANCH_CODE={sample_row.get('BRANCH_CODE')}, AGENCE={sample_row.get('AGENCE')}, DETTES_RATTACHEES_DAT_M={sample_row.get('DETTES_RATTACHEES_DAT_M')}")
            
            if len(data) == 0:
                logger.warning("⚠️ Aucune donnée Volume DAT trouvée")
                return {
                    "hierarchicalData": {
                        "TERRITOIRE": {},
                        "POINT SERVICES": {}
                    }
                }
            
            # Organiser les données par territoire et point de service
            agencies_by_territory = {
                'territoire_dakar_ville': [],
                'territoire_dakar_banlieue': [],
                'territoire_province_centre_sud': [],
                'territoire_province_nord': []
            }
            
            agencies_by_service_point = {}
            grand_compte = None
            
            for row in data:
                branch_code = row.get('BRANCH_CODE') or row.get('branch_code')
                agency_name = row.get('AGENCE') or row.get('agence') or ''
                
                # Créer l'objet agence
                # Gérer la colonne VARIATION_DAT% qui peut être retournée avec ou sans %
                variation_dat_value = row.get('VARIATION_DAT%') or row.get('VARIATION_DAT') or row.get('"VARIATION_DAT%"') or 0
                agency = {
                    'BRANCH_CODE': branch_code,
                    'AGENCE': agency_name,
                    'name': agency_name,
                    'DAT_M_1': float(row.get('DAT_M_1') or 0),
                    'DAT_M': float(row.get('DAT_M') or 0),
                    'VARIATION_VOLUME_DA': float(row.get('VARIATION_VOLUME_DA') or 0),
                    'VARIATION_DAT': float(variation_dat_value),
                    'VARIATION_DAT%': float(variation_dat_value),  # Alias pour compatibilité
                    'DETTES_RATTACHEES_DAT_M': float(row.get('DETTES_RATTACHEES_DAT_M') or 0),
                    'DETTES_RATTACHEES_DAT': float(row.get('DETTES_RATTACHEES_DAT_M') or 0)  # Alias
                }
                
                # Déterminer le territoire
                territory = get_territory_from_branch_code(branch_code)
                territory_key = get_territory_key(territory)
                
                # Vérifier si c'est le grand compte
                if agency_name and 'GRAND COMPTE' in agency_name.upper():
                    grand_compte = agency
                    continue
                
                # Vérifier si c'est un point de service
                if territory == 'POINT SERVICES':
                    # Utiliser le nom de l'agence comme clé pour les points de service
                    service_point_key = agency_name or branch_code
                    if service_point_key not in agencies_by_service_point:
                        agencies_by_service_point[service_point_key] = []
                    agencies_by_service_point[service_point_key].append(agency)
                else:
                    # Ajouter à la liste du territoire approprié
                    if territory_key in agencies_by_territory:
                        agencies_by_territory[territory_key].append(agency)
            
            # Calculer les totaux pour chaque territoire
            def calculate_territory_totals(agencies_list):
                """Calcule les totaux pour un territoire"""
                totals = {
                    'datM1': 0,
                    'datM': 0,
                    'variationVolumeDa': 0,
                    'variationDat': 0,
                    'dettesRattacheesDat': 0
                }
                for agency in agencies_list:
                    totals['datM1'] += float(agency.get('DAT_M_1', 0) or 0)
                    totals['datM'] += float(agency.get('DAT_M', 0) or 0)
                    totals['variationVolumeDa'] += float(agency.get('VARIATION_VOLUME_DA', 0) or 0)
                    totals['variationDat'] += float(agency.get('VARIATION_DAT', 0) or 0)
                    totals['dettesRattacheesDat'] += float(agency.get('DETTES_RATTACHEES_DAT_M', 0) or agency.get('DETTES_RATTACHEES_DAT', 0) or 0)
                return totals
            
            # Construire la structure hiérarchique
            response_data = {
                "hierarchicalData": {
                    "TERRITOIRE": {},
                    "POINT SERVICES": {}
                }
            }
            
            # Ajouter les territoires
            if any(agencies_by_territory.values()):
                response_data["hierarchicalData"]["TERRITOIRE"] = {
                    "territoire_dakar_ville": {
                        "name": "DAKAR CENTRE VILLE",
                        "agencies": agencies_by_territory['territoire_dakar_ville'],
                        "totals": calculate_territory_totals(agencies_by_territory['territoire_dakar_ville'])
                    },
                    "territoire_dakar_banlieue": {
                        "name": "DAKAR BANLIEUE",
                        "agencies": agencies_by_territory['territoire_dakar_banlieue'],
                        "totals": calculate_territory_totals(agencies_by_territory['territoire_dakar_banlieue'])
                    },
                    "province_centre_sud": {
                        "name": "PROVINCE CENTRE SUD",
                        "agencies": agencies_by_territory['territoire_province_centre_sud'],
                        "totals": calculate_territory_totals(agencies_by_territory['territoire_province_centre_sud'])
                    },
                    "province_nord": {
                        "name": "PROVINCE NORD",
                        "agencies": agencies_by_territory['territoire_province_nord'],
                        "totals": calculate_territory_totals(agencies_by_territory['territoire_province_nord'])
                    }
                }
                
                # Ajouter le grand compte dans TERRITOIRE si présent
                if grand_compte:
                    response_data["hierarchicalData"]["TERRITOIRE"]["grand_compte"] = {
                        "name": "GRAND COMPTE",
                        "agencies": [grand_compte],
                        "totals": {
                            'datM1': grand_compte.get('DAT_M_1', 0),
                            'datM': grand_compte.get('DAT_M', 0),
                            'variationVolumeDa': grand_compte.get('VARIATION_VOLUME_DA', 0),
                            'variationDat': grand_compte.get('VARIATION_DAT', 0),
                            'dettesRattacheesDat': grand_compte.get('DETTES_RATTACHEES_DAT_M', 0) or grand_compte.get('DETTES_RATTACHEES_DAT', 0) or 0
                        }
                    }
            
            # Ajouter les points de service
            if agencies_by_service_point:
                for service_point_key, agencies in agencies_by_service_point.items():
                    response_data["hierarchicalData"]["POINT SERVICES"][service_point_key] = {
                        "name": service_point_key,
                        "agencies": agencies,
                        "totals": calculate_territory_totals(agencies)
                    }
            
            # Mettre en cache le résultat (TTL de 5 minutes)
            set_cache(cache_key, response_data, ttl=300)
            
            logger.info(f"✅ Données Volume DAT récupérées: {len(data)} agences")
            return response_data
            
        except Exception as e:
            logger.error(f"❌ Erreur lors de la récupération des données Volume DAT: {str(e)}", exc_info=True)
            raise

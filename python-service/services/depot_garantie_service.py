"""
Service pour la gestion des données Dépôt de Garantie
"""
import logging
from typing import Optional, Dict
from datetime import datetime
import calendar
from database.oracle import get_oracle_connection
from services.utils import get_territory_from_agency, get_territory_from_branch_code, get_territory_key, get_all_territories

logger = logging.getLogger(__name__)


def get_depot_garantie_data(period: str = "month", zone: Optional[str] = None, 
                            month: Optional[int] = None, year: Optional[int] = None, date: Optional[str] = None):
    """
    Récupère les données Dépôt de Garantie depuis Oracle
    
    Args:
        period: Période d'analyse ("month", "year", "week")
        zone: Zone géographique (optionnel)
        month: Mois à analyser (1-12)
        year: Année à analyser
        date: Date pour la période semaine (format YYYY-MM-DD)
    
    Returns:
        Dictionnaire avec les données Dépôt de Garantie organisées par zones
    """
    logger.info(f"🔍 get_depot_garantie_data appelé avec period={period}, zone={zone}, month={month}, year={year}, date={date}")
    
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
    
    logger.info(f"📅 Dates calculées: M fin={m_end_str}, M-1 fin={m1_end_str}")
    
    # Utiliser le pool de connexions et le cache
    from database.oracle_pool import get_pool
    from services.cache_service import get_cache, set_cache, generate_cache_key
    
    # Générer une clé de cache basée sur les paramètres
    cache_key = f"depot_garantie:{generate_cache_key(period, zone, month, year, date)}"
    
    # Vérifier le cache
    cached_result = get_cache(cache_key)
    if cached_result is not None:
        logger.info("✅ Données Dépôt de Garantie récupérées depuis le cache")
        return cached_result
    
    pool = get_pool()
    with pool.get_connection_context() as conn:
        cursor = conn.cursor()
        
        # Optimisations Oracle
        cursor.arraysize = 1000
        cursor.prefetchrows = 1000
        
        try:
            logger.info("🔍 Exécution de la requête Dépôt de Garantie...")
            
            # Construire la requête SQL avec les dates dynamiques
            query = f"""
WITH JOURNAL AS (
    SELECT
        AC_ENTRY_SR_NO,
        AC_NO,
        DRCR_IND,
        LCY_AMOUNT,
        CASE 
            WHEN MODULE = 'DE' THEN VALUE_DT 
            ELSE TRN_DT 
        END AS TRN_DT
    FROM CFSFCUBS145.ACVW_ALL_AC_ENTRIES
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
 
-- Compte Courant
CPT_COURANT AS (
    SELECT 
        y.BRANCH_CODE,
        SUM(CASE WHEN a.DRCR_IND = 'C' AND a.TRN_DT <= TO_DATE('{m_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END)
      - SUM(CASE WHEN a.DRCR_IND = 'D' AND a.TRN_DT <= TO_DATE('{m_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END) AS M,
        SUM(CASE WHEN a.DRCR_IND = 'C' AND a.TRN_DT <= TO_DATE('{m1_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END)
      - SUM(CASE WHEN a.DRCR_IND = 'D' AND a.TRN_DT <= TO_DATE('{m1_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END) AS M_1
    FROM JOURNAL a
    JOIN COMPTE y ON a.AC_NO = y.CUST_AC_NO
    WHERE y.ACCOUNT_CODE = '251'
    GROUP BY y.BRANCH_CODE
),

-- Compte Épargne Projet
EPARGNE_PROJET AS (
    SELECT 
        y.BRANCH_CODE,
        SUM(CASE WHEN a.DRCR_IND = 'C' AND a.TRN_DT <= TO_DATE('{m_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END)
      - SUM(CASE WHEN a.DRCR_IND = 'D' AND a.TRN_DT <= TO_DATE('{m_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END) AS M,
        SUM(CASE WHEN a.DRCR_IND = 'C' AND a.TRN_DT <= TO_DATE('{m1_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END)
      - SUM(CASE WHEN a.DRCR_IND = 'D' AND a.TRN_DT <= TO_DATE('{m1_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END) AS M_1
    FROM JOURNAL a
    JOIN COMPTE y ON a.AC_NO = y.CUST_AC_NO
    WHERE y.ACCOUNT_CODE = '253'
      AND UPPER(y.DESCRIPTION) LIKE '%PROJET%'
    GROUP BY y.BRANCH_CODE
),

-- Compte Épargne (autres)
CPT_EPARGNE AS (
    SELECT 
        y.BRANCH_CODE,
        SUM(CASE WHEN a.DRCR_IND = 'C' AND a.TRN_DT <= TO_DATE('{m_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END)
      - SUM(CASE WHEN a.DRCR_IND = 'D' AND a.TRN_DT <= TO_DATE('{m_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END) AS M,
        SUM(CASE WHEN a.DRCR_IND = 'C' AND a.TRN_DT <= TO_DATE('{m1_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END)
      - SUM(CASE WHEN a.DRCR_IND = 'D' AND a.TRN_DT <= TO_DATE('{m1_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END) AS M_1
    FROM JOURNAL a
    JOIN COMPTE y ON a.AC_NO = y.CUST_AC_NO
    WHERE y.ACCOUNT_CODE = '253'
      AND UPPER(y.DESCRIPTION) NOT LIKE '%PROJET%'
    GROUP BY y.BRANCH_CODE
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
),

-- Dépôt de Garantie
DEPOT_GARANTIE AS (
    SELECT 
        y.BRANCH_CODE,
        SUM(CASE WHEN a.DRCR_IND = 'C' AND a.TRN_DT <= TO_DATE('{m_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END)
      - SUM(CASE WHEN a.DRCR_IND = 'D' AND a.TRN_DT <= TO_DATE('{m_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END) AS M,
        SUM(CASE WHEN a.DRCR_IND = 'C' AND a.TRN_DT <= TO_DATE('{m1_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END)
      - SUM(CASE WHEN a.DRCR_IND = 'D' AND a.TRN_DT <= TO_DATE('{m1_end_str}','DD/MM/YYYY') THEN NVL(a.LCY_AMOUNT,0) ELSE 0 END) AS M_1
    FROM JOURNAL a
    JOIN COMPTE y ON a.AC_NO = y.CUST_AC_NO
    WHERE y.ACCOUNT_CODE = '254'
    GROUP BY y.BRANCH_CODE
),

-- Somme des encours par agence (sans ORDER BY dans le CTE)
depot AS (
    SELECT
        b.BRANCH_CODE,
        A.BRANCH_NAME,
        NVL(c.M, 0) AS M_ENCOURS_COMPTE_COURANT,
        NVL(e.M, 0) AS M_ENCOURS_COMPTE_EPARGNE,
        NVL(p.M, 0) AS M_ENCOURS_COMPTE_EPARGNE_PROJET,
        NVL(d.M, 0) AS M_ENCOURS_DAT,
        NVL(g.M, 0) AS M_ENCOURS_DEPOT_GARANTIE,
        NVL(c.M_1, 0) AS M1_ENCOURS_COMPTE_COURANT,
        NVL(e.M_1, 0) AS M1_ENCOURS_COMPTE_EPARGNE,
        NVL(p.M_1, 0) AS M1_ENCOURS_COMPTE_EPARGNE_PROJET,
        NVL(d.M_1, 0) AS M1_ENCOURS_DAT,
        NVL(g.M_1, 0) AS M1_ENCOURS_DEPOT_GARANTIE
    FROM (SELECT DISTINCT BRANCH_CODE FROM COMPTE) b
    LEFT JOIN CPT_COURANT c ON b.BRANCH_CODE = c.BRANCH_CODE
    LEFT JOIN CPT_EPARGNE e ON b.BRANCH_CODE = e.BRANCH_CODE
    LEFT JOIN EPARGNE_PROJET p ON b.BRANCH_CODE = p.BRANCH_CODE
    LEFT JOIN DAT d ON b.BRANCH_CODE = d.BRANCH_CODE
    LEFT JOIN DEPOT_GARANTIE g ON b.BRANCH_CODE = g.BRANCH_CODE
    LEFT JOIN BRANCH A ON b.BRANCH_CODE = A.BRANCH_CODE
),

DEBLOCAGE AS (
    -- On convertit SCHEDULE_LINKAGE en date si c'est stocké sous forme texte 'DD/MM/YYYY'
    SELECT
        ACCOUNT_NUMBER,
        COALESCE(DTYPE, 'VIDE') AS DTYPE,
        MAX(SCHEDULE_LINKAGE) AS SCHEDULE_LINKAGE
    FROM CFSFCUBS145.CLTB_DISBR_SCHEDULES
    WHERE (DTYPE <> 'X' OR DTYPE IS NULL)
    GROUP BY ACCOUNT_NUMBER, COALESCE(DTYPE, 'VIDE')
),

ENCOURS_M AS (
    SELECT 
        c.ACCOUNT_NUMBER AS NO_PRET,
        c.BRANCH_CODE,
        SUM(NVL(z.AMOUNT_DUE,0)) AS MT_CAPITAL_TA,
        SUM(NVL(z.AMOUNT_DUE,0) - NVL(z.AMOUNT_SETTLED,0)) AS ENCOURS_TOTAL_M,
        SUM(CASE WHEN c.USER_DEFINED_STATUS IN ('NORM', 'IMPA') 
                 THEN (NVL(z.AMOUNT_DUE,0) - NVL(z.AMOUNT_SETTLED,0)) ELSE 0 END) AS ENCOURS_SAIN,
        SUM(CASE WHEN c.USER_DEFINED_STATUS NOT IN ('NORM', 'IMPA') 
                 THEN (NVL(z.AMOUNT_DUE,0) - NVL(z.AMOUNT_SETTLED,0)) ELSE 0 END) AS ENCOURS_IMPAYE
    FROM CFSFCUBS145.CLTB_ACCOUNT_MASTER c
    LEFT JOIN CFSFCUBS145.CLTB_ACCOUNT_SCHEDULES z 
           ON z.ACCOUNT_NUMBER = c.ACCOUNT_NUMBER
    LEFT JOIN DEBLOCAGE d 
           ON d.ACCOUNT_NUMBER = c.ACCOUNT_NUMBER
    WHERE 
        c.ACCOUNT_STATUS NOT IN ('L', 'V')
        AND z.COMPONENT_NAME = 'PRINCIPAL'
        AND (d.SCHEDULE_LINKAGE IS NULL OR TO_DATE(d.SCHEDULE_LINKAGE,'DD/MM/YYYY') <= TO_DATE('{m_end_str}','DD/MM/YYYY'))
    GROUP BY c.ACCOUNT_NUMBER, c.BRANCH_CODE
),

ENCOURS_M_1 AS (
    SELECT 
        c.ACCOUNT_NUMBER AS NO_PRET,
        c.BRANCH_CODE,
        SUM(NVL(z.AMOUNT_DUE,0)) AS MT_CAPITAL_TA,
        SUM(NVL(z.AMOUNT_DUE,0) - NVL(z.AMOUNT_SETTLED,0)) AS ENCOURS_TOTAL_M_1,
        SUM(CASE WHEN c.USER_DEFINED_STATUS IN ('NORM', 'IMPA') 
                 THEN (NVL(z.AMOUNT_DUE,0) - NVL(z.AMOUNT_SETTLED,0)) ELSE 0 END) AS ENCOURS_SAIN,
        SUM(CASE WHEN c.USER_DEFINED_STATUS NOT IN ('NORM', 'IMPA') 
                 THEN (NVL(z.AMOUNT_DUE,0) - NVL(z.AMOUNT_SETTLED,0)) ELSE 0 END) AS ENCOURS_IMPAYE
    FROM CFSFCUBS145.CLTB_ACCOUNT_MASTER c
    LEFT JOIN CFSFCUBS145.CLTB_ACCOUNT_SCHEDULES z 
           ON z.ACCOUNT_NUMBER = c.ACCOUNT_NUMBER
    LEFT JOIN DEBLOCAGE d 
           ON d.ACCOUNT_NUMBER = c.ACCOUNT_NUMBER
    WHERE 
        c.ACCOUNT_STATUS NOT IN ('L', 'V')
        AND z.COMPONENT_NAME = 'PRINCIPAL'
        AND (d.SCHEDULE_LINKAGE IS NULL OR TO_DATE(d.SCHEDULE_LINKAGE,'DD/MM/YYYY') <= TO_DATE('{m1_end_str}','DD/MM/YYYY'))
    GROUP BY c.ACCOUNT_NUMBER, c.BRANCH_CODE
),

encours_credit AS (
    SELECT
      COALESCE(e1.BRANCH_CODE, e.BRANCH_CODE) AS BRANCH_CODE,
      br.BRANCH_NAME,
      SUM(NVL(e.ENCOURS_TOTAL_M,0)) AS ENCOURS_TOTAL_M,
      SUM(NVL(e1.ENCOURS_TOTAL_M_1,0)) AS ENCOURS_TOTAL_M_1,
      SUM(NVL(e.ENCOURS_TOTAL_M,0)) - SUM(NVL(e1.ENCOURS_TOTAL_M_1,0)) AS VARIATION_ENCOURS_CREDIT,
      SUM(NVL(e.ENCOURS_SAIN,0)) AS ENCOURS_SAIN_M,
      SUM(NVL(e.ENCOURS_IMPAYE,0)) AS ENCOURS_IMPAYE_M
    FROM  ENCOURS_M e
    LEFT JOIN ENCOURS_M_1 e1 ON e1.NO_PRET = e.NO_PRET
    LEFT JOIN BRANCH br ON br.BRANCH_CODE = COALESCE(e1.BRANCH_CODE, e.BRANCH_CODE)
    GROUP BY COALESCE(e1.BRANCH_CODE, e.BRANCH_CODE), br.BRANCH_NAME
)

SELECT 
    o.BRANCH_CODE,
    o.BRANCH_NAME,
    NVL(v.ENCOURS_TOTAL_M,0)         AS ENCOURS_TOTAL_M,
    o.M1_ENCOURS_DEPOT_GARANTIE,
    o.M_ENCOURS_DEPOT_GARANTIE
FROM depot o
LEFT JOIN encours_credit v ON o.BRANCH_CODE = v.BRANCH_CODE
ORDER BY o.BRANCH_CODE, o.BRANCH_NAME
"""
            
            logger.info(f"⏱️  Exécution de la requête Dépôt de Garantie (timeout: 5 minutes)")
            cursor.execute(query)
            
            # Récupérer les résultats
            columns = [desc[0] for desc in cursor.description]
            data = []
            for row in cursor.fetchall():
                row_dict = dict(zip(columns, row))
                data.append(row_dict)
            
            logger.info(f"📊 {len(data)} lignes récupérées depuis Oracle")
            
            if len(data) == 0:
                logger.warning("⚠️ Aucune donnée Dépôt de Garantie trouvée")
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
                agency_name = row.get('BRANCH_NAME') or row.get('branch_name') or ''
                
                # Créer l'objet agence
                agency = {
                    'BRANCH_CODE': branch_code,
                    'BRANCH_NAME': agency_name,
                    'name': agency_name,
                    'M1_ENCOURS_DEPOT_GARANTIE': float(row.get('M1_ENCOURS_DEPOT_GARANTIE') or 0),
                    'M_ENCOURS_DEPOT_GARANTIE': float(row.get('M_ENCOURS_DEPOT_GARANTIE') or 0),
                    'ENCOURS_TOTAL_M': float(row.get('ENCOURS_TOTAL_M') or 0)
                }
                
                # Déterminer le territoire
                territory = get_territory_from_branch_code(branch_code)
                if territory is None:
                    territory = 'POINT SERVICES'
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
                    'm1EncoursDepotGarantie': 0,
                    'mEncoursDepotGarantie': 0,
                    'encoursTotalM': 0
                }
                for agency in agencies_list:
                    totals['m1EncoursDepotGarantie'] += float(agency.get('M1_ENCOURS_DEPOT_GARANTIE', 0) or 0)
                    totals['mEncoursDepotGarantie'] += float(agency.get('M_ENCOURS_DEPOT_GARANTIE', 0) or 0)
                    totals['encoursTotalM'] += float(agency.get('ENCOURS_TOTAL_M', 0) or 0)
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
                            'm1EncoursDepotGarantie': grand_compte.get('M1_ENCOURS_DEPOT_GARANTIE', 0),
                            'mEncoursDepotGarantie': grand_compte.get('M_ENCOURS_DEPOT_GARANTIE', 0),
                            'encoursTotalM': grand_compte.get('ENCOURS_TOTAL_M', 0)
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
            
            logger.info(f"✅ Données Dépôt de Garantie récupérées: {len(data)} agences")
            return response_data
            
        except Exception as e:
            logger.error(f"❌ Erreur lors de la récupération des données Dépôt de Garantie: {str(e)}", exc_info=True)
            raise

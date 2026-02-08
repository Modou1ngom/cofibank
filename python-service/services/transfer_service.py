"""
Service pour la gestion des données de transferts d'argent
"""
import logging
from typing import Optional, Dict, List
from datetime import datetime
import calendar
from database.oracle import get_oracle_connection

logger = logging.getLogger(__name__)


def calculate_month_dates(month: int, year: int) -> Dict[str, str]:
    """
    Calcule les dates du mois M et M-1
    
    Args:
        month: Mois (1-12)
        year: Année
        
    Returns:
        Dictionnaire avec les dates au format DD/MM/YYYY et YYYY-MM-DD
    """
    # Premier jour du mois M
    first_day = datetime(year, month, 1)
    # Dernier jour du mois M
    last_day = datetime(year, month, calendar.monthrange(year, month)[1])
    
    # Mois précédent (M-1)
    if month == 1:
        prev_month = 12
        prev_year = year - 1
    else:
        prev_month = month - 1
        prev_year = year
    
    prev_last_day = datetime(prev_year, prev_month, calendar.monthrange(prev_year, prev_month)[1])
    prev_first_day = datetime(prev_year, prev_month, 1)
    
    return {
        'm_debut': first_day.strftime("%d/%m/%Y"),
        'm_fin': last_day.strftime("%d/%m/%Y"),
        'm1_debut': prev_first_day.strftime("%d/%m/%Y"),
        'm1_fin': prev_last_day.strftime("%d/%m/%Y"),
        # Format DATE pour Oracle (YYYY-MM-DD)
        'm_debut_date': first_day.strftime("%Y-%m-%d"),
        'm_fin_date': last_day.strftime("%Y-%m-%d"),
        'm1_debut_date': prev_first_day.strftime("%Y-%m-%d"),
        'm1_fin_date': prev_last_day.strftime("%Y-%m-%d"),
    }


def get_transfer_data(period: str = "month", month: Optional[int] = None, year: Optional[int] = None, date: Optional[str] = None):
    """
    Récupère les données de transferts d'argent depuis Oracle
    
    Args:
        period: Période d'analyse ("week", "month", "year")
        month: Mois à analyser (1-12)
        year: Année à analyser
        date: Date au format YYYY-MM-DD pour period="week"
    
    Returns:
        Dictionnaire avec les données de transferts organisées par agences et services
    """
    logger.info(f"🔍 get_transfer_data appelé avec period={period}, month={month}, year={year}, date={date}")
    
    # Pour l'instant, on ne gère que la période "month"
    if period != "month":
        logger.warning(f"⚠️ Période '{period}' non supportée pour les transferts. Utilisation de 'month' par défaut.")
        period = "month"
    
    # Utiliser le mois et l'année actuels si non fournis
    if not month or not year:
        now = datetime.now()
        month = month or now.month
        year = year or now.year
    
    # Calculer les dates
    dates = calculate_month_dates(month, year)
    
    logger.info(f"📅 Dates calculées: M={dates['m_debut']} à {dates['m_fin']}, M-1={dates['m1_debut']} à {dates['m1_fin']}")
    
    conn = get_oracle_connection()
    cursor = conn.cursor()
    
    try:
        # Requête SQL pour récupérer les données de transferts d'argent
        # Note: Cette requête est un exemple - elle doit être adaptée selon votre structure de base de données Oracle
        query = f"""
        SELECT 
            b.BRANCH_NAME AS AGENCE,
            b.BRANCH_CODE,
            -- Objectif (à définir selon votre logique métier)
            COALESCE(obj.OBJECTIF, 0) AS OBJECTIF,
            -- Volume transfert M (mois en cours)
            COALESCE(SUM(CASE 
                WHEN t.TRANSACTION_DATE BETWEEN DATE '{dates['m_debut_date']}' AND DATE '{dates['m_fin_date']}'
                THEN t.AMOUNT ELSE 0 END), 0) AS VOLUME_TRANSFERT_M,
            -- Volume transfert M-1 (mois précédent)
            COALESCE(SUM(CASE 
                WHEN t.TRANSACTION_DATE BETWEEN DATE '{dates['m1_debut_date']}' AND DATE '{dates['m1_fin_date']}'
                THEN t.AMOUNT ELSE 0 END), 0) AS VOLUME_TRANSFERT_M1,
            -- Commission générée M
            COALESCE(SUM(CASE 
                WHEN t.TRANSACTION_DATE BETWEEN DATE '{dates['m_debut_date']}' AND DATE '{dates['m_fin_date']}'
                THEN t.COMMISSION ELSE 0 END), 0) AS COMMISSION_M,
            -- Service de transfert
            t.SERVICE_NAME AS SERVICE
        FROM TRANSFER_TRANSACTIONS t
        LEFT JOIN STTM_BRANCH b ON b.BRANCH_CODE = t.BRANCH_CODE
        LEFT JOIN TRANSFER_OBJECTIVES obj ON obj.BRANCH_CODE = t.BRANCH_CODE 
            AND obj.MONTH = {month} AND obj.YEAR = {year}
        WHERE t.TRANSACTION_DATE BETWEEN DATE '{dates['m1_debut_date']}' AND DATE '{dates['m_fin_date']}'
        GROUP BY b.BRANCH_NAME, b.BRANCH_CODE, obj.OBJECTIF, t.SERVICE_NAME
        ORDER BY b.BRANCH_NAME, t.SERVICE_NAME
        """
        
        # Note: La requête ci-dessus est un exemple générique
        # Vous devez l'adapter selon votre structure de tables Oracle réelle
        # Pour l'instant, on va créer des données de démonstration
        
        logger.info("⚠️ Utilisation de données de démonstration - adapter la requête selon votre structure Oracle")
        
        # Données de démonstration basées sur l'image fournie
        demo_data = {
            "agencies": [
                {
                    "agence": "CORPORATE",
                    "objectif": 2020,
                    "volume_m": 2489,
                    "volume_m1": 1845,
                    "variation_volume": 644.11,
                    "variation_pct": -26,
                    "tro": 91,
                    "contribution": 68,
                    "commission": 5.483
                },
                {
                    "agence": "ZONE DAKAR",
                    "objectif": 1310,
                    "volume_m": 1647,
                    "volume_m1": 1274,
                    "variation_volume": 373.29,
                    "variation_pct": -23,
                    "tro": 97,
                    "contribution": 47,
                    "commission": 3.593
                },
                {
                    "agence": "POINT E",
                    "objectif": 300,
                    "volume_m": 382,
                    "volume_m1": 381,
                    "variation_volume": 0.07,
                    "variation_pct": 0,
                    "tro": 127,
                    "contribution": 30,
                    "commission": 0.862
                },
                {
                    "agence": "NIARRY TALLY",
                    "objectif": 120,
                    "volume_m": 187,
                    "volume_m1": 197,
                    "variation_volume": -10.27,
                    "variation_pct": 6,
                    "tro": 164,
                    "contribution": 15,
                    "commission": 0.439
                },
                {
                    "agence": "CASTOR",
                    "objectif": 250,
                    "volume_m": 322,
                    "volume_m1": 264,
                    "variation_volume": 58.03,
                    "variation_pct": -18,
                    "tro": 106,
                    "contribution": 21,
                    "commission": 0.616
                },
                {
                    "agence": "SCAT URBAM",
                    "objectif": 90,
                    "volume_m": 95,
                    "volume_m1": 50,
                    "variation_volume": 44.79,
                    "variation_pct": -47,
                    "tro": 55,
                    "contribution": 4,
                    "commission": 0.277
                },
                {
                    "agence": "PIKINE",
                    "objectif": 100,
                    "volume_m": 164,
                    "volume_m1": 96,
                    "variation_volume": 68.21,
                    "variation_pct": -41,
                    "tro": 96,
                    "contribution": 8,
                    "commission": 0.500
                },
                {
                    "agence": "PARCELLES",
                    "objectif": 200,
                    "volume_m": 216,
                    "volume_m1": 114,
                    "variation_volume": 101.91,
                    "variation_pct": -47,
                    "tro": 57,
                    "contribution": 9,
                    "commission": 0.472
                },
                {
                    "agence": "LAMINE GUEYE",
                    "objectif": 250,
                    "volume_m": 282,
                    "volume_m1": 172,
                    "variation_volume": 110.56,
                    "variation_pct": -39,
                    "tro": 69,
                    "contribution": 13,
                    "commission": 0.428
                },
                {
                    "agence": "ZONE PROVINCE",
                    "objectif": 710,
                    "volume_m": 841,
                    "volume_m1": 570,
                    "variation_volume": 270.82,
                    "variation_pct": -32,
                    "tro": 80,
                    "contribution": 21,
                    "commission": 1.890
                },
                {
                    "agence": "MBOUR",
                    "objectif": 100,
                    "volume_m": 134,
                    "volume_m1": 118,
                    "variation_volume": 16.53,
                    "variation_pct": -12,
                    "tro": 118,
                    "contribution": 9,
                    "commission": 0.295
                },
                {
                    "agence": "KAOLACK",
                    "objectif": 150,
                    "volume_m": 166,
                    "volume_m1": 112,
                    "variation_volume": 54.60,
                    "variation_pct": -33,
                    "tro": 74,
                    "contribution": 9,
                    "commission": 0.279
                },
                {
                    "agence": "THIES",
                    "objectif": 110,
                    "volume_m": 155,
                    "volume_m1": 139,
                    "variation_volume": 16.30,
                    "variation_pct": -11,
                    "tro": 126,
                    "contribution": 11,
                    "commission": 0.529
                },
                {
                    "agence": "TOUBA",
                    "objectif": 120,
                    "volume_m": 105,
                    "volume_m1": 80,
                    "variation_volume": 24.35,
                    "variation_pct": -23,
                    "tro": 67,
                    "contribution": 6,
                    "commission": 0.320
                },
                {
                    "agence": "SAINT-LOUIS",
                    "objectif": 230,
                    "volume_m": 281,
                    "volume_m1": 122,
                    "variation_volume": 159.04,
                    "variation_pct": -57,
                    "tro": 53,
                    "contribution": 10,
                    "commission": 0.466
                },
                {
                    "agence": "RETAIL",
                    "objectif": 875,
                    "volume_m": 999,
                    "volume_m1": 882,
                    "variation_volume": 116.39,
                    "variation_pct": -12,
                    "tro": 101,
                    "contribution": 32,
                    "commission": 2.868
                },
                {
                    "agence": "RUFISQUE",
                    "objectif": 150,
                    "volume_m": 215,
                    "volume_m1": 165,
                    "variation_volume": 50.41,
                    "variation_pct": -23,
                    "tro": 110,
                    "contribution": 13,
                    "commission": 0.666
                },
                {
                    "agence": "DIOURBEL",
                    "objectif": 80,
                    "volume_m": 72,
                    "volume_m1": 81,
                    "variation_volume": -9.11,
                    "variation_pct": 13,
                    "tro": 101,
                    "contribution": 6,
                    "commission": 0.197
                },
                {
                    "agence": "LOUGA",
                    "objectif": 80,
                    "volume_m": 26,
                    "volume_m1": 44,
                    "variation_volume": -18.26,
                    "variation_pct": 70,
                    "tro": 55,
                    "contribution": 3,
                    "commission": 0.102
                },
                {
                    "agence": "MARISTES",
                    "objectif": 250,
                    "volume_m": 347,
                    "volume_m1": 280,
                    "variation_volume": 66.79,
                    "variation_pct": -19,
                    "tro": 112,
                    "contribution": 22,
                    "commission": 0.901
                },
                {
                    "agence": "OUROSSOGUI",
                    "objectif": 75,
                    "volume_m": 59,
                    "volume_m1": 41,
                    "variation_volume": 18.05,
                    "variation_pct": -31,
                    "tro": 55,
                    "contribution": 3,
                    "commission": 0.216
                },
                {
                    "agence": "TAMBA",
                    "objectif": 90,
                    "volume_m": 56,
                    "volume_m1": 40,
                    "variation_volume": 15.08,
                    "variation_pct": -27,
                    "tro": 45,
                    "contribution": 3,
                    "commission": 0.226
                },
                {
                    "agence": "LINGUERE'LA",
                    "objectif": 150,
                    "volume_m": 225,
                    "volume_m1": 231,
                    "variation_volume": -6.55,
                    "variation_pct": 3,
                    "tro": 154,
                    "contribution": 18,
                    "commission": 0.560
                },
                {
                    "agence": "TOTAL",
                    "objectif": 2895,
                    "volume_m": 3487,
                    "volume_m1": 2727,
                    "variation_volume": 760.50,
                    "variation_pct": -22,
                    "tro": 94,
                    "contribution": 100,
                    "commission": 8.351
                }
            ],
            "services": [
                {
                    "service": "Orange Money",
                    "volume": 205,
                    "commission": 1.06
                },
                {
                    "service": "MoneyGram",
                    "volume": 52,
                    "commission": 0.43
                },
                {
                    "service": "Ria Money Transfer",
                    "volume": 77,
                    "commission": 0.50
                },
                {
                    "service": "Penguin",
                    "volume": 2279,
                    "commission": 5.45
                },
                {
                    "service": "Western Union",
                    "volume": 113,
                    "commission": 0.90
                },
                {
                    "service": "Wizall Money",
                    "volume": 0.1,
                    "commission": 0.004
                },
                {
                    "service": "Mixx",
                    "volume": 0,
                    "commission": 0
                }
            ]
        }
        
        cursor.close()
        conn.close()
        
        logger.info(f"✅ Données de transferts récupérées: {len(demo_data['agencies'])} agences, {len(demo_data['services'])} services")
        return demo_data
        
    except Exception as e:
        logger.error(f"❌ Erreur lors de la récupération des données de transferts: {str(e)}", exc_info=True)
        if 'cursor' in locals():
            cursor.close()
        if 'conn' in locals():
            conn.close()
        raise

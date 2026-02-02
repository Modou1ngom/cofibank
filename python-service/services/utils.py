"""
Fonctions utilitaires partagées entre les services
"""
from datetime import datetime
import calendar
from typing import Optional, Dict, Tuple
import logging

logger = logging.getLogger(__name__)


def calculate_period_dates(period: str, month: Optional[int] = None, year: Optional[int] = None, date: Optional[str] = None) -> Dict[str, str]:
    """Calcule les dates de début et fin pour une période donnée"""
    import logging
    logger = logging.getLogger(__name__)
    logger.info(f"🔍 calculate_period_dates appelé avec period='{period}' (type: {type(period)}), month={month}, year={year}, date={date}")
    # Convertir month et year en entiers si nécessaire
    if month is not None:
        try:
            month = int(month) if not isinstance(month, int) else month
        except (ValueError, TypeError):
            month = None
    if year is not None:
        try:
            year = int(year) if not isinstance(year, int) else year
        except (ValueError, TypeError):
            year = None
    
    # Normaliser le paramètre period (minuscules, sans espaces)
    period_normalized = str(period).strip().lower() if period else "month"
    logger.info(f"📅 Période normalisée: '{period_normalized}' (original: '{period}')")
    
    if period_normalized == "month":
        if month and year:
            current_date = datetime(year, month, 1)
            # Mois précédent
            if month == 1:
                prev_date = datetime(year - 1, 12, 1)
            else:
                prev_date = datetime(year, month - 1, 1)
        else:
            current_date = datetime.now().replace(day=1)
            if current_date.month == 1:
                prev_date = datetime(current_date.year - 1, 12, 1)
            else:
                prev_date = datetime(current_date.year, current_date.month - 1, 1)
        
        # Dates de début et fin pour le mois actuel
        current_start = current_date
        current_end = current_date.replace(day=calendar.monthrange(current_date.year, current_date.month)[1])
        
        # Dates de début et fin pour le mois précédent
        prev_start = prev_date
        prev_end = prev_date.replace(day=calendar.monthrange(prev_date.year, prev_date.month)[1])
    elif period_normalized == "year":
        # Pour l'année, comparer l'année complète avec l'année précédente
        if year:
            current_start = datetime(year, 1, 1)
            current_end = datetime(year, 12, 31)
            prev_start = datetime(year - 1, 1, 1)
            prev_end = datetime(year - 1, 12, 31)
        else:
            now = datetime.now()
            current_start = datetime(now.year, 1, 1)
            current_end = datetime(now.year, 12, 31)
            prev_start = datetime(now.year - 1, 1, 1)
            prev_end = datetime(now.year - 1, 12, 31)
    elif period_normalized == "week":
        # Pour la semaine, calculer les dates de début et fin de la semaine sélectionnée
        # Si une date est fournie (format YYYY-MM-DD), l'utiliser, sinon utiliser aujourd'hui
        from datetime import timedelta
        import logging
        logger = logging.getLogger(__name__)
        
        logger.info(f"📅 Calcul des dates pour période WEEK - date reçue: {date}, month: {month}, year: {year}")
        
        if date:
            # Parser la date au format YYYY-MM-DD
            try:
                reference_date = datetime.strptime(date, "%Y-%m-%d")
                logger.info(f"📅 Date parsée avec succès: {reference_date}")
            except (ValueError, TypeError) as e:
                # Si le parsing échoue, utiliser aujourd'hui
                logger.warning(f"⚠️ Erreur lors du parsing de la date '{date}': {e}. Utilisation de la date actuelle.")
                reference_date = datetime.now()
        elif month and year:
            # Si month et year sont fournis, utiliser le 1er du mois comme date de référence
            # (pour compatibilité avec l'ancien code)
            reference_date = datetime(year, month, 1)
            logger.info(f"📅 Utilisation de month/year: {reference_date}")
        else:
            reference_date = datetime.now()
            logger.info(f"📅 Utilisation de la date actuelle: {reference_date}")
        
        # Trouver le lundi de la semaine contenant la date de référence
        # En Python, lundi = 0, dimanche = 6
        days_since_monday = reference_date.weekday()
        monday = reference_date - timedelta(days=days_since_monday)
        sunday = monday + timedelta(days=6)
        
        # Semaine précédente (S-1)
        prev_monday = monday - timedelta(days=7)
        prev_sunday = prev_monday + timedelta(days=6)
        
        logger.info(f"📅 Semaine calculée - Lundi: {monday.strftime('%d/%m/%Y')}, Dimanche: {sunday.strftime('%d/%m/%Y')}")
        logger.info(f"📅 Semaine précédente - Lundi: {prev_monday.strftime('%d/%m/%Y')}, Dimanche: {prev_sunday.strftime('%d/%m/%Y')}")
        
        current_start = monday
        current_end = sunday
        prev_start = prev_monday
        prev_end = prev_sunday
    else:
        # Par défaut, utiliser le mois actuel
        current_date = datetime.now().replace(day=1)
        if current_date.month == 1:
            prev_date = datetime(current_date.year - 1, 12, 1)
        else:
            prev_date = datetime(current_date.year, current_date.month - 1, 1)
        
        current_start = current_date
        current_end = current_date.replace(day=calendar.monthrange(current_date.year, current_date.month)[1])
        prev_start = prev_date
        prev_end = prev_date.replace(day=calendar.monthrange(prev_date.year, prev_date.month)[1])
    
    # Format des dates pour Oracle (DD/MM/YYYY)
    return {
        'date_m_debut_str': current_start.strftime("%d/%m/%Y"),
        'date_m_fin_str': current_end.strftime("%d/%m/%Y"),
        'date_m1_debut_str': prev_start.strftime("%d/%m/%Y"),
        'date_m1_fin_str': prev_end.strftime("%d/%m/%Y")
    }


# Mapping des agences vers les territoires selon le nouveau zonage
AGENCY_TERRITORY_MAPPING = {
    # DAKAR CENTRE VILLE
    'CASTOR': 'DAKAR CENTRE VILLE',
    'CASTORS': 'DAKAR CENTRE VILLE',  # Variante
    'AGENCE CASTOR': 'DAKAR CENTRE VILLE',
    'AGENCE CASTORS': 'DAKAR CENTRE VILLE',  # Variante
    'MARISTES': 'DAKAR CENTRE VILLE',
    'AGENCE MARISTES': 'DAKAR CENTRE VILLE',
    'C-E MARISTES': 'DAKAR CENTRE VILLE',  # Variante
    'CE MARISTES': 'DAKAR CENTRE VILLE',  # Variante
    'COFINA EXPRESS MARISTES': 'DAKAR CENTRE VILLE',
    'NGUELAW': 'DAKAR CENTRE VILLE',
    'C-E NGUELAW': 'DAKAR CENTRE VILLE',
    'CE NGUELAW': 'DAKAR CENTRE VILLE',  # Variante
    'VITRINE LAMINE': 'DAKAR CENTRE VILLE',
    'AGENCE VITRINE LAMINE': 'DAKAR CENTRE VILLE',
    'GUEYE': 'DAKAR CENTRE VILLE',
    'LAMINE GUEYE': 'DAKAR CENTRE VILLE',  # Variante
    'AGENCE LAMINE GUEYE': 'DAKAR CENTRE VILLE',  # Variante
    'PRINCIPALE POINT E': 'DAKAR CENTRE VILLE',
    'AGENCE PRINCIPALE POINT E': 'DAKAR CENTRE VILLE',
    'POINT E': 'DAKAR CENTRE VILLE',
    'AGENCE POINT E': 'DAKAR CENTRE VILLE',
    'KEUR MASSAR': 'DAKAR CENTRE VILLE',
    'AGENCE KEUR MASSAR': 'DAKAR CENTRE VILLE',
    'GRAND COMPTE': 'DAKAR CENTRE VILLE',
    'AGENCE GRAND COMPTE': 'DAKAR CENTRE VILLE',
    'SIEGE': 'DAKAR CENTRE VILLE',  # AGENCE SIEGE
    'AGENCE SIEGE': 'DAKAR CENTRE VILLE',
    
    # DAKAR BANLIEUE
    'LINGUERE': 'DAKAR BANLIEUE',
    'LINGUERLA': 'DAKAR BANLIEUE',  # Variante
    'LINGUERE\'LA': 'DAKAR BANLIEUE',
    'LINGUERE LA': 'DAKAR BANLIEUE',  # Variante sans apostrophe
    'AGENCE LINGUERE\'LA': 'DAKAR BANLIEUE',
    'AGENCE LINGUERE LA': 'DAKAR BANLIEUE',  # Variante sans apostrophe
    'AGENCE LINGUERE\'LA GUEDIAWAYE': 'DAKAR BANLIEUE',
    'AGENCE LINGUERE LA GUEDIAWAYE': 'DAKAR BANLIEUE',  # Variante sans apostrophe
    'AGENCE LINGUERLA': 'DAKAR BANLIEUE',  # Variante
    'GUEDIAWAYE': 'DAKAR BANLIEUE',
    'AGENCE GUEDIAWAYE': 'DAKAR BANLIEUE',
    'RUFISQUE': 'DAKAR BANLIEUE',
    'AGENCE RUFISQUE': 'DAKAR BANLIEUE',
    'COFINA EXPRESS RUFISQUE': 'DAKAR BANLIEUE',
    'CE RUFISQUE': 'DAKAR BANLIEUE',  # Variante
    'PARCELLES': 'DAKAR BANLIEUE',
    'AGENCE PARCELLES': 'DAKAR BANLIEUE',
    'PIKINE': 'DAKAR BANLIEUE',
    'AGENCE PIKINE': 'DAKAR BANLIEUE',
    
    # PROVINCE CENTRE SUD
    'MBOUR': 'PROVINCE CENTRE SUD',
    'AGENCE MBOUR': 'PROVINCE CENTRE SUD',
    'TAMBACOUNDA': 'PROVINCE CENTRE SUD',
    'TAMBA': 'PROVINCE CENTRE SUD',  # Variante courte
    'AGENCE TAMBACOUNDA': 'PROVINCE CENTRE SUD',
    'AGENCE TAMBA': 'PROVINCE CENTRE SUD',  # Variante courte
    'COFINA EXPRESS TAMBA': 'PROVINCE CENTRE SUD',
    'CE TAMBA': 'PROVINCE CENTRE SUD',  # Variante
    'ZIGUINCHOR': 'PROVINCE CENTRE SUD',
    'AGENCE ZIGUINCHOR': 'PROVINCE CENTRE SUD',
    'COFINA C. E. ZIGUINCHOR': 'PROVINCE CENTRE SUD',
    'COFINA C E ZIGUINCHOR': 'PROVINCE CENTRE SUD',  # Variante sans points
    'COFINA CE ZIGUINCHOR': 'PROVINCE CENTRE SUD',  # Variante
    'C-E ZIGUINCHOR': 'PROVINCE CENTRE SUD',  # Variante
    'CE ZIGUINCHOR': 'PROVINCE CENTRE SUD',  # Variante
    'THIES': 'PROVINCE CENTRE SUD',
    'AGENCE THIES': 'PROVINCE CENTRE SUD',
    'KAOLACK': 'PROVINCE CENTRE SUD',
    'AGENCE KAOLACK': 'PROVINCE CENTRE SUD',
    
    # PROVINCE NORD
    'TOUBA KHAYRA': 'PROVINCE NORD',
    'AGENCE TOUBA KHAYRA': 'PROVINCE NORD',
    'TOUBA': 'PROVINCE NORD',
    'AGENCE TOUBA': 'PROVINCE NORD',
    'SAINT-LOUIS': 'PROVINCE NORD',
    'SAINT LOUIS': 'PROVINCE NORD',  # Variante sans tiret
    'AGENCE SAINT-LOUIS': 'PROVINCE NORD',
    'AGENCE SAINT LOUIS': 'PROVINCE NORD',  # Variante sans tiret
    'LOUGA': 'PROVINCE NORD',
    'AGENCE LOUGA': 'PROVINCE NORD',
    'COFINA EXPRESS LOUGA': 'PROVINCE NORD',
    'CE LOUGA': 'PROVINCE NORD',  # Variante
    'DIOURBEL': 'PROVINCE NORD',
    'AGENCE DIOURBEL': 'PROVINCE NORD',
    'COFINA EXPRESS DIOURBEL': 'PROVINCE NORD',
    'CE DIOURBEL': 'PROVINCE NORD',  # Variante
    'OUROSSOGUI': 'PROVINCE NORD',
    'AGENCE OUROSSOGUI': 'PROVINCE NORD',
    'COFINA EXPRESS OUROSSOGUI': 'PROVINCE NORD',
    'CE OUROSSOGUI': 'PROVINCE NORD',  # Variante
}

# Mapping des codes agence (BRANCH_CODE) vers les territoires
# Ce mapping a la priorité sur le mapping par nom d'agence
# Format: 'CODE_AGENCE': 'NOM_TERRITOIRE'
# Exemple: '001': 'DAKAR CENTRE VILLE', '002': 'DAKAR BANLIEUE', etc.
# 
# Ce mapping est généré automatiquement depuis la base de données
# lors du premier appel. Vous pouvez aussi l'initialiser manuellement
# en ajoutant les codes agence ci-dessous.

# Initialiser le mapping vide - sera rempli dynamiquement
_BRANCH_CODE_MAPPING_CACHE = None

def get_branch_code_territory_mapping(force_regenerate: bool = False) -> Dict[str, str]:
    """
    Retourne le mapping des codes agence vers les territoires.
    Génère automatiquement le mapping depuis la base de données si nécessaire.
    
    Args:
        force_regenerate: Si True, force la régénération du mapping même s'il existe déjà
    
    Returns:
        Dictionnaire avec le mapping code agence -> territoire
    """
    global _BRANCH_CODE_MAPPING_CACHE
    
    # Si le cache est vide ou si on force la régénération
    if _BRANCH_CODE_MAPPING_CACHE is None or force_regenerate:
        logger.info("🔄 Génération du mapping des codes agence depuis la base de données...")
        _BRANCH_CODE_MAPPING_CACHE = generate_branch_code_mapping_from_db()
        
        # Fusionner avec le mapping manuel (le mapping manuel a la priorité)
        if BRANCH_CODE_TERRITORY_MAPPING_MANUAL:
            _BRANCH_CODE_MAPPING_CACHE.update(BRANCH_CODE_TERRITORY_MAPPING_MANUAL)
            logger.info(f"✅ Mapping fusionné: {len(_BRANCH_CODE_MAPPING_CACHE)} codes agence au total")
        
        # Si le mapping généré est vide, utiliser un mapping par défaut vide
        if not _BRANCH_CODE_MAPPING_CACHE:
            _BRANCH_CODE_MAPPING_CACHE = {}
            logger.warning("⚠️ Aucun mapping de code agence généré. Utilisation d'un mapping vide.")
    
    return _BRANCH_CODE_MAPPING_CACHE


def reset_branch_code_mapping_cache():
    """
    Réinitialise le cache du mapping des codes agence.
    Utile pour forcer la régénération du mapping.
    """
    global _BRANCH_CODE_MAPPING_CACHE
    _BRANCH_CODE_MAPPING_CACHE = None
    logger.info("🔄 Cache du mapping des codes agence réinitialisé")

# Mapping manuel (optionnel) - sera fusionné avec le mapping généré
BRANCH_CODE_TERRITORY_MAPPING_MANUAL = {
    # Vous pouvez ajouter manuellement des codes agence ici si nécessaire
    # Exemple: '001': 'DAKAR CENTRE VILLE',
}

# Mapping des points de service vers les agences
SERVICE_POINT_MAPPING = {
    'SCAT URBAM': 'AGENCE CASTOR',
    'SCAT-URBAM': 'AGENCE CASTOR',  # Variante avec tiret
    'SCATURBAM': 'AGENCE CASTOR',  # Variante sans espace
    'C-E SCAT URBAM': 'AGENCE CASTOR',  # Variante avec préfixe C-E
    'C-E SCAT-URBAM': 'AGENCE CASTOR',  # Variante avec préfixe C-E et tiret
    'CE SCAT URBAM': 'AGENCE CASTOR',  # Variante avec préfixe CE
    'CE SCAT-URBAM': 'AGENCE CASTOR',  # Variante avec préfixe CE et tiret
    'SCAT': 'AGENCE CASTOR',  # Variante courte (si seul)
    'URBAM': 'AGENCE CASTOR',  # Variante courte (si seul)
    'NIARRY TALLY': 'AGENCE PRINCIPALE POINT E',
    'NIARRY TALLI': 'AGENCE PRINCIPALE POINT E',  # Variante
    'NIARRY-TALLY': 'AGENCE PRINCIPALE POINT E',  # Variante avec tiret
    'NIARRY-TALLI': 'AGENCE PRINCIPALE POINT E',  # Variante avec tiret
    'NIARRYTALLY': 'AGENCE PRINCIPALE POINT E',  # Variante sans espace
    'NIARRYTALLI': 'AGENCE PRINCIPALE POINT E',  # Variante sans espace
    'C-E NIARRY TALLI': 'AGENCE PRINCIPALE POINT E',  # Variante avec préfixe
    'C-E NIARRY TALLY': 'AGENCE PRINCIPALE POINT E',  # Variante avec préfixe
    'C-E NIARRY-TALLI': 'AGENCE PRINCIPALE POINT E',  # Variante avec préfixe et tiret
    'C-E NIARRY-TALLY': 'AGENCE PRINCIPALE POINT E',  # Variante avec préfixe et tiret
    'CE NIARRY TALLI': 'AGENCE PRINCIPALE POINT E',  # Variante sans tiret dans préfixe
    'CE NIARRY TALLY': 'AGENCE PRINCIPALE POINT E',  # Variante sans tiret dans préfixe
    'NIARRY': 'AGENCE PRINCIPALE POINT E',  # Variante courte (si seul)
    'TALLY': 'AGENCE PRINCIPALE POINT E',  # Variante courte (si seul)
    'TALLI': 'AGENCE PRINCIPALE POINT E',  # Variante courte (si seul)
}


def get_territory_from_branch_code(branch_code: str) -> Optional[str]:
    """
    Retourne le territoire d'une agence en fonction de son code agence (BRANCH_CODE).
    Utilise le mapping généré automatiquement depuis la base de données.
    
    Args:
        branch_code: Code agence (BRANCH_CODE)
    
    Returns:
        Nom du territoire ou None si non trouvé
    """
    if not branch_code or branch_code is None:
        return None
    
    # Normaliser le code agence (supprimer les espaces, convertir en string)
    branch_code_str = str(branch_code).strip().upper() if branch_code else ''
    
    if not branch_code_str:
        return None
    
    # Récupérer le mapping (généré automatiquement si nécessaire)
    mapping = get_branch_code_territory_mapping()
    
    # Vérifier d'abord le mapping manuel (priorité)
    if branch_code_str in BRANCH_CODE_TERRITORY_MAPPING_MANUAL:
        return BRANCH_CODE_TERRITORY_MAPPING_MANUAL[branch_code_str]
    
    # Chercher dans le mapping généré automatiquement
    if branch_code_str in mapping:
        return mapping[branch_code_str]
    
    # Si aucune correspondance, retourner None
    return None


def get_territory_from_agency(agency_name: str) -> Optional[str]:
    """
    Retourne le territoire d'une agence selon le nouveau zonage.
    Ne retourne pas de territoire pour les points de service.
    
    Args:
        agency_name: Nom de l'agence (peut contenir des variations)
    
    Returns:
        Nom du territoire ou None si non trouvé ou si c'est un point de service
    """
    if not agency_name or agency_name is None:
        return None
    
    # Convertir en chaîne et normaliser le nom de l'agence (majuscules, supprimer espaces multiples)
    agency_name_str = str(agency_name) if agency_name else ''
    normalized_name = ' '.join(agency_name_str.upper().split())
    
    # Vérifier d'abord si c'est un point de service
    # Si c'est un point de service, ne pas retourner de territoire
    for service_point_name in SERVICE_POINT_MAPPING.keys():
        service_point_str = str(service_point_name) if service_point_name else ''
        service_point_normalized = ' '.join(service_point_str.upper().split())
        # Correspondance exacte
        if service_point_normalized == normalized_name:
            return None  # C'est un point de service, pas une agence
        # Correspondance partielle
        if service_point_normalized in normalized_name or normalized_name in service_point_normalized:
            return None  # C'est un point de service, pas une agence
    
    # Chercher une correspondance exacte
    if normalized_name in AGENCY_TERRITORY_MAPPING:
        return AGENCY_TERRITORY_MAPPING[normalized_name]
    
    # Chercher une correspondance partielle (contient) - vérifier d'abord les clés les plus longues
    # Trier par longueur décroissante pour prioriser les correspondances les plus spécifiques
    sorted_keys = sorted(AGENCY_TERRITORY_MAPPING.keys(), key=len, reverse=True)
    for key in sorted_keys:
        # Vérifier si le nom de l'agence contient la clé ou vice versa
        if key in normalized_name or normalized_name in key:
            return AGENCY_TERRITORY_MAPPING[key]
    
    # Chercher par mots-clés dans le nom (plus flexible)
    # Extraire les mots significatifs du nom de l'agence
    agency_words = [word for word in normalized_name.split() if len(word) > 2]
    for word in agency_words:
        for key, territory in AGENCY_TERRITORY_MAPPING.items():
            key_words = [w for w in key.split() if len(w) > 2]
            if word in key_words or any(kw in normalized_name for kw in key_words):
                return territory
    
    # Si aucune correspondance, retourner None
    return None


def get_territory_key(territory_name: str) -> str:
    """
    Convertit le nom du territoire en clé utilisable (zone1, zone2, etc.)
    Pour le nouveau zonage, on utilise les noms complets des territoires.
    
    Args:
        territory_name: Nom du territoire
    
    Returns:
        Clé du territoire avec le préfixe "territoire_"
    """
    territory_key_mapping = {
        'TERRITOIRE DAKAR VILLE': 'territoire_dakar_ville',
        'DAKAR CENTRE VILLE': 'territoire_dakar_ville',
        'DAKAR VILLE': 'territoire_dakar_ville',
        'TERRITOIRE DAKAR BANLIEUE': 'territoire_dakar_banlieue',
        'DAKAR BANLIEUE': 'territoire_dakar_banlieue',
        'TERRITOIRE PROVINCE CENTRE-SUD': 'territoire_province_centre_sud',
        'TERRITOIRE PROVINCE CENTRE SUD': 'territoire_province_centre_sud',
        'PROVINCE CENTRE-SUD': 'territoire_province_centre_sud',
        'PROVINCE CENTRE SUD': 'territoire_province_centre_sud',
        'TERRITOIRE PROVINCE NORD': 'territoire_province_nord',
        'PROVINCE NORD': 'territoire_province_nord',
    }
    result = territory_key_mapping.get(territory_name, territory_name.lower().replace(' ', '_').replace('-', '_'))
    # S'assurer que le résultat a le préfixe "territoire_" si ce n'est pas déjà le cas
    if not result.startswith('territoire_'):
        result = 'territoire_' + result
    return result


def generate_branch_code_mapping_from_db() -> Dict[str, str]:
    """
    Génère automatiquement le mapping BRANCH_CODE_TERRITORY_MAPPING
    en récupérant les codes agence depuis Oracle et en utilisant
    le mapping par nom d'agence existant.
    
    Returns:
        Dictionnaire avec le mapping code agence -> territoire
    """
    try:
        # Essayer d'importer get_oracle_connection
        try:
            from database.oracle import get_oracle_connection
        except ImportError:
            # Si l'import échoue, essayer une importation relative
            try:
                import sys
                import os
                sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
                from database.oracle import get_oracle_connection
            except ImportError as e:
                logger.warning(f"⚠️ Impossible d'importer get_oracle_connection: {e}")
                return {}
    except Exception as e:
        logger.warning(f"⚠️ Erreur lors de l'import: {e}")
        return {}
    
    try:
        connection = get_oracle_connection()
        if not connection:
            logger.warning("⚠️ Impossible de se connecter à Oracle pour récupérer les codes agence")
            return {}
        
        cursor = connection.cursor()
        
        # Récupérer tous les codes agence et noms d'agences
        query = """
            SELECT DISTINCT
                b.BRANCH_CODE,
                b.BRANCH_NAME
            FROM CFSFCUBS145.STTM_BRANCH b
            WHERE b.BRANCH_CODE IS NOT NULL
              AND b.BRANCH_NAME IS NOT NULL
            ORDER BY b.BRANCH_CODE
        """
        
        cursor.execute(query)
        results = cursor.fetchall()
        cursor.close()
        
        mapping = {}
        mapped_count = 0
        unmapped_count = 0
        
        for branch_code, branch_name in results:
            if not branch_code or not branch_name:
                continue
            
            # Normaliser le code agence
            branch_code_str = str(branch_code).strip().upper()
            
            # Utiliser le mapping par nom pour déterminer le territoire
            territory = get_territory_from_agency(branch_name)
            
            if territory:
                mapping[branch_code_str] = territory
                mapped_count += 1
                logger.debug(f"✅ Code agence {branch_code_str} ({branch_name}) -> {territory}")
            else:
                unmapped_count += 1
                logger.debug(f"⚠️ Code agence {branch_code_str} ({branch_name}) non mappé")
        
        logger.info(f"📊 Mapping généré: {mapped_count} codes agence mappés, {unmapped_count} non mappés")
        
        return mapping
        
    except Exception as e:
        logger.error(f"❌ Erreur lors de la génération du mapping: {e}", exc_info=True)
        return {}


def get_all_territories() -> Dict[str, Dict]:
    """
    Retourne la structure complète des territoires avec leurs agences.
    
    Returns:
        Dictionnaire avec la structure des territoires (clés avec préfixe "territoire_")
    """
    return {
        'territoire_dakar_ville': {
            'name': 'TERRITOIRE DAKAR VILLE',
            'agencies': [
                'AGENCE LAMINE GUEYE',
                'AGENCE BOURGUIBA',
                'AGENCE POINT E',
                'COFINA EXPRESS MARISTES'
            ],
            'service_points': {
                'AGENCE CASTOR': ['SCAT URBAM'],
                'AGENCE POINT E': ['NIARRY TALLY']
            }
        },
        'territoire_dakar_banlieue': {
            'name': 'TERRITOIRE DAKAR BANLIEUE',
            'agencies': [
                'AGENCE PIKINE',
                'AGENCE PARCELLES',
                'AGENCE LINGUERE\'LA GUEDIAWAYE',
                'COFINA EXPRESS RUFISQUE'
            ],
            'service_points': {}
        },
        'territoire_province_centre_sud': {
            'name': 'TERRITOIRE PROVINCE CENTRE SUD',
            'agencies': [
                'COFINA EXPRESS TAMBA',
                'KAOLACK',
                'MBOUR',
                'THIES',
                'COFINA C. E. ZIGUINCHOR'
            ],
            'service_points': {}
        },
        'territoire_province_nord': {
            'name': 'TERRITOIRE PROVINCE NORD',
            'agencies': [
                'TOUBA',
                'SAINT LOUIS',
                'COFINA EXPRESS DIOURBEL',
                'COFINA EXPRESS LOUGA',
                'COFINA EXPRESS OUROSSOGUI'
            ],
            'service_points': {}
        }
    }


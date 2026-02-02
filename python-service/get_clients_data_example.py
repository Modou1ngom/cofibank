"""
Exemple de requête SQL pour récupérer les données clients depuis Oracle
À adapter selon votre schéma Oracle réel
"""

# Exemple de structure de données attendue par le dashboard
EXAMPLE_QUERY = """
-- Exemple 1: Si vous avez une table avec les données clients par agence
SELECT 
    AGENCE_NAME as name,
    CATEGORIE,  -- 'CORPORATE' ou 'RETAIL'
    ZONE,       -- 'zone1' (DAKAR) ou 'zone2' (PROVINCE)
    COUNT(CASE WHEN TRUNC(DATE_CREATION, 'MM') = TRUNC(SYSDATE, 'MM') THEN 1 END) as nouveauxClientsM,
    COUNT(CASE WHEN TRUNC(DATE_CREATION, 'MM') = ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -1) THEN 1 END) as nouveauxClientsM1,
    NVL(SUM(CASE WHEN TRUNC(DATE_CREATION, 'MM') = TRUNC(SYSDATE, 'MM') THEN FRAIS_OUVERTURE ELSE 0 END), 0) as fraisM,
    NVL(SUM(CASE WHEN TRUNC(DATE_CREATION, 'MM') = ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -1) THEN FRAIS_OUVERTURE ELSE 0 END), 0) as fraisM1
FROM CFSFCUBS145.CLIENTS  -- Remplacez par votre table réelle
WHERE DATE_CREATION >= ADD_MONTHS(TRUNC(SYSDATE, 'MM'), -1)
GROUP BY AGENCE_NAME, CATEGORIE, ZONE
ORDER BY CATEGORIE, ZONE, AGENCE_NAME;
"""

# Exemple 2: Si vous avez une vue ou une table de synthèse
EXAMPLE_QUERY_2 = """
SELECT 
    AGENCE,
    TYPE_CLIENT as CATEGORIE,  -- 'CORPORATE' ou 'RETAIL'
    REGION as ZONE,            -- Mapper vers zone1/zone2
    NOUVEAUX_CLIENTS_MOIS as nouveauxClientsM,
    NOUVEAUX_CLIENTS_MOIS_PRECEDENT as nouveauxClientsM1,
    FRAIS_MOIS as fraisM,
    FRAIS_MOIS_PRECEDENT as fraisM1
FROM VOTRE_VUE_SYNTHESE
WHERE MOIS = :month AND ANNEE = :year;
"""

# Structure de données attendue par le composant Vue
EXPECTED_STRUCTURE = {
    "globalResult": {
        "mois": 125000000,      # Total frais du mois actuel
        "mois1": 118000000,     # Total frais du mois précédent
        "evolution": 5.93,      # Pourcentage d'évolution
        "cumulAnnee": 1450000000  # Cumul de l'année
    },
    "corporateZones": {
        "zone1": {
            "name": "ZONE DAKAR",
            "agencies": [
                {
                    "name": "POINT E",
                    "nouveauxClientsM1": 116,
                    "nouveauxClientsM": 94,
                    "variationClients": -22,  # Calculé: nouveauxClientsM - nouveauxClientsM1
                    "tauxCroissanceClients": -18.97,  # Calculé: (variationClients / nouveauxClientsM1) * 100
                    "fraisM1": 1764,
                    "fraisM": 1750,
                    "variationFrais": -14,  # Calculé: fraisM - fraisM1
                    "tauxCroissanceFrais": -0.70  # Calculé: (variationFrais / fraisM1) * 100
                }
                # ... autres agences
            ]
        },
        "zone2": {
            "name": "ZONE PROVINCE",
            "agencies": [
                # ... agences de la zone 2
            ]
        }
    },
    "retailZones": {
        "zone1": {
            "name": "ZONE DAKAR",
            "agencies": [
                # ... agences RETAIL zone 1
            ]
        },
        "zone2": {
            "name": "ZONE PROVINCE",
            "agencies": [
                # ... agences RETAIL zone 2
            ]
        }
    }
}

# Instructions pour adapter la requête:
"""
1. Identifiez vos tables Oracle qui contiennent:
   - Les données clients (nouveaux clients par agence)
   - Les frais d'ouverture de compte
   - La catégorie (CORPORATE/RETAIL)
   - La zone (DAKAR/PROVINCE ou zone1/zone2)
   - Les dates de création

2. Adaptez la requête SQL dans python-service/main.py dans la fonction get_clients_data()

3. Mappez les colonnes Oracle vers le format attendu:
   - AGENCE_NAME -> name
   - CATEGORIE -> 'CORPORATE' ou 'RETAIL'
   - ZONE -> 'zone1' ou 'zone2'
   - DATE_CREATION -> pour filtrer par période
   - FRAIS_OUVERTURE -> fraisM et fraisM1

4. Calculez les variations et taux de croissance dans Python après avoir récupéré les données

5. Structurez les données selon le format EXPECTED_STRUCTURE ci-dessus
"""





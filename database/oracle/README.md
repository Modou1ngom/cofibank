# Table OBJECTIFS dans Oracle

## Création de la table

La table `OBJECTIFS` doit être créée dans Oracle pour stocker les objectifs personnalisés.

### Méthode 1 : Via l'API (recommandé)

```bash
curl -X POST http://localhost:8000/api/objectives/create-table
```

### Méthode 2 : Via SQL directement

Exécutez le script SQL dans votre client Oracle (SQL Developer, SQL*Plus, etc.) :

```bash
# Le fichier se trouve dans : database/oracle/create_objectives_table.sql
```

Ou copiez-collez le contenu du fichier `create_objectives_table.sql` dans votre client Oracle.

### Méthode 3 : Via le service Python

Si vous avez accès au service Python, vous pouvez exécuter la requête via l'API :

```bash
curl -X POST http://localhost:8001/api/oracle/query \
  -H "Content-Type: application/json" \
  -d '{"sql": "CREATE TABLE OBJECTIFS ..."}'
```

## Structure de la table

La table `OBJECTIFS` contient les colonnes suivantes :

- `ID_OBJECTIF` : Identifiant unique (auto-incrémenté)
- `TYPE_OBJECTIF` : Type ('CLIENT' ou 'PRODUCTION')
- `CATEGORIE` : Catégorie ('TERRITOIRE', 'POINT SERVICES', 'GRAND COMPTE')
- `TERRITOIRE` : Territoire (optionnel)
- `CODE_AGENCE` : Code de l'agence
- `NOM_AGENCE` : Nom de l'agence (optionnel)
- `VALEUR` : Valeur de l'objectif
- `PERIODE` : Période ('month', 'quarter', 'year')
- `ANNEE` : Année
- `MOIS` : Mois (si période = 'month')
- `TRIMESTRE` : Trimestre (si période = 'quarter')
- `DESCRIPTION` : Description (optionnel)
- `DATE_CREATION` : Date de création
- `DATE_MODIFICATION` : Date de modification

## Vérification

Pour vérifier que la table a été créée :

```sql
SELECT * FROM OBJECTIFS;
```

## Migration des données existantes

Si vous avez des objectifs dans la base de données Laravel (SQLite/MySQL), vous pouvez les migrer vers Oracle en utilisant la route de test :

```bash
# Récupérer les objectifs depuis Laravel
php artisan tinker
>>> $objectives = App\Models\Objective::all();
>>> // Puis créer chaque objectif via l'API POST /api/objectives
```

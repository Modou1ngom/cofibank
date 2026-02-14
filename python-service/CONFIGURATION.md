# Configuration du Service Python COFIdash

## Variables d'environnement

Le service Python utilise des variables d'environnement pour la configuration Oracle. Aucune information sensible n'est stockée dans le code.

### Variables requises

```bash
# Adresse IP ou hostname du serveur Oracle
export ORACLE_HOST=votre_serveur_oracle

# Port du serveur Oracle (généralement 1521 ou 1522)
export ORACLE_PORT=1521

# Nom du service Oracle
export ORACLE_SERVICE_NAME=votre_service_name

# Nom d'utilisateur Oracle
export ORACLE_USERNAME=votre_username

# Mot de passe Oracle
export ORACLE_PASSWORD=votre_password
```

### Configuration via fichier .env

Créez un fichier `.env` dans le répertoire `python-service/` :

```bash
cd python-service
cat > .env << EOF
ORACLE_HOST=votre_serveur_oracle
ORACLE_PORT=1521
ORACLE_SERVICE_NAME=votre_service_name
ORACLE_USERNAME=votre_username
ORACLE_PASSWORD=votre_password
EOF
```

Puis chargez les variables avant de démarrer le service :

```bash
export $(cat .env | xargs)
uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```

### Valeurs par défaut

Si les variables d'environnement ne sont pas définies, le service utilisera :
- `ORACLE_HOST`: `localhost`
- `ORACLE_PORT`: `1521`
- `ORACLE_SERVICE_NAME`: `ORCL`
- `ORACLE_USERNAME`: `` (vide)
- `ORACLE_PASSWORD`: `` (vide)

⚠️ **Important** : Les valeurs par défaut ne fonctionneront pas en production. Vous devez définir les variables d'environnement avec vos valeurs réelles.

## Sécurité

- ✅ Le fichier `.env` est dans `.gitignore` et ne sera jamais commité
- ✅ Aucune information sensible n'est dans le code source
- ✅ Utilisez des variables d'environnement pour toutes les configurations sensibles

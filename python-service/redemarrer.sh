#!/bin/bash

# Script pour redémarrer le service Python Oracle

echo "🔄 Arrêt du service Python Oracle..."

# Trouver et arrêter le processus uvicorn
pkill -f "uvicorn.*main:app" || pkill -f "python3.*main.py" || echo "Aucun processus trouvé"

# Attendre un peu
sleep 2

echo "🚀 Démarrage du service Python Oracle..."

cd "$(dirname "$0")"

# Vérifier si l'environnement virtuel existe
if [ ! -d "venv" ]; then
    echo "❌ L'environnement virtuel n'existe pas."
    echo "Veuillez d'abord exécuter : ./installer.sh"
    exit 1
fi

source venv/bin/activate

# Démarrer en arrière-plan avec uvicorn
nohup uvicorn main:app --host 0.0.0.0 --port 8001 > service.log 2>&1 &

# Attendre que le service démarre
sleep 3

# Vérifier que le service est démarré
if curl -s http://localhost:8001/ > /dev/null; then
    echo "✅ Service démarré avec succès sur http://localhost:8001"
    echo "📋 Logs disponibles dans: service.log"
else
    echo "❌ Erreur: Le service ne répond pas"
    echo "📋 Vérifiez les logs: tail -f service.log"
fi

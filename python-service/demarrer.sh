#!/bin/bash

# Script de démarrage simple pour le service Python

cd "$(dirname "$0")"

# Vérifier si l'environnement virtuel existe
if [ ! -d "venv" ]; then
    echo "❌ L'environnement virtuel n'existe pas."
    echo "Veuillez d'abord exécuter : ./installer.sh"
    echo "Ou :"
    echo "  python3 -m venv venv"
    echo "  source venv/bin/activate"
    echo "  pip install -r requirements.txt"
    exit 1
fi

# Activer l'environnement virtuel
source venv/bin/activate

# Vérifier si uvicorn est installé
if ! command -v uvicorn &> /dev/null; then
    echo "❌ uvicorn n'est pas installé dans l'environnement virtuel."
    echo "Veuillez exécuter : pip install -r requirements.txt"
    exit 1
fi

echo "========================================="
echo "  Démarrage du Service Python"
echo "========================================="
echo ""
echo "Le service va démarrer sur http://localhost:8001"
echo "Appuyez sur Ctrl+C pour arrêter le serveur"
echo ""

# Démarrer le service
uvicorn main:app --host 0.0.0.0 --port 8001 --reload


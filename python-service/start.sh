#!/bin/bash

# Script de démarrage du service Python pour COFIBANK Charts API

echo "Démarrage du service Python COFIBANK Charts API..."

# Vérifier si Python 3 est installé
if ! command -v python3 &> /dev/null; then
    echo "Erreur: Python 3 n'est pas installé"
    exit 1
fi

# Vérifier si pip est installé
if ! command -v pip3 &> /dev/null; then
    echo "Erreur: pip3 n'est pas installé"
    exit 1
fi

# Installer les dépendances si nécessaire
if [ ! -d "venv" ]; then
    echo "Création de l'environnement virtuel..."
    python3 -m venv venv
fi

# Activer l'environnement virtuel
source venv/bin/activate

# Installer les dépendances
echo "Installation des dépendances..."
pip install -r requirements.txt

# Démarrer le serveur
echo "Démarrage du serveur sur http://localhost:8001..."
uvicorn main:app --host 0.0.0.0 --port 8001 --reload


#!/bin/bash

# Script d'installation pour le service Python COFIBANK Charts API

echo "========================================="
echo "  Installation du Service Python"
echo "========================================="
echo ""

# Vérifier si python3-venv est installé
if ! python3 -m venv --help > /dev/null 2>&1; then
    echo "❌ Le module python3-venv n'est pas installé."
    echo ""
    echo "Veuillez installer python3-venv avec :"
    echo "  sudo apt install python3-venv"
    echo ""
    exit 1
fi

# Créer l'environnement virtuel si nécessaire
if [ ! -d "venv" ]; then
    echo "📦 Création de l'environnement virtuel..."
    python3 -m venv venv
    if [ $? -ne 0 ]; then
        echo "❌ Erreur lors de la création de l'environnement virtuel"
        exit 1
    fi
    echo "✅ Environnement virtuel créé"
fi

# Activer l'environnement virtuel
echo "🔧 Activation de l'environnement virtuel..."
source venv/bin/activate

# Installer les dépendances
echo "📥 Installation des dépendances Python..."
pip install -r requirements.txt

if [ $? -ne 0 ]; then
    echo "❌ Erreur lors de l'installation des dépendances"
    exit 1
fi

echo ""
echo "✅ Installation terminée avec succès !"
echo ""
echo "Pour démarrer le service, exécutez :"
echo "  source venv/bin/activate"
echo "  uvicorn main:app --host 0.0.0.0 --port 8001 --reload"
echo ""


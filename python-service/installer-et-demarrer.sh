#!/bin/bash

# Script pour installer et démarrer le service Python COFIdash Charts API

echo "========================================="
echo "  Installation du Service Python"
echo "========================================="
echo ""

# Vérifier si python3-venv est installé
if ! python3 -m venv --help > /dev/null 2>&1; then
    echo "❌ Le module python3-venv n'est pas installé."
    echo ""
    echo "Veuillez installer python3-venv avec la commande suivante :"
    echo "  sudo apt install python3-venv"
    echo ""
    echo "Ou installez les dépendances globalement (non recommandé) :"
    echo "  pip3 install --user -r requirements.txt"
    echo ""
    exit 1
fi

# Créer l'environnement virtuel si nécessaire
if [ ! -d "venv" ]; then
    echo "📦 Création de l'environnement virtuel Python..."
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
echo "========================================="
echo "  Démarrage du Service Python"
echo "========================================="
echo ""
echo "Le service Python va démarrer sur http://localhost:8001"
echo "Appuyez sur Ctrl+C pour arrêter le serveur"
echo ""

# Démarrer le service
uvicorn main:app --host 0.0.0.0 --port 8001 --reload


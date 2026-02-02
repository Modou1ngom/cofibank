#!/bin/bash
# Script pour redémarrer le service Python
echo "🛑 Arrêt du service Python..."
pkill -f "uvicorn main:app"
sleep 2
echo "🚀 Démarrage du service Python..."
cd /home/modou-ngom/cofibank/python-service
source venv/bin/activate
nohup python -m uvicorn main:app --host 0.0.0.0 --port 8001 --reload > service.log 2>&1 &
echo "✅ Service redémarré avec --reload (rechargement automatique activé)"
echo "📋 PID: $(pgrep -f 'uvicorn main:app')"

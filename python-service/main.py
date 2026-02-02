"""
Service Python pour générer des graphiques pour COFIBANK Dashboard
Utilise FastAPI pour exposer des endpoints API
"""
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import logging

from routers import charts, oracle

# Configuration du logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Création de l'application FastAPI
app = FastAPI(title="COFIBANK Charts API", version="1.0.0")

# Configuration CORS pour permettre les requêtes depuis Laravel/Vue.js
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # En production, spécifier les origines autorisées
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Inclusion des routers
app.include_router(charts.router)
app.include_router(oracle.router)


@app.get("/")
async def root():
    """Endpoint de santé"""
    return {"status": "ok", "service": "COFIBANK Charts API", "version": "1.0.0"}


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)

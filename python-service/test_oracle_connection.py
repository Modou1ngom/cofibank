#!/usr/bin/env python3
"""
Script de test de connexion Oracle
Permet de diagnostiquer les problèmes de connexion à Oracle
"""

import socket
import sys
import os

# Configuration Oracle
ORACLE_HOST = os.getenv('ORACLE_HOST', '10.44.221.104')
ORACLE_PORT = int(os.getenv('ORACLE_PORT', '1522'))
ORACLE_SERVICE_NAME = os.getenv('ORACLE_SERVICE_NAME', 'FCPRDSNPDB')
ORACLE_USERNAME = os.getenv('ORACLE_USERNAME', 'report_sn')
ORACLE_PASSWORD = os.getenv('ORACLE_PASSWORD', 'rEport$ml221')

def test_network_connectivity():
    """Teste la connectivité réseau au serveur Oracle"""
    print(f"1. Test de connectivité réseau vers {ORACLE_HOST}:{ORACLE_PORT}...")
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(5)
        result = sock.connect_ex((ORACLE_HOST, ORACLE_PORT))
        sock.close()
        
        if result == 0:
            print(f"   ✓ Le port {ORACLE_PORT} est accessible")
            return True
        else:
            print(f"   ✗ Le port {ORACLE_PORT} n'est pas accessible (code: {result})")
            print(f"   Vérifiez que:")
            print(f"   - Le serveur Oracle est démarré")
            print(f"   - Le firewall n'bloque pas le port {ORACLE_PORT}")
            print(f"   - Le serveur Oracle écoute sur ce port")
            return False
    except socket.gaierror as e:
        print(f"   ✗ Erreur de résolution DNS: {e}")
        print(f"   Vérifiez que l'adresse IP {ORACLE_HOST} est correcte")
        return False
    except Exception as e:
        print(f"   ✗ Erreur lors du test de connectivité: {e}")
        return False

def test_oracle_connection():
    """Teste la connexion Oracle complète"""
    print(f"\n2. Test de connexion Oracle...")
    
    try:
        import oracledb
    except ImportError:
        try:
            import cx_Oracle as oracledb
        except ImportError:
            print("   ✗ oracledb ou cx_Oracle n'est pas installé")
            print("   Installez-le avec: pip install oracledb")
            return False
    
    try:
        # Essayer de créer une connexion
        dsn = oracledb.makedsn(
            ORACLE_HOST,
            ORACLE_PORT,
            service_name=ORACLE_SERVICE_NAME
        )
        
        print(f"   Connexion à: {ORACLE_USERNAME}@{ORACLE_HOST}:{ORACLE_PORT}/{ORACLE_SERVICE_NAME}")
        # Essayer avec timeout d'abord, puis sans si non supporté
        try:
            connection = oracledb.connect(
                user=ORACLE_USERNAME,
                password=ORACLE_PASSWORD,
                dsn=dsn,
                timeout=10
            )
        except TypeError:
            # Si timeout n'est pas supporté, essayer sans timeout
            connection = oracledb.connect(
                user=ORACLE_USERNAME,
                password=ORACLE_PASSWORD,
                dsn=dsn
            )
        
        # Tester une requête simple
        cursor = connection.cursor()
        cursor.execute("SELECT 1 FROM DUAL")
        result = cursor.fetchone()
        cursor.close()
        connection.close()
        
        print(f"   ✓ Connexion Oracle réussie!")
        print(f"   Résultat du test: {result[0]}")
        return True
        
    except oracledb.exceptions.OperationalError as e:
        print(f"   ✗ Erreur de connexion Oracle: {e}")
        print(f"\n   Détails:")
        print(f"   - Host: {ORACLE_HOST}")
        print(f"   - Port: {ORACLE_PORT}")
        print(f"   - Service Name: {ORACLE_SERVICE_NAME}")
        print(f"   - Username: {ORACLE_USERNAME}")
        print(f"\n   Vérifiez que:")
        print(f"   1. Le service Oracle est démarré")
        print(f"   2. Le service name '{ORACLE_SERVICE_NAME}' est correct")
        print(f"   3. Les identifiants sont corrects")
        print(f"   4. Le listener Oracle écoute sur le port {ORACLE_PORT}")
        return False
    except Exception as e:
        print(f"   ✗ Erreur inattendue: {e}")
        import traceback
        traceback.print_exc()
        return False

def main():
    print("=" * 60)
    print("Test de connexion Oracle - COFIBANK")
    print("=" * 60)
    print()
    
    # Test 1: Connectivité réseau
    network_ok = test_network_connectivity()
    
    if not network_ok:
        print("\n" + "=" * 60)
        print("ÉCHEC: Le serveur Oracle n'est pas accessible")
        print("=" * 60)
        print("\nActions recommandées:")
        print("1. Vérifiez que le serveur Oracle est démarré")
        print("2. Testez la connectivité avec: telnet", ORACLE_HOST, ORACLE_PORT)
        print("3. Vérifiez les règles du firewall")
        print("4. Contactez l'administrateur réseau si nécessaire")
        sys.exit(1)
    
    # Test 2: Connexion Oracle complète
    oracle_ok = test_oracle_connection()
    
    print("\n" + "=" * 60)
    if oracle_ok:
        print("✓ SUCCÈS: La connexion Oracle fonctionne correctement")
    else:
        print("✗ ÉCHEC: La connexion Oracle a échoué")
    print("=" * 60)
    
    sys.exit(0 if oracle_ok else 1)

if __name__ == "__main__":
    main()

<template>
  <div class="agency-performance-section">
    <div class="section-header">
      <h2 class="section-title">
        <span class="title-text">Performance des Agences</span>
        <span class="chart-icon">📊</span>
      </h2>
    </div>
    
    <div v-if="loading" class="loading-message">
      <p>🔄 Chargement des données depuis Oracle...</p>
    </div>
    <div v-if="errorMessage" class="error-message">
      <p>⚠️ {{ errorMessage }}</p>
    </div>

    <div class="performance-container">
      <!-- Section NOMBRE -->
      <div class="performance-category" data-category="nombre">
        <div class="category-label">
          <span class="label-text">NOMBRE</span>
        </div>
        <div class="top-flop-container">
          <!-- Top 5 Agence -->
          <div class="agency-list top-list">
            <div class="list-header top-header">
              <div class="header-content">
                <span class="thumbs-icon">👍</span>
                <span class="list-title">Top 5 Agence</span>
              </div>
              <div class="header-decoration"></div>
            </div>
            <ol class="agency-items">
              <li 
                v-for="(agency, index) in top5Nombre" 
                :key="index" 
                class="agency-item"
                :style="{ animationDelay: `${index * 0.1}s` }"
              >
                <span class="agency-number-badge">{{ index + 1 }}</span>
                <span class="agency-name">{{ agency }}</span>
                <span class="rank-indicator top-indicator">▲</span>
              </li>
              <li v-if="top5Nombre.length === 0" class="agency-item no-data">
                <span class="agency-name">Aucune donnée disponible</span>
              </li>
            </ol>
          </div>

          <!-- Flop 5 Agence -->
          <div class="agency-list flop-list">
            <div class="list-header flop-header">
              <div class="header-content">
                <span class="thumbs-icon">👎</span>
                <span class="list-title">Flop 5 Agence</span>
              </div>
              <div class="header-decoration"></div>
            </div>
            <ol class="agency-items">
              <li 
                v-for="(agency, index) in flop5Nombre" 
                :key="index" 
                class="agency-item"
                :style="{ animationDelay: `${index * 0.1}s` }"
              >
                <span class="agency-number-badge">{{ index + 1 }}</span>
                <span class="agency-name">{{ agency }}</span>
                <span class="rank-indicator flop-indicator">▼</span>
              </li>
              <li v-if="flop5Nombre.length === 0" class="agency-item no-data">
                <span class="agency-name">Aucune donnée disponible</span>
              </li>
            </ol>
          </div>
        </div>
      </div>

      <!-- Ligne de séparation rouge -->
      <div class="separator-line">
        <div class="separator-glow"></div>
      </div>

      <!-- Section VOLUME -->
      <div class="performance-category" data-category="volume">
        <div class="category-label">
          <span class="label-text">VOLUME</span>
        </div>
        <div class="top-flop-container">
          <!-- Top 5 Agence -->
          <div class="agency-list top-list">
            <div class="list-header top-header">
              <div class="header-content">
                <span class="thumbs-icon">👍</span>
                <span class="list-title">Top 5 Agence</span>
              </div>
              <div class="header-decoration"></div>
            </div>
            <ol class="agency-items">
              <li 
                v-for="(agency, index) in top5Volume" 
                :key="index" 
                class="agency-item"
                :style="{ animationDelay: `${index * 0.1}s` }"
              >
                <span class="agency-number-badge">{{ index + 1 }}</span>
                <span class="agency-name">{{ agency }}</span>
                <span class="rank-indicator top-indicator">▲</span>
              </li>
              <li v-if="top5Volume.length === 0" class="agency-item no-data">
                <span class="agency-name">Aucune donnée disponible</span>
              </li>
            </ol>
          </div>

          <!-- Flop 5 Agence -->
          <div class="agency-list flop-list">
            <div class="list-header flop-header">
              <div class="header-content">
                <span class="thumbs-icon">👎</span>
                <span class="list-title">Flop 5 Agence</span>
              </div>
              <div class="header-decoration"></div>
            </div>
            <ol class="agency-items">
              <li 
                v-for="(agency, index) in flop5Volume" 
                :key="index" 
                class="agency-item"
                :style="{ animationDelay: `${index * 0.1}s` }"
              >
                <span class="agency-number-badge">{{ index + 1 }}</span>
                <span class="agency-name">{{ agency }}</span>
                <span class="rank-indicator flop-indicator">▼</span>
              </li>
              <li v-if="flop5Volume.length === 0" class="agency-item no-data">
                <span class="agency-name">Aucune donnée disponible</span>
              </li>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AgencyPerformanceSection',
  props: {
    dataType: {
      type: String,
      default: 'default',
      validator: (value) => {
        return ['default', 'client', 'collection', 'credit', 'prepaid-cards', 'money-transfers', 'eps', 'divers'].includes(value);
      }
    }
  },
  data() {
    return {
      loading: false,
      errorMessage: null,
      allAgencies: [], // Toutes les agences avec leurs données
      // Données calculées pour la section NOMBRE
      top5Nombre: [],
      flop5Nombre: [],
      // Données calculées pour la section VOLUME
      top5Volume: [],
      flop5Volume: []
    }
  },
  mounted() {
    // Charger les données depuis l'API si nécessaire
    this.fetchPerformanceData();
  },
  watch: {
    dataType() {
      // Recharger les données quand le type change
      this.fetchPerformanceData();
    }
  },
  methods: {
    async fetchPerformanceData() {
      this.loading = true;
      this.errorMessage = null;
      
      try {
        // Déterminer l'endpoint selon le type de données
        let endpoint = '/api/oracle/data/clients';
        
        // Mapper les types de données vers les endpoints appropriés
        const endpointMap = {
          'client': '/api/oracle/data/clients',
          'collection': '/api/oracle/data/collection',
          'credit': '/api/oracle/data/production',
          'prepaid-cards': '/api/oracle/data/prepaid-cards',
          'money-transfers': '/api/oracle/data/money-transfers',
          'eps': '/api/oracle/data/eps',
          'divers': '/api/oracle/data/divers',
          'default': '/api/oracle/data/clients'
        };
        
        endpoint = endpointMap[this.dataType] || endpointMap['default'];
        
        // Paramètres pour la requête (utiliser les valeurs par défaut pour le mois en cours)
        const now = new Date();
        const params = {
          period: 'month',
          month: now.getMonth() + 1,
          year: now.getFullYear(),
          _t: Date.now()
        };
        
        console.log('Chargement des données de performance pour:', this.dataType, 'depuis:', endpoint);
        
        const response = await window.axios.get(endpoint, { 
          params,
          timeout: 120000,
          headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
          }
        });
        
        // Extraire toutes les agences depuis la réponse
        const agencies = this.extractAgenciesFromResponse(response.data);
        
        // Classer les agences
        this.classifyAgencies(agencies);
        
      } catch (error) {
        console.error('Erreur lors du chargement des données de performance:', error);
        this.errorMessage = 'Erreur lors du chargement des données. Utilisation des données par défaut.';
        // En cas d'erreur, utiliser les données par défaut
        this.setDefaultData();
      } finally {
        this.loading = false;
      }
    },
    
    extractAgenciesFromResponse(data) {
      const agencies = [];
      
      // Extraire les données selon le format de réponse
      let hierarchicalData = null;
      if (data && data.data && data.data.hierarchicalData) {
        hierarchicalData = data.data.hierarchicalData;
      } else if (data && data.hierarchicalData) {
        hierarchicalData = data.hierarchicalData;
      } else if (data && data.data) {
        hierarchicalData = data.data;
      }
      
      if (!hierarchicalData) {
        return agencies;
      }
      
      // Extraire les agences des territoires
      if (hierarchicalData.TERRITOIRE) {
        Object.values(hierarchicalData.TERRITOIRE).forEach(territory => {
          if (territory.agencies && Array.isArray(territory.agencies)) {
            territory.agencies.forEach(agency => {
              const agencyName = this.getAgencyName(agency);
              if (agencyName && agencyName.toUpperCase() !== 'INCONNU' && agencyName.toUpperCase() !== 'UNKNOWN') {
                agencies.push({
                  name: agencyName,
                  nouveauxClientsM: agency.nouveauxClientsM || 0,
                  nouveauxClientsM1: agency.nouveauxClientsM1 || 0,
                  fraisM: agency.fraisM || 0,
                  fraisM1: agency.fraisM1 || 0,
                  volume: agency.fraisM || 0, // Volume = frais pour l'instant
                  nombre: agency.nouveauxClientsM || 0 // Nombre = nouveaux clients
                });
              }
            });
          }
        });
      }
      
      // Extraire les agences des points de service
      if (hierarchicalData['POINT SERVICES']) {
        Object.values(hierarchicalData['POINT SERVICES']).forEach(servicePoint => {
          if (servicePoint.agencies && Array.isArray(servicePoint.agencies)) {
            servicePoint.agencies.forEach(agency => {
              const agencyName = this.getAgencyName(agency);
              if (agencyName && agencyName.toUpperCase() !== 'INCONNU' && agencyName.toUpperCase() !== 'UNKNOWN') {
                agencies.push({
                  name: agencyName,
                  nouveauxClientsM: agency.nouveauxClientsM || 0,
                  nouveauxClientsM1: agency.nouveauxClientsM1 || 0,
                  fraisM: agency.fraisM || 0,
                  fraisM1: agency.fraisM1 || 0,
                  volume: agency.fraisM || 0,
                  nombre: agency.nouveauxClientsM || 0
                });
              }
            });
          }
        });
      }
      
      // Si pas de données hiérarchiques, essayer d'autres formats
      if (agencies.length === 0 && data.territories) {
        Object.values(data.territories).forEach(territory => {
          if (territory.agencies && Array.isArray(territory.agencies)) {
            territory.agencies.forEach(agency => {
              const agencyName = this.getAgencyName(agency);
              if (agencyName && agencyName.toUpperCase() !== 'INCONNU' && agencyName.toUpperCase() !== 'UNKNOWN') {
                agencies.push({
                  name: agencyName,
                  nouveauxClientsM: agency.nouveauxClientsM || 0,
                  nouveauxClientsM1: agency.nouveauxClientsM1 || 0,
                  fraisM: agency.fraisM || 0,
                  fraisM1: agency.fraisM1 || 0,
                  volume: agency.fraisM || 0,
                  nombre: agency.nouveauxClientsM || 0
                });
              }
            });
          }
        });
      }
      
      return agencies;
    },
    
    getAgencyName(agency) {
      return agency.name || agency.AGENCE || agency.NOM_AGENCE || agency.agence || '';
    },
    
    classifyAgencies(agencies) {
      if (!agencies || agencies.length === 0) {
        console.warn('Aucune agence trouvée, utilisation des données par défaut');
        this.setDefaultData();
        return;
      }
      
      // Filtrer les agences avec des données valides
      const validAgencies = agencies.filter(a => 
        a.name && 
        a.name.trim() !== '' && 
        (a.nombre > 0 || a.volume > 0)
      );
      
      if (validAgencies.length === 0) {
        console.warn('Aucune agence avec des données valides, utilisation des données par défaut');
        this.setDefaultData();
        return;
      }
      
      // Classer par NOMBRE (nouveaux clients) - décroissant
      const sortedByNombre = [...validAgencies].sort((a, b) => {
        // Si les valeurs sont égales, trier par nom
        if (b.nombre === a.nombre) {
          return a.name.localeCompare(b.name);
        }
        return b.nombre - a.nombre;
      });
      
      // Top 5 par nombre (les meilleures)
      this.top5Nombre = sortedByNombre.slice(0, Math.min(5, sortedByNombre.length)).map(a => a.name);
      
      // Flop 5 par nombre (les moins performantes) - prendre les 5 dernières
      const flopByNombre = sortedByNombre.filter(a => a.nombre > 0); // Exclure les agences à 0
      if (flopByNombre.length > 0) {
        this.flop5Nombre = flopByNombre.slice(-Math.min(5, flopByNombre.length)).reverse().map(a => a.name);
      } else {
        this.flop5Nombre = [];
      }
      
      // Classer par VOLUME (frais ou autre métrique) - décroissant
      const sortedByVolume = [...validAgencies].sort((a, b) => {
        // Si les valeurs sont égales, trier par nom
        if (b.volume === a.volume) {
          return a.name.localeCompare(b.name);
        }
        return b.volume - a.volume;
      });
      
      // Top 5 par volume (les meilleures)
      this.top5Volume = sortedByVolume.slice(0, Math.min(5, sortedByVolume.length)).map(a => a.name);
      
      // Flop 5 par volume (les moins performantes) - prendre les 5 dernières
      const flopByVolume = sortedByVolume.filter(a => a.volume > 0); // Exclure les agences à 0
      if (flopByVolume.length > 0) {
        this.flop5Volume = flopByVolume.slice(-Math.min(5, flopByVolume.length)).reverse().map(a => a.name);
      } else {
        this.flop5Volume = [];
      }
      
      console.log('Agences classées:', {
        total: validAgencies.length,
        top5Nombre: this.top5Nombre,
        flop5Nombre: this.flop5Nombre,
        top5Volume: this.top5Volume,
        flop5Volume: this.flop5Volume
      });
    },
    
    setDefaultData() {
      // Données par défaut si aucune donnée n'est disponible
      this.top5Nombre = [
        'SAINT-LOUIS',
        'LOUGA',
        'DIOURBEL',
        'LINGUERE LA',
        'RUFISQUE'
      ];
      this.flop5Nombre = [
        'GRAND COMPTE',
        'OUROSSOGUI',
        'THIES',
        'SCAT URBAM',
        'CASTOR'
      ];
      this.top5Volume = [
        'LOUGA',
        'POINT E',
        'NIARRY TALLY',
        'RUFISQUE',
        'CASTOR'
      ];
      this.flop5Volume = [
        'GRAND COMPTE',
        'THIES',
        'KAOLACK',
        'LAMINE GUEYE',
        'MBOUR'
      ];
    }
  }
}
</script>

<style scoped>
.agency-performance-section {
  width: 100%;
  padding: 50px;
  background: 
    radial-gradient(ellipse at top left, rgba(220, 38, 38, 0.15) 0%, transparent 50%),
    radial-gradient(ellipse at bottom right, rgba(16, 185, 129, 0.15) 0%, transparent 50%),
    linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 25%, #2a2a2a 50%, #1f1f1f 75%, #0f0f0f 100%);
  min-height: calc(100vh - 100px);
  position: relative;
  overflow: hidden;
}

.agency-performance-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(circle at 20% 30%, rgba(220, 38, 38, 0.2) 0%, transparent 40%),
    radial-gradient(circle at 80% 70%, rgba(16, 185, 129, 0.2) 0%, transparent 40%),
    radial-gradient(circle at 50% 50%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
  pointer-events: none;
  z-index: 0;
  animation: backgroundPulse 8s ease-in-out infinite;
}

.agency-performance-section::after {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: 
    repeating-linear-gradient(
      0deg,
      transparent,
      transparent 2px,
      rgba(255, 255, 255, 0.03) 2px,
      rgba(255, 255, 255, 0.03) 4px
    );
  pointer-events: none;
  z-index: 0;
  animation: gridMove 20s linear infinite;
}

@keyframes backgroundPulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.8;
    transform: scale(1.1);
  }
}

@keyframes gridMove {
  0% {
    transform: translate(0, 0);
  }
  100% {
    transform: translate(50px, 50px);
  }
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
  position: relative;
  z-index: 1;
}

.section-title {
  font-size: 42px;
  font-weight: 800;
  color: white;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 20px;
  text-shadow: 
    0 0 20px rgba(255, 255, 255, 0.3),
    0 4px 15px rgba(0, 0, 0, 0.5);
  letter-spacing: 1px;
  position: relative;
}

.title-text {
  background: linear-gradient(135deg, #ffffff 0%, #f0f0f0 30%, #ffffff 60%, #e8e8e8 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  background-size: 200% 200%;
  animation: gradientShift 3s ease infinite;
  position: relative;
}

.title-text::after {
  content: 'Performance des Agences';
  position: absolute;
  top: 0;
  left: 0;
  background: linear-gradient(135deg, rgba(220, 38, 38, 0.3) 0%, rgba(16, 185, 129, 0.3) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  z-index: -1;
  filter: blur(10px);
  opacity: 0.7;
}

@keyframes gradientShift {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.chart-icon {
  font-size: 48px;
  color: #DC2626;
  filter: 
    drop-shadow(0 0 10px rgba(220, 38, 38, 0.8))
    drop-shadow(0 0 20px rgba(220, 38, 38, 0.4));
  animation: pulse 2s ease-in-out infinite, rotate 10s linear infinite;
  display: inline-block;
  transform-origin: center;
}

@keyframes rotate {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.performance-container {
  display: flex;
  flex-direction: column;
  gap: 0;
  position: relative;
  z-index: 1;
}

.performance-category {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  padding: 40px 0;
  gap: 30px;
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.category-label {
  writing-mode: vertical-rl;
  text-orientation: mixed;
  font-size: 28px;
  font-weight: 900;
  color: white;
  padding: 25px 15px;
  min-width: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: 
    linear-gradient(180deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.08) 50%, rgba(255, 255, 255, 0.15) 100%),
    linear-gradient(90deg, rgba(220, 38, 38, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
  border-radius: 20px;
  letter-spacing: 6px;
  border: 2px solid rgba(255, 255, 255, 0.2);
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.3),
    inset 0 0 20px rgba(255, 255, 255, 0.1),
    0 0 30px rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px) saturate(180%);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
  overflow: hidden;
}

.category-label::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
  animation: shine 3s infinite;
}

@keyframes shine {
  0% {
    transform: translateX(-100%) translateY(-100%) rotate(45deg);
  }
  100% {
    transform: translateX(100%) translateY(100%) rotate(45deg);
  }
}

.category-label:hover {
  background: 
    linear-gradient(180deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.15) 50%, rgba(255, 255, 255, 0.25) 100%),
    linear-gradient(90deg, rgba(220, 38, 38, 0.2) 0%, rgba(16, 185, 129, 0.2) 100%);
  transform: scale(1.08) translateX(-5px);
  box-shadow: 
    0 12px 40px rgba(0, 0, 0, 0.4),
    inset 0 0 30px rgba(255, 255, 255, 0.2),
    0 0 50px rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.4);
}

.top-flop-container {
  display: flex;
  gap: 25px;
  flex: 1;
}

.agency-list {
  flex: 1;
  background: 
    linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(250, 250, 250, 0.95) 100%),
    linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
  border-radius: 24px;
  padding: 30px;
  box-shadow: 
    0 20px 60px rgba(0, 0, 0, 0.2),
    0 0 0 1px rgba(255, 255, 255, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.6);
  min-height: 260px;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(10px);
}

.agency-list::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 5px;
  background: linear-gradient(90deg, 
    transparent 0%, 
    currentColor 20%, 
    currentColor 80%, 
    transparent 100%);
  opacity: 0.6;
  box-shadow: 0 0 20px currentColor;
}

.top-list::before {
  color: #10B981;
  background: linear-gradient(90deg, 
    transparent 0%, 
    #10B981 20%, 
    #34D399 50%,
    #10B981 80%, 
    transparent 100%);
}

.flop-list::before {
  color: #DC2626;
  background: linear-gradient(90deg, 
    transparent 0%, 
    #DC2626 20%, 
    #F87171 50%,
    #DC2626 80%, 
    transparent 100%);
}

.agency-list::after {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
  opacity: 0;
  transition: opacity 0.4s ease;
}

.agency-list:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 
    0 30px 80px rgba(0, 0, 0, 0.3),
    0 0 0 1px rgba(255, 255, 255, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.8),
    0 0 40px rgba(0, 0, 0, 0.1);
}

.agency-list:hover::after {
  opacity: 1;
}

.top-list:hover {
  box-shadow: 
    0 30px 80px rgba(16, 185, 129, 0.2),
    0 0 0 1px rgba(16, 185, 129, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.8),
    0 0 40px rgba(16, 185, 129, 0.15);
}

.flop-list:hover {
  box-shadow: 
    0 30px 80px rgba(220, 38, 38, 0.2),
    0 0 0 1px rgba(220, 38, 38, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.8),
    0 0 40px rgba(220, 38, 38, 0.15);
}

.list-header {
  display: flex;
  flex-direction: column;
  margin-bottom: 20px;
  position: relative;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.top-header .header-content {
  color: #10B981;
}

.flop-header .header-content {
  color: #DC2626;
}

.header-decoration {
  height: 3px;
  border-radius: 2px;
  background: linear-gradient(90deg, transparent, currentColor, transparent);
  opacity: 0.6;
}

.top-header .header-decoration {
  background: linear-gradient(90deg, transparent, #10B981, transparent);
}

.flop-header .header-decoration {
  background: linear-gradient(90deg, transparent, #DC2626, transparent);
}

.thumbs-icon {
  font-size: 28px;
  animation: bounce 2s ease-in-out infinite;
  filter: 
    drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3))
    drop-shadow(0 0 10px currentColor);
  transition: all 0.3s ease;
}

.top-header .thumbs-icon {
  filter: 
    drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3))
    drop-shadow(0 0 15px rgba(16, 185, 129, 0.6));
}

.flop-header .thumbs-icon {
  filter: 
    drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3))
    drop-shadow(0 0 15px rgba(220, 38, 38, 0.6));
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}

.list-title {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: 1px;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.top-list .list-title {
  font-size: 28px;
  font-weight: 900;
}

.flop-list .list-title {
  font-size: 28px;
  font-weight: 900;
}

.agency-items {
  list-style: none;
  padding: 0;
  margin: 0;
}

.agency-item {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 18px 16px;
  margin-bottom: 10px;
  border-radius: 14px;
  background: 
    linear-gradient(90deg, transparent 0%, rgba(0, 0, 0, 0.02) 50%, transparent 100%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.5) 0%, transparent 100%);
  border-left: 4px solid transparent;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  animation: slideIn 0.6s ease-out backwards;
  position: relative;
  overflow: hidden;
  cursor: pointer;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.agency-item::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 0;
  transition: width 0.3s ease;
  z-index: 0;
}

.top-list .agency-item::before {
  background: linear-gradient(90deg, rgba(16, 185, 129, 0.1), transparent);
}

.flop-list .agency-item::before {
  background: linear-gradient(90deg, rgba(220, 38, 38, 0.1), transparent);
}

.agency-item:hover {
  transform: translateX(8px) scale(1.02);
  background: 
    linear-gradient(90deg, rgba(0, 0, 0, 0.06) 0%, rgba(0, 0, 0, 0.03) 50%, transparent 100%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0.3) 100%);
  box-shadow: 
    0 4px 15px rgba(0, 0, 0, 0.15),
    inset 0 1px 0 rgba(255, 255, 255, 0.6);
  border-left-width: 5px;
}

.agency-item:hover::before {
  width: 100%;
  opacity: 0.8;
}

.top-list .agency-item:hover {
  border-left-color: #10B981;
  box-shadow: 
    0 4px 15px rgba(16, 185, 129, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.6),
    0 0 20px rgba(16, 185, 129, 0.1);
}

.flop-list .agency-item:hover {
  border-left-color: #DC2626;
  box-shadow: 
    0 4px 15px rgba(220, 38, 38, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.6),
    0 0 20px rgba(220, 38, 38, 0.1);
}

.agency-item:last-child {
  margin-bottom: 0;
}

.agency-item.no-data {
  opacity: 0.6;
  font-style: italic;
  justify-content: center;
}

.agency-item.no-data .agency-name {
  color: #9ca3af;
}

.agency-number-badge {
  font-weight: 900;
  min-width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  font-size: 18px;
  border-radius: 14px;
  position: relative;
  z-index: 1;
  box-shadow: 
    0 4px 12px rgba(0, 0, 0, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.3),
    inset 0 -1px 0 rgba(0, 0, 0, 0.2);
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.top-list .agency-number-badge {
  background: linear-gradient(135deg, #10B981 0%, #059669 50%, #047857 100%);
  color: white;
  box-shadow: 
    0 4px 12px rgba(16, 185, 129, 0.4),
    inset 0 1px 0 rgba(255, 255, 255, 0.3),
    inset 0 -1px 0 rgba(0, 0, 0, 0.2),
    0 0 15px rgba(16, 185, 129, 0.3);
}

.flop-list .agency-number-badge {
  background: linear-gradient(135deg, #DC2626 0%, #B91C1C 50%, #991B1B 100%);
  color: white;
  box-shadow: 
    0 4px 12px rgba(220, 38, 38, 0.4),
    inset 0 1px 0 rgba(255, 255, 255, 0.3),
    inset 0 -1px 0 rgba(0, 0, 0, 0.2),
    0 0 15px rgba(220, 38, 38, 0.3);
}

.agency-item:hover .agency-number-badge {
  transform: scale(1.15) rotate(8deg);
  box-shadow: 
    0 6px 20px rgba(0, 0, 0, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.4),
    inset 0 -1px 0 rgba(0, 0, 0, 0.3);
}

.top-list .agency-item:hover .agency-number-badge {
  box-shadow: 
    0 6px 20px rgba(16, 185, 129, 0.5),
    inset 0 1px 0 rgba(255, 255, 255, 0.4),
    inset 0 -1px 0 rgba(0, 0, 0, 0.3),
    0 0 25px rgba(16, 185, 129, 0.4);
}

.flop-list .agency-item:hover .agency-number-badge {
  box-shadow: 
    0 6px 20px rgba(220, 38, 38, 0.5),
    inset 0 1px 0 rgba(255, 255, 255, 0.4),
    inset 0 -1px 0 rgba(0, 0, 0, 0.3),
    0 0 25px rgba(220, 38, 38, 0.4);
}

.agency-name {
  color: #1f2937;
  font-size: 17px;
  font-weight: 700;
  flex: 1;
  position: relative;
  z-index: 1;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;
}

.agency-item:hover .agency-name {
  color: #111827;
  transform: translateX(3px);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.rank-indicator {
  font-size: 22px;
  position: relative;
  z-index: 1;
  opacity: 0.8;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  font-weight: 900;
}

.agency-item:hover .rank-indicator {
  opacity: 1;
  transform: scale(1.3) translateY(-2px);
}

.top-indicator {
  color: #10B981;
  filter: 
    drop-shadow(0 0 8px rgba(16, 185, 129, 0.8))
    drop-shadow(0 2px 4px rgba(16, 185, 129, 0.4));
  text-shadow: 0 0 10px rgba(16, 185, 129, 0.6);
}

.flop-indicator {
  color: #DC2626;
  filter: 
    drop-shadow(0 0 8px rgba(220, 38, 38, 0.8))
    drop-shadow(0 2px 4px rgba(220, 38, 38, 0.4));
  text-shadow: 0 0 10px rgba(220, 38, 38, 0.6);
}

.separator-line {
  width: 100%;
  height: 6px;
  background: linear-gradient(90deg, 
    transparent 0%, 
    rgba(220, 38, 38, 0.3) 10%,
    #DC2626 30%,
    #EF4444 50%,
    #DC2626 70%,
    rgba(220, 38, 38, 0.3) 90%,
    transparent 100%);
  margin: 50px 0;
  border-radius: 3px;
  position: relative;
  overflow: hidden;
  box-shadow: 
    0 0 20px rgba(220, 38, 38, 0.5),
    inset 0 0 10px rgba(220, 38, 38, 0.3);
}

.separator-glow {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, 
    transparent 0%, 
    rgba(255, 255, 255, 0.8) 50%, 
    transparent 100%);
  animation: shimmer 2s infinite;
  box-shadow: 0 0 30px rgba(255, 255, 255, 0.8);
}

@keyframes shimmer {
  0% {
    left: -100%;
  }
  100% {
    left: 100%;
  }
}

.loading-message {
  background: #E3F2FD;
  border: 1px solid #2196F3;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 30px;
  text-align: center;
  color: #1976D2;
  font-weight: 500;
  font-size: 16px;
}

.error-message {
  background: #FFEBEE;
  border: 1px solid #F44336;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 30px;
  text-align: center;
  color: #C62828;
  font-weight: 500;
  font-size: 16px;
}

@media (max-width: 768px) {
  .agency-performance-section {
    padding: 20px;
  }

  .section-title {
    font-size: 24px;
  }

  .chart-icon {
    font-size: 28px;
  }

  .performance-category {
    flex-direction: column;
    padding: 30px 0;
  }

  .category-label {
    writing-mode: horizontal-tb;
    text-orientation: mixed;
    min-width: auto;
    width: 100%;
    margin-right: 0;
    margin-bottom: 20px;
    padding: 15px;
  }

  .top-flop-container {
    flex-direction: column;
    gap: 20px;
  }

  .agency-list {
    min-height: auto;
  }
}
</style>

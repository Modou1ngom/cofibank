<template>
  <div class="money-transfer-section">
    <div class="section-header">
      <h2 class="section-title">Activités de Transferts d'Argent</h2>
      <div class="period-selector">
        <select v-model="selectedMonth" class="month-select" @change="handleMonthChange">
          <option v-for="(month, index) in months" :key="index" :value="index + 1">
            {{ month }}
          </option>
        </select>
        <select v-model="selectedYear" class="year-select" @change="handleYearChange">
          <option v-for="year in years" :key="year" :value="year">
            {{ year }}
          </option>
        </select>
      </div>
    </div>
    
    <!-- Message de chargement ou d'erreur -->
    <div v-if="loading" class="loading-message">
      <p>🔄 Chargement des données depuis Oracle...</p>
    </div>
    <div v-if="errorMessage" class="error-message">
      <p>⚠️ {{ errorMessage }}</p>
    </div>
    
    <!-- Contenu principal -->
    <div v-if="!loading && !errorMessage" class="transfer-content">
      <!-- Tableau détaillé à gauche -->
      <div class="transfer-table-container">
        <table class="transfer-table">
          <thead>
            <tr>
              <th>AGENCE</th>
              <th>Objectif</th>
              <th>Volume transfert M</th>
              <th>Volume transfert M-1</th>
              <th>Variation (Volume)</th>
              <th>Variation (%)</th>
              <th>TRO</th>
              <th>Contribution agence sur la zone</th>
              <th>Commission générée</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="(agency, index) in agencies" 
              :key="index"
              :class="getRowClass(agency.agence)"
            >
              <td :class="getAgencyClass(agency.agence)">{{ agency.agence }}</td>
              <td>{{ formatNumber(agency.objectif) }}</td>
              <td>{{ formatNumber(agency.volume_m) }}</td>
              <td>{{ formatNumber(agency.volume_m1) }}</td>
              <td :class="getVariationClass(agency.variation_volume)">
                {{ formatNumber(agency.variation_volume) }}
              </td>
              <td :class="getVariationClass(agency.variation_pct)">
                {{ formatPercent(agency.variation_pct) }}
              </td>
              <td :class="getTROClass(agency.tro)">
                {{ formatPercent(agency.tro) }}
              </td>
              <td :class="getContributionClass(agency.contribution)">
                {{ formatPercent(agency.contribution) }}
              </td>
              <td>{{ formatNumber(agency.commission, 3) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Résumé par service à droite -->
      <div class="transfer-services-container">
        <h3 class="services-title">Volume de transfert</h3>
        <div class="services-list">
          <div 
            v-for="(service, index) in services" 
            :key="index"
            class="service-item"
          >
            <div class="service-logo">
              <div class="service-logo-placeholder">{{ getServiceInitials(service.service) }}</div>
            </div>
            <div class="service-name">{{ service.service }}</div>
            <div class="service-volume">
              <div class="volume-box">{{ formatNumber(service.volume) }} M</div>
              <div class="arrow">→</div>
              <div class="commission-box">{{ formatNumber(service.commission, 2) }} M</div>
            </div>
            <div class="service-label">Commission générée</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'MoneyTransferSection',
  data() {
    const now = new Date();
    return {
      loading: false,
      errorMessage: null,
      agencies: [],
      services: [],
      selectedMonth: now.getMonth() + 1,
      selectedYear: now.getFullYear(),
      months: [
        'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
      ],
      years: Array.from({ length: 5 }, (_, i) => now.getFullYear() - i)
    }
  },
  mounted() {
    this.fetchTransferData();
  },
  methods: {
    async fetchTransferData() {
      this.loading = true;
      this.errorMessage = null;
      
      try {
        const response = await fetch(
          `http://localhost:8001/api/oracle/data/transfers?month=${this.selectedMonth}&year=${this.selectedYear}`
        );
        
        if (!response.ok) {
          throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.data) {
          this.agencies = result.data.agencies || [];
          this.services = result.data.services || [];
        } else {
          throw new Error('Format de données invalide');
        }
      } catch (error) {
        console.error('Erreur lors de la récupération des données de transferts:', error);
        this.errorMessage = `Erreur: ${error.message}`;
      } finally {
        this.loading = false;
      }
    },
    handleMonthChange() {
      this.fetchTransferData();
    },
    handleYearChange() {
      this.fetchTransferData();
    },
    formatNumber(value, decimals = 0) {
      if (value === null || value === undefined) return '0';
      return Number(value).toLocaleString('fr-FR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
      });
    },
    formatPercent(value) {
      if (value === null || value === undefined) return '0%';
      return `${Number(value).toFixed(0)}%`;
    },
    getRowClass(agence) {
      const upperAgence = agence.toUpperCase();
      if (upperAgence.includes('ZONE') || upperAgence.includes('CORPORATE') || 
          upperAgence.includes('RETAIL') || upperAgence === 'TOTAL') {
        return 'summary-row';
      }
      return '';
    },
    getAgencyClass(agence) {
      const upperAgence = agence.toUpperCase();
      if (upperAgence.includes('ZONE') || upperAgence.includes('CORPORATE') || 
          upperAgence.includes('RETAIL') || upperAgence === 'TOTAL') {
        return 'summary-cell';
      }
      return '';
    },
    getVariationClass(value) {
      if (value > 0) return 'positive';
      if (value < 0) return 'negative';
      return '';
    },
    getTROClass(value) {
      if (value >= 100) return 'positive';
      if (value >= 80) return 'moderate';
      return 'negative';
    },
    getContributionClass(value) {
      if (value >= 20) return 'positive';
      if (value >= 10) return 'moderate';
      return 'negative';
    },
    getServiceInitials(serviceName) {
      if (!serviceName) return '?';
      const words = serviceName.split(' ');
      if (words.length >= 2) {
        return words[0][0] + words[1][0];
      }
      return serviceName.substring(0, 2).toUpperCase();
    }
  }
}
</script>

<style scoped>
.money-transfer-section {
  padding: 20px;
  background: #f5f5f5;
  min-height: 100vh;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  background: white;
  padding: 15px 20px;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-title {
  font-size: 20px;
  font-weight: 600;
  color: #1A4D3A;
  margin: 0;
}

.period-selector {
  display: flex;
  gap: 10px;
}

.month-select,
.year-select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  background: white;
  cursor: pointer;
}

.loading-message,
.error-message {
  padding: 20px;
  text-align: center;
  background: white;
  border-radius: 4px;
  margin-bottom: 20px;
}

.error-message {
  background: #fee;
  color: #c33;
}

.transfer-content {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 20px;
}

.transfer-table-container {
  background: white;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  overflow-x: auto;
}

.transfer-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.transfer-table thead {
  background: #1A4D3A;
  color: white;
}

.transfer-table th {
  padding: 12px 8px;
  text-align: left;
  font-weight: 600;
  font-size: 12px;
  white-space: nowrap;
}

.transfer-table tbody tr {
  border-bottom: 1px solid #eee;
}

.transfer-table tbody tr:hover {
  background: #f9f9f9;
}

.transfer-table td {
  padding: 10px 8px;
  text-align: right;
}

.transfer-table td:first-child {
  text-align: left;
}

.summary-row {
  background: #e8e8e8 !important;
  font-weight: 600;
}

.summary-cell {
  font-weight: 600;
}

.positive {
  color: #22c55e;
  font-weight: 600;
}

.negative {
  color: #ef4444;
  font-weight: 600;
}

.moderate {
  color: #f59e0b;
  font-weight: 600;
}

.transfer-services-container {
  background: white;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  padding: 20px;
}

.services-title {
  font-size: 16px;
  font-weight: 600;
  color: #1A4D3A;
  margin: 0 0 20px 0;
  text-align: center;
}

.services-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.service-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 15px;
  border: 1px solid #eee;
  border-radius: 4px;
  background: #fafafa;
}

.service-logo {
  width: 60px;
  height: 60px;
  margin-bottom: 10px;
}

.service-logo-placeholder {
  width: 100%;
  height: 100%;
  background: #1A4D3A;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  font-weight: 600;
  font-size: 18px;
}

.service-name {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 10px;
  text-align: center;
}

.service-volume {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 5px;
}

.volume-box {
  background: #ef4444;
  color: white;
  padding: 8px 12px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 14px;
  min-width: 80px;
  text-align: center;
}

.arrow {
  color: #666;
  font-size: 18px;
}

.commission-box {
  background: #f0f0f0;
  border: 1px solid #ef4444;
  color: #333;
  padding: 8px 12px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 14px;
  min-width: 80px;
  text-align: center;
}

.service-label {
  font-size: 11px;
  color: #666;
  margin-top: 5px;
}

@media (max-width: 1200px) {
  .transfer-content {
    grid-template-columns: 1fr;
  }
  
  .transfer-services-container {
    margin-top: 20px;
  }
}
</style>

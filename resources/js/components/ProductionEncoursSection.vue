<template>
  <div class="client-section">
    <div class="section-header">
      <h2 class="section-title">Evolution de l'encours crédit - {{ getPeriodTitle }}</h2>
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
      Chargement des données depuis Oracle...
    </div>
    <div v-if="error" class="error-message">
      ⚠️ {{ error }}
    </div>
    <div v-if="!loading && !error && (!encoursData || encoursData.length === 0)" class="info-message">
      ℹ️ Aucune donnée disponible. Vérifiez la connexion Oracle ou activez le mode fallback.
    </div>
    
    <!-- Tableau d'évolution de l'encours crédit -->
    <div class="zone-agencies-section" v-if="!loading && encoursData && encoursData.length > 0">
      <div class="table-container">
        <table class="encours-table">
          <thead>
            <tr>
              <th rowspan="2">AGENCE</th>
              <th colspan="4">PTF</th>
              <th colspan="4">Produit d'intérêt</th>
            </tr>
            <tr>
              <th>PTF {{ periodLabels.m1 }}</th>
              <th>PTF {{ periodLabels.m }}</th>
              <th>Variation<br>(Millions FCFA)</th>
              <th>Taux de croissance</th>
              <th>Produit d'intérêt {{ periodLabels.m1 }}</th>
              <th>Produit d'intérêt {{ periodLabels.m }}</th>
              <th>Variation<br>(Millions FCFA)</th>
              <th>Taux de croissance</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, index) in processedData" :key="index" 
                :class="getRowClass(row)"
                @click="row.expandable ? toggleExpand(row.key) : null">
              <td :class="getCellClass(row)">
                <button v-if="row.expandable" class="expand-btn" @click.stop="toggleExpand(row.key)">
                  {{ expandedSections[row.key] ? '−' : '+' }}
                </button>
                <strong v-if="row.isTotal || row.isSubTotal">{{ row.AGENCE }}</strong>
                <span v-else>{{ row.AGENCE }}</span>
              </td>
              <td :class="getCellClass(row)">
                <strong v-if="row.isTotal || row.isSubTotal">{{ formatNumber(row.PTF_M1) }}</strong>
                <span v-else>{{ formatNumber(row.PTF_M1) }}</span>
              </td>
              <td :class="getCellClass(row)">
                <strong v-if="row.isTotal || row.isSubTotal">{{ formatNumber(row.PTF_M) }}</strong>
                <span v-else>{{ formatNumber(row.PTF_M) }}</span>
              </td>
              <td :class="[getCellClass(row), getVariationClass(row.VARIATION_PTF)]">
                <strong v-if="row.isTotal || row.isSubTotal">{{ formatVariation(row.VARIATION_PTF) }}</strong>
                <span v-else>{{ formatVariation(row.VARIATION_PTF) }}</span>
              </td>
              <td :class="[getCellClass(row), getGrowthClass(row.TAUX_CROISSANCE_PTF)]">
                <strong v-if="row.isTotal || row.isSubTotal">{{ formatGrowthRate(row.TAUX_CROISSANCE_PTF) }}</strong>
                <span v-else>{{ formatGrowthRate(row.TAUX_CROISSANCE_PTF) }}</span>
              </td>
              <td :class="getCellClass(row)">
                <strong v-if="row.isTotal || row.isSubTotal">{{ formatNumber(row.PRODUIT_INT_M1) }}</strong>
                <span v-else>{{ formatNumber(row.PRODUIT_INT_M1) }}</span>
              </td>
              <td :class="getCellClass(row)">
                <strong v-if="row.isTotal || row.isSubTotal">{{ formatNumber(row.PRODUIT_INT_M) }}</strong>
                <span v-else>{{ formatNumber(row.PRODUIT_INT_M) }}</span>
              </td>
              <td :class="[getCellClass(row), getVariationClass(row.VARIATION_PRODUIT_INT)]">
                <strong v-if="row.isTotal || row.isSubTotal">{{ formatVariation(row.VARIATION_PRODUIT_INT) }}</strong>
                <span v-else>{{ formatVariation(row.VARIATION_PRODUIT_INT) }}</span>
              </td>
              <td :class="[getCellClass(row), getGrowthClass(row.TAUX_CROISSANCE_PRODUIT_INT)]">
                <strong v-if="row.isTotal || row.isSubTotal">{{ formatGrowthRate(row.TAUX_CROISSANCE_PRODUIT_INT) }}</strong>
                <span v-else>{{ formatGrowthRate(row.TAUX_CROISSANCE_PRODUIT_INT) }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ProductionEncoursSection',
  props: {
    selectedZoneProp: {
      type: String,
      default: null
    }
  },
  data() {
    const now = new Date();
    return {
      selectedMonth: now.getMonth() + 1,
      selectedYear: now.getFullYear(),
      months: [
        'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
      ],
      loading: false,
      error: null,
      encoursData: [],
      expandedSections: {}
    };
  },
  computed: {
    years() {
      const currentYear = new Date().getFullYear();
      const years = [];
      for (let i = currentYear - 5; i <= currentYear + 2; i++) {
        years.push(i);
      }
      return years;
    },
    periodLabels() {
      const monthM1 = this.selectedMonth === 1 ? 12 : this.selectedMonth - 1;
      const yearM1 = this.selectedMonth === 1 ? this.selectedYear - 1 : this.selectedYear;
      return {
        m: this.months[this.selectedMonth - 1] + ' ' + this.selectedYear,
        m1: this.months[monthM1 - 1] + ' ' + yearM1
      };
    },
    getPeriodTitle() {
      return this.months[this.selectedMonth - 1] + ' ' + this.selectedYear;
    },
    processedData() {
      if (!this.encoursData || this.encoursData.length === 0) {
        return [];
      }
      
      // Grouper les données par agence et calculer les totaux
      const dataMap = {};
      const processed = [];
      
      // Parcourir les données brutes
      this.encoursData.forEach(item => {
        const agence = item.AGENCE || 'Inconnu';
        
        if (!dataMap[agence]) {
          dataMap[agence] = {
            AGENCE: agence,
            PTF_M1: 0,
            PTF_M: 0,
            VARIATION_PTF: 0,
            TAUX_CROISSANCE_PTF: 0,
            PRODUIT_INT_M1: 0,
            PRODUIT_INT_M: 0,
            VARIATION_PRODUIT_INT: 0,
            TAUX_CROISSANCE_PRODUIT_INT: 0,
            isTotal: false,
            isSubTotal: false,
            expandable: false
          };
        }
        
        dataMap[agence].PTF_M1 += item.PTF_M1 || 0;
        dataMap[agence].PTF_M += item.PTF_M || 0;
        dataMap[agence].PRODUIT_INT_M1 += item.PRODUIT_INT_M1 || 0;
        dataMap[agence].PRODUIT_INT_M += item.PRODUIT_INT_M || 0;
      });
      
      // Calculer les variations et taux de croissance
      Object.values(dataMap).forEach(item => {
        item.VARIATION_PTF = item.PTF_M - item.PTF_M1;
        item.TAUX_CROISSANCE_PTF = item.PTF_M1 > 0 
          ? ((item.PTF_M - item.PTF_M1) / item.PTF_M1) * 100 
          : 0;
        
        item.VARIATION_PRODUIT_INT = item.PRODUIT_INT_M - item.PRODUIT_INT_M1;
        item.TAUX_CROISSANCE_PRODUIT_INT = item.PRODUIT_INT_M1 > 0 
          ? ((item.PRODUIT_INT_M - item.PRODUIT_INT_M1) / item.PRODUIT_INT_M1) * 100 
          : 0;
        
        processed.push(item);
      });
      
      // Calculer le total
      const total = {
        AGENCE: 'TOTAL',
        PTF_M1: processed.reduce((sum, item) => sum + item.PTF_M1, 0),
        PTF_M: processed.reduce((sum, item) => sum + item.PTF_M, 0),
        PRODUIT_INT_M1: processed.reduce((sum, item) => sum + item.PRODUIT_INT_M1, 0),
        PRODUIT_INT_M: processed.reduce((sum, item) => sum + item.PRODUIT_INT_M, 0),
        isTotal: true,
        isSubTotal: false,
        expandable: false
      };
      
      total.VARIATION_PTF = total.PTF_M - total.PTF_M1;
      total.TAUX_CROISSANCE_PTF = total.PTF_M1 > 0 
        ? ((total.PTF_M - total.PTF_M1) / total.PTF_M1) * 100 
        : 0;
      
      total.VARIATION_PRODUIT_INT = total.PRODUIT_INT_M - total.PRODUIT_INT_M1;
      total.TAUX_CROISSANCE_PRODUIT_INT = total.PRODUIT_INT_M1 > 0 
        ? ((total.PRODUIT_INT_M - total.PRODUIT_INT_M1) / total.PRODUIT_INT_M1) * 100 
        : 0;
      
      processed.push(total);
      
      return processed;
    }
  },
  mounted() {
    this.fetchEncoursData();
  },
  watch: {
    selectedMonth() {
      this.fetchEncoursData();
    },
    selectedYear() {
      this.fetchEncoursData();
    }
  },
  methods: {
    toggleExpand(key) {
      this.expandedSections[key] = !this.expandedSections[key];
    },
    getRowClass(row) {
      if (row.isTotal) return 'total-row';
      if (row.isSubTotal) return 'subtotal-row';
      return 'data-row';
    },
    getCellClass(row) {
      if (row.isTotal) return 'total-cell';
      if (row.isSubTotal) return 'subtotal-cell';
      return 'data-cell';
    },
    formatNumber(num) {
      if (num === null || num === undefined) return '-';
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(num);
    },
    formatVariation(num) {
      if (num === null || num === undefined || num === 0) return '-';
      const formatted = Math.abs(num).toFixed(2);
      return num > 0 ? `+${formatted}` : `-${formatted}`;
    },
    formatGrowthRate(num) {
      if (num === null || num === undefined || isNaN(num)) return '-';
      const formatted = Math.abs(num).toFixed(2);
      const arrow = num > 0 ? '↑' : num < 0 ? '↓' : '';
      return num > 0 ? `+${formatted}% ${arrow}` : `${formatted}% ${arrow}`;
    },
    getVariationClass(value) {
      if (value === null || value === undefined || value === 0) return '';
      return value > 0 ? 'positive' : 'negative';
    },
    getGrowthClass(value) {
      if (value === null || value === undefined || isNaN(value)) return '';
      return value > 0 ? 'positive' : 'negative';
    },
    async fetchEncoursData() {
      this.loading = true;
      this.error = null;
      
      try {
        const apiUrl = '/api/oracle/data/encours-credit';
        const params = {
          month_m: this.selectedMonth,
          year_m: this.selectedYear
        };
        
        const response = await axios.get(apiUrl, { params });
        const apiData = response.data;
        
        if (apiData.error) {
          throw new Error(apiData.detail || apiData.error || 'Erreur lors de la récupération des données');
        }
        
        this.encoursData = apiData.data || [];
        
      } catch (error) {
        console.error('Erreur lors de la récupération des données d\'encours crédit:', error);
        
        let errorMessage = 'Erreur lors du chargement des données';
        
        if (error.response) {
          const errorData = error.response.data;
          errorMessage = errorData?.detail || errorData?.error || `Erreur HTTP ${error.response.status}`;
        } else if (error.request) {
          errorMessage = '⚠️ Impossible de se connecter au service Python.';
        } else {
          errorMessage = error.message || 'Erreur inconnue';
        }
        
        this.error = errorMessage;
        this.encoursData = [];
      } finally {
        this.loading = false;
      }
    },
    handleMonthChange() {
      this.fetchEncoursData();
    },
    handleYearChange() {
      this.fetchEncoursData();
    }
  }
}
</script>

<style scoped>
.client-section {
  margin-bottom: 30px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.section-title {
  font-size: 24px;
  font-weight: 600;
  margin: 0;
  color: #333;
}

.period-selector {
  display: flex;
  gap: 10px;
  align-items: center;
}

.month-select,
.year-select {
  padding: 8px 12px;
  border: 1px solid #DDD;
  border-radius: 4px;
  font-size: 14px;
  background: white;
  color: #333;
  cursor: pointer;
}

.month-select:hover,
.year-select:hover {
  border-color: #1A4D3A;
}

.month-select:focus,
.year-select:focus {
  outline: none;
  border-color: #1A4D3A;
  box-shadow: 0 0 0 2px rgba(26, 77, 58, 0.1);
}

.zone-agencies-section {
  margin-top: 30px;
}

.table-container {
  overflow-x: auto;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.encours-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  min-width: 1200px;
}

.encours-table thead {
  background: #2A2A2A;
  color: white;
}

.encours-table th {
  padding: 12px 8px;
  text-align: center;
  font-weight: 600;
  font-size: 12px;
  border-right: 1px solid #444;
  white-space: nowrap;
}

.encours-table th:first-child {
  text-align: left;
  padding-left: 16px;
}

.encours-table td {
  padding: 10px 8px;
  font-size: 13px;
  text-align: center;
  border-bottom: 1px solid #EEE;
  border-right: 1px solid #F0F0F0;
}

.encours-table td:first-child {
  text-align: left;
  padding-left: 16px;
}

.data-row {
  background: white;
}

.data-row:hover {
  background: #f5f5f5;
}

.subtotal-row {
  background: #E0E0E0;
  font-weight: 600;
}

.total-row {
  background: #DC2626;
  font-weight: 600;
  color: white;
}

.total-cell {
  color: white;
}

.subtotal-cell {
  color: #333;
}

.data-cell {
  color: #333;
}

.expand-btn {
  width: 24px;
  height: 24px;
  border: 1px solid rgba(0, 0, 0, 0.3);
  background: rgba(255, 255, 255, 0.5);
  color: #333;
  border-radius: 3px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 14px;
  margin-right: 8px;
}

.positive {
  color: #10B981;
  font-weight: 600;
}

.negative {
  color: #EF4444;
  font-weight: 600;
}

.loading-message {
  padding: 20px;
  text-align: center;
  background: #F0F9FF;
  border: 1px solid #0EA5E9;
  border-radius: 8px;
  color: #0369A1;
  font-weight: 500;
  margin: 20px 0;
}

.error-message {
  padding: 20px;
  text-align: left;
  background: #FEF2F2;
  border: 2px solid #EF4444;
  border-radius: 8px;
  color: #DC2626;
  font-weight: 500;
  margin: 20px 0;
  white-space: pre-line;
}

.info-message {
  padding: 15px;
  text-align: center;
  background: #EFF6FF;
  border: 1px solid #3B82F6;
  border-radius: 8px;
  color: #1E40AF;
  font-weight: 500;
  margin: 20px 0;
}
</style>
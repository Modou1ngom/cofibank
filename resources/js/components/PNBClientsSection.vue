<template>
  <div class="pnb-section">
    <div class="filters-row">
      <div class="filter-group">
        <label>Année</label>
        <input type="text" v-model="year" class="filter-input" />
      </div>
      <div class="filter-group">
        <label>Mois</label>
        <input type="text" v-model="month" class="filter-input" />
      </div>
    </div>
    
    <h2 class="section-title">PNB Clients</h2>
    
    <div class="kpi-row">
      <KPICard label="Mois" :value="pnbData.mois" currency="USD" />
      <KPICard label="Mois-1" :value="pnbData.mois1" currency="USD" />
      <KPICard label="Mois-1N" :value="pnbData.mois1Percent" percentage />
      <KPICard label="Année-1" :value="pnbData.annee1" currency="USD" />
      <KPICard label="Cumul Année" :value="pnbData.cumulAnnee" currency="USD" />
      <KPICard label="Cumul Année-1" :value="pnbData.cumulAnnee1" currency="USD" />
    </div>

    <div class="pnb-tabs-section">
      <div class="search-row">
        <input type="text" placeholder="Defilement_RGP..." class="search-input" />
        <span class="search-icon">🔍</span>
        <input type="text" placeholder="Defilement" class="search-input" />
        <span class="search-icon">🔍</span>
        <input type="text" placeholder="LigneAnalytic" class="search-input" />
        <span class="search-icon">🔍</span>
      </div>
      <div class="tabs-container">
        <div class="tabs">
          <button class="tab active">Valeurs</button>
          <button class="tab">Evolution PNB Clients</button>
        </div>
        <span class="export-icon">📤</span>
      </div>
    </div>
  </div>
</template>

<script>
import KPICard from './KPICard.vue';

export default {
  name: 'PNBClientsSection',
  components: {
    KPICard
  },
  data() {
    return {
      year: '2020',
      month: '05',
      pnbData: {
        mois: 530,
        mois1: 527,
        mois1Percent: 0.46,
        annee1: 517,
        cumulAnnee: 2101,
        cumulAnnee1: 2163
      }
    }
  }
}
</script>

<style scoped>
.pnb-section {
  margin-bottom: 30px;
}

.section-title {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 20px;
  color: #333;
}

.kpi-row {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 15px;
  margin-bottom: 20px;
}

.filters-row {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 10px;
}

.filter-group label {
  font-size: 14px;
  color: #666;
}

.filter-input {
  padding: 6px 12px;
  border: 1px solid #CCC;
  border-radius: 4px;
  font-size: 14px;
  width: 100px;
}

.pnb-tabs-section {
  margin-top: 20px;
}

.search-row {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 15px;
}

.search-input {
  padding: 8px 12px;
  border: 1px solid #CCC;
  border-radius: 4px;
  font-size: 13px;
  flex: 1;
  max-width: 250px;
}

.search-icon {
  font-size: 14px;
  color: #666;
}

.tabs-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #DDD;
  margin-bottom: 15px;
}

.tabs {
  display: flex;
  gap: 0;
}

.tab {
  padding: 10px 20px;
  border: none;
  background: transparent;
  border-bottom: 3px solid transparent;
  font-size: 14px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  transition: all 0.2s;
}

.tab.active {
  color: #1A4D3A;
  border-bottom-color: #1A4D3A;
  font-weight: 600;
}

.tab:hover {
  color: #1A4D3A;
}

.export-icon {
  cursor: pointer;
  font-size: 18px;
  color: #666;
}

.evolution-section {
  border: 1px solid #DDD;
  border-radius: 4px;
  padding: 15px;
  margin-top: 20px;
}

.evolution-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.evolution-header h3 {
  font-size: 16px;
  font-weight: 500;
  color: #333;
}

.export-icon {
  cursor: pointer;
  font-size: 18px;
}

.evolution-content {
  min-height: 200px;
  background: #F9F9F9;
  border-radius: 4px;
}
</style>


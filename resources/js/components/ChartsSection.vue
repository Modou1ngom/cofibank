<template>
  <div class="charts-section">
    <div class="charts-grid">
      <div class="chart-container">
        <h3 class="chart-title">Encours Produits</h3>
        <div class="chart-wrapper">
          <EncoursProduitsChart />
        </div>
      </div>
      
      <div class="chart-container">
        <h3 class="chart-title">Prestation Clients</h3>
        <div class="chart-wrapper">
          <PrestationClientsChart />
        </div>
        <p class="chart-note">
          *L'ensemble de données contient des valeurs négatives ou égales à zéro impossibles à afficher dans ce graphique.
        </p>
      </div>
    </div>

    <div class="prestation-tabs">
      <div class="tabs-container">
        <div class="tabs">
          <button class="tab active">Prestation Clients</button>
          <button class="tab">Evolution Prestation Clients</button>
        </div>
        <span class="export-icon">📤</span>
      </div>
    </div>
  </div>
</template>

<script>
import EncoursProduitsChart from './charts/EncoursProduitsChart.vue';
import PrestationClientsChart from './charts/PrestationClientsChart.vue';

export default {
  name: 'ChartsSection',
  components: {
    EncoursProduitsChart,
    PrestationClientsChart
  }
}
</script>

<style scoped>
.charts-section {
  margin-bottom: 30px;
}

.charts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  margin-bottom: 30px;
}

.chart-container {
  background: white;
  border: 1px solid #DDD;
  border-radius: 4px;
  padding: 20px;
}

.chart-title {
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 20px;
  color: #333;
}

.chart-wrapper {
  height: 350px;
  position: relative;
}

.chart-note {
  font-size: 12px;
  color: #999;
  font-style: italic;
  margin-top: 15px;
}

.prestation-tabs {
  margin-top: 20px;
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

</style>


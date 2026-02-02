<template>
  <div class="prepaid-card-sales-section">
    <div class="section-header">
      <h2 class="section-title">
        <span class="title-text">Vente Cartes <span class="title-highlight">prépayées</span></span>
        <span class="card-icon">💳</span>
      </h2>
    </div>

    <div class="content-container">
      <!-- Tableau de données -->
      <div class="table-container">
        <table class="sales-table">
          <thead>
            <tr>
              <th>AGENCE</th>
              <th>Objectif</th>
              <th>Vente CofiCarte M-1</th>
              <th>Vente CofiCarte M</th>
              <th>Variation (nombre)</th>
              <th>Variation (%)</th>
              <th>Atteinte</th>
              <th>Contribution agence</th>
            </tr>
          </thead>
          <tbody>
            <!-- TERRITOIRE -->
            <tr class="category-row">
              <td class="category-cell">
                <button class="expand-btn">+</button>
                <strong>TERRITOIRE</strong>
              </td>
              <td><strong>729</strong></td>
              <td><strong>246</strong></td>
              <td><strong>288</strong></td>
              <td class="positive"><strong>42</strong></td>
              <td class="positive"><strong>17%</strong></td>
              <td class="negative"><strong>40%</strong></td>
              <td class="contribution"><strong>64%</strong></td>
            </tr>

            <!-- TERRITOIRE DAKAR VILLE -->
            <tr class="sub-agency level-2-row">
              <td class="indent-cell level-2">
                <button class="expand-btn">+</button>
                TERRITOIRE DAKAR VILLE
              </td>
              <td><strong>428</strong></td>
              <td><strong>109</strong></td>
              <td><strong>116</strong></td>
              <td class="positive"><strong>7</strong></td>
              <td class="positive"><strong>6%</strong></td>
              <td class="negative"><strong>27%</strong></td>
              <td class="contribution"><strong>26%</strong></td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">POINT E</td>
              <td>95</td>
              <td>22</td>
              <td>32</td>
              <td class="positive">10</td>
              <td class="positive">45%</td>
              <td class="negative">34%</td>
              <td class="contribution">28%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">NIARRY TALLY</td>
              <td>60</td>
              <td>14</td>
              <td>20</td>
              <td class="positive">6</td>
              <td class="positive">43%</td>
              <td class="negative">33%</td>
              <td class="contribution">17%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">CASTOR</td>
              <td>55</td>
              <td>27</td>
              <td>11</td>
              <td class="negative">-16</td>
              <td class="negative">-59%</td>
              <td class="negative">20%</td>
              <td class="contribution">9%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">PARCELLES</td>
              <td>95</td>
              <td>13</td>
              <td>14</td>
              <td class="positive">1</td>
              <td class="positive">8%</td>
              <td class="negative">15%</td>
              <td class="contribution">12%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">LAMINE GUEYE</td>
              <td>60</td>
              <td>11</td>
              <td>12</td>
              <td class="positive">1</td>
              <td class="positive">9%</td>
              <td class="negative">20%</td>
              <td class="contribution">10%</td>
            </tr>

            <!-- TERRITOIRE DAKAR BANLIEUE -->
            <tr class="sub-agency level-2-row">
              <td class="indent-cell level-2">
                <button class="expand-btn">+</button>
                TERRITOIRE DAKAR BANLIEUE
              </td>
              <td><strong>171</strong></td>
              <td><strong>113</strong></td>
              <td>
                <strong>
                  <span class="with-indicator">164</span>
                  <span class="indicator-up">▲</span>
                </strong>
              </td>
              <td class="positive"><strong>51</strong></td>
              <td class="positive"><strong>45%</strong></td>
              <td class="negative"><strong>96%</strong></td>
              <td class="contribution"><strong>36%</strong></td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">PIKINE</td>
              <td>38</td>
              <td>3</td>
              <td>7</td>
              <td class="positive">4</td>
              <td class="positive">133%</td>
              <td class="negative">18%</td>
              <td class="contribution">6%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">RUFISQUE</td>
              <td>40</td>
              <td>34</td>
              <td>54</td>
              <td class="positive">20</td>
              <td class="positive">59%</td>
              <td class="positive">135%</td>
              <td class="contribution">33%</td>
            </tr>

            <!-- TERRITOIRE PROVINCE CENTRE-SUD -->
            <tr class="sub-agency level-2-row">
              <td class="indent-cell level-2">
                <button class="expand-btn">+</button>
                TERRITOIRE PROVINCE CENTRE-SUD
              </td>
              <td><strong>201</strong></td>
              <td><strong>113</strong></td>
              <td>
                <strong>
                  <span class="with-indicator">100</span>
                  <span class="indicator-up">▲</span>
                </strong>
              </td>
              <td class="negative"><strong>-13</strong></td>
              <td class="negative"><strong>-12%</strong></td>
              <td class="negative"><strong>50%</strong></td>
              <td class="contribution"><strong>22%</strong></td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">MBOUR</td>
              <td>78</td>
              <td>83</td>
              <td>91</td>
              <td class="positive">8</td>
              <td class="positive">10%</td>
              <td class="positive">117%</td>
              <td class="contribution">53%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">KAOLACK</td>
              <td>40</td>
              <td>9</td>
              <td>7</td>
              <td class="negative">-2</td>
              <td class="negative">-22%</td>
              <td class="negative">18%</td>
              <td class="contribution">4%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">THIES</td>
              <td>73</td>
              <td>12</td>
              <td>1</td>
              <td class="negative">-11</td>
              <td class="negative">-92%</td>
              <td class="negative">1%</td>
              <td class="contribution">1%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">TOUBA</td>
              <td>60</td>
              <td>9</td>
              <td>2</td>
              <td class="negative">-7</td>
              <td class="negative">-78%</td>
              <td class="negative">3%</td>
              <td class="contribution">1%</td>
            </tr>

            <!-- TERRITOIRE PROVINCE NORD -->
            <tr class="sub-agency level-2-row">
              <td class="indent-cell level-2">
                <button class="expand-btn">+</button>
                TERRITOIRE PROVINCE NORD
              </td>
              <td><strong>100</strong></td>
              <td><strong>24</strong></td>
              <td>
                <strong>
                  <span class="with-indicator">72</span>
                  <span class="indicator-up">▲</span>
                </strong>
              </td>
              <td class="positive"><strong>48</strong></td>
              <td class="positive"><strong>200%</strong></td>
              <td class="negative"><strong>72%</strong></td>
              <td class="contribution"><strong>16%</strong></td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">SAINT-LOUIS</td>
              <td>50</td>
              <td>24</td>
              <td>71</td>
              <td class="positive">47</td>
              <td class="positive">196%</td>
              <td class="positive">142%</td>
              <td class="contribution">41%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">DIOURBEL</td>
              <td>20</td>
              <td>-</td>
              <td>-</td>
              <td>-</td>
              <td>0%</td>
              <td class="negative">0%</td>
              <td class="contribution">0%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">LOUGA</td>
              <td>20</td>
              <td>2</td>
              <td>18</td>
              <td class="positive">16</td>
              <td class="positive">800%</td>
              <td class="negative">90%</td>
              <td class="contribution">11%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">OUROSSOGUI</td>
              <td>18</td>
              <td>-</td>
              <td>1</td>
              <td class="positive">1</td>
              <td>-</td>
              <td class="negative">6%</td>
              <td class="contribution">1%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">TAMBA</td>
              <td>18</td>
              <td>9</td>
              <td>25</td>
              <td class="positive">16</td>
              <td class="positive">178%</td>
              <td class="positive">139%</td>
              <td class="contribution">15%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-3">LINGUERE'LA</td>
              <td>25</td>
              <td>46</td>
              <td>44</td>
              <td class="negative">-2</td>
              <td class="negative">-4%</td>
              <td class="positive">176%</td>
              <td class="contribution">27%</td>
            </tr>

            <!-- POINT SERVICES -->
            <tr class="category-row">
              <td class="category-cell">
                <button class="expand-btn">+</button>
                <strong>POINT SERVICES</strong>
              </td>
              <td><strong>55</strong></td>
              <td><strong>41</strong></td>
              <td><strong>40</strong></td>
              <td class="negative"><strong>-1</strong></td>
              <td class="negative"><strong>-2%</strong></td>
              <td class="negative"><strong>73%</strong></td>
              <td class="contribution"><strong>9%</strong></td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-2">SCAT URBAM</td>
              <td>25</td>
              <td>19</td>
              <td>20</td>
              <td class="positive">1</td>
              <td class="positive">5%</td>
              <td class="negative">80%</td>
              <td class="contribution">17%</td>
            </tr>
            <tr class="sub-agency">
              <td class="indent-cell level-2">MARISTES</td>
              <td>30</td>
              <td>22</td>
              <td>22</td>
              <td>-</td>
              <td>0%</td>
              <td class="negative">73%</td>
              <td class="contribution">13%</td>
            </tr>

            <!-- TOTAL -->
            <tr class="total-row">
              <td class="category-cell">TOTAL</td>
              <td>900</td>
              <td>359</td>
              <td>452</td>
              <td class="positive">93</td>
              <td class="positive">26%</td>
              <td class="negative">50%</td>
              <td>-</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Graphiques -->
      <div class="charts-container">
        <!-- TOP 5 Chart -->
        <div class="chart-wrapper">
          <h3 class="chart-title">TOP 5 VENTE COFI'CARTES</h3>
          <div class="chart-container">
            <canvas ref="top5Chart"></canvas>
          </div>
        </div>

        <!-- FLOP 5 Chart -->
        <div class="chart-wrapper">
          <h3 class="chart-title">FLOP 5 VENTE COFI'CARTES</h3>
          <div class="chart-container">
            <canvas ref="flop5Chart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
);

export default {
  name: 'PrepaidCardSalesSection',
  setup() {
    const top5Chart = ref(null);
    const flop5Chart = ref(null);
    let top5ChartInstance = null;
    let flop5ChartInstance = null;

    const createCharts = async () => {
      await nextTick();
      
      // TOP 5 Chart
      if (top5Chart.value) {
        try {
          const ctx = top5Chart.value.getContext('2d');
          
          if (!ctx) {
            console.error('Impossible d\'obtenir le contexte 2D pour TOP 5');
            return;
          }
          
          if (top5ChartInstance) {
            top5ChartInstance.destroy();
          }

          top5ChartInstance = new ChartJS(ctx, {
          type: 'bar',
          data: {
            labels: ["LINGUERE'LA", 'TAMBA', 'RUFISQUE', 'SAINT-LOUIS', 'MBOUR'],
            datasets: [{
              label: 'Vente Cofi\'cartes',
              data: [1.75, 1.4, 1.35, 1.3, 1.2],
              backgroundColor: '#DC2626',
              borderRadius: 0
            }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 10,
                callbacks: {
                  label: function(context) {
                    return `Vente: ${context.parsed.x}`;
                  }
                }
              }
            },
            scales: {
              x: {
                beginAtZero: true,
                max: 2,
                ticks: {
                  stepSize: 0.5,
                  font: {
                    size: 11
                  },
                  color: '#e5e7eb'
                },
                grid: {
                  color: 'rgba(255, 255, 255, 0.1)',
                  lineWidth: 1
                }
              },
              y: {
                ticks: {
                  font: {
                    size: 11
                  },
                  color: '#e5e7eb'
                },
                grid: {
                  color: 'rgba(255, 255, 255, 0.1)',
                  lineWidth: 1
                }
              }
            }
          }
        });
        } catch (error) {
          console.error('Erreur lors de la création du graphique TOP 5:', error);
        }
      } else {
        console.warn('Référence top5Chart.value est null');
      }

      // FLOP 5 Chart
      if (flop5Chart.value) {
        try {
          const ctx = flop5Chart.value.getContext('2d');
          
          if (!ctx) {
            console.error('Impossible d\'obtenir le contexte 2D pour FLOP 5');
            return;
          }
          
          if (flop5ChartInstance) {
            flop5ChartInstance.destroy();
          }

          flop5ChartInstance = new ChartJS(ctx, {
          type: 'bar',
          data: {
            labels: ['OUROSSOGUI', 'DIOURBEL', 'TOUBA', 'THIES', 'PARCELLES'],
            datasets: [{
              label: 'Vente Cofi\'cartes',
              data: [0.058, 0.005, 0.035, 0.015, 0.145],
              backgroundColor: '#DC2626',
              borderRadius: 0
            }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 10,
                callbacks: {
                  label: function(context) {
                    return `Vente: ${context.parsed.x}`;
                  }
                }
              }
            },
            scales: {
              x: {
                beginAtZero: true,
                max: 0.16,
                ticks: {
                  stepSize: 0.02,
                  font: {
                    size: 11
                  },
                  color: '#e5e7eb'
                },
                grid: {
                  color: 'rgba(255, 255, 255, 0.1)',
                  lineWidth: 1
                }
              },
              y: {
                ticks: {
                  font: {
                    size: 11
                  },
                  color: '#e5e7eb'
                },
                grid: {
                  color: 'rgba(255, 255, 255, 0.1)',
                  lineWidth: 1
                }
              }
            }
          }
        });
        } catch (error) {
          console.error('Erreur lors de la création du graphique FLOP 5:', error);
        }
      } else {
        console.warn('Référence flop5Chart.value est null');
      }
    };

    onMounted(async () => {
      await nextTick();
      // Attendre que le DOM soit complètement rendu
      setTimeout(async () => {
        await nextTick();
        console.log('Initialisation des graphiques...', {
          top5Chart: top5Chart.value,
          flop5Chart: flop5Chart.value
        });
        await createCharts();
      }, 200);
    });

    onBeforeUnmount(() => {
      if (top5ChartInstance) {
        top5ChartInstance.destroy();
      }
      if (flop5ChartInstance) {
        flop5ChartInstance.destroy();
      }
    });

    return {
      top5Chart,
      flop5Chart
    };
  }
}
</script>

<style scoped>
.prepaid-card-sales-section {
  width: 100%;
  padding: 30px;
  background: #FFFFFF;
  min-height: calc(100vh - 100px);
  position: relative;
}

.section-header {
  margin-bottom: 30px;
  position: relative;
  z-index: 1;
}

.section-title {
  font-size: 36px;
  font-weight: 800;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 15px;
  letter-spacing: 1px;
}

.title-text {
  color: #1f2937;
}

.title-highlight {
  color: #DC2626;
  -webkit-text-fill-color: #DC2626;
}

.card-icon {
  font-size: 40px;
  color: #DC2626;
  filter: 
    drop-shadow(0 0 10px rgba(220, 38, 38, 0.8))
    drop-shadow(0 0 20px rgba(220, 38, 38, 0.4));
}

.content-container {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 30px;
  position: relative;
  z-index: 1;
}

.table-container {
  overflow-x: auto;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sales-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  min-width: 1200px;
  font-size: 13px;
}

.sales-table thead {
  background: #DC2626;
  color: white;
}

.sales-table th {
  padding: 12px 8px;
  text-align: center;
  font-weight: 600;
  font-size: 12px;
  border-right: 1px solid #444;
  white-space: nowrap;
}

.sales-table th:first-child {
  text-align: left;
  padding-left: 16px;
}

.sales-table td {
  padding: 10px 8px;
  font-size: 13px;
  text-align: center;
  border-bottom: 1px solid #EEE;
  border-right: 1px solid #F0F0F0;
}

.sales-table td:first-child {
  text-align: left;
  padding-left: 16px;
}

.category-row {
  background: #2A2A2A;
  color: white;
  font-weight: 700;
  cursor: pointer;
}

.category-cell {
  font-size: 16px;
  padding-left: 16px !important;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  color: white;
}

.sub-agency {
  background: white;
}

.sub-agency:hover {
  background: #f5f5f5;
}

.indent-cell {
  padding-left: 48px !important;
  color: #333;
  cursor: pointer;
}

.level-2 {
  padding-left: 32px !important;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: white !important;
}

.level-2-row {
  background: #4A4A4A !important;
  color: white !important;
  font-weight: 600;
  cursor: pointer;
}

.level-2-row:hover {
  background: #5A5A5A !important;
}

.level-2-row td {
  color: white !important;
}

.level-3 {
  padding-left: 48px !important;
  color: #333;
  cursor: pointer;
}

.expand-btn {
  width: 24px;
  height: 24px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border-radius: 3px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 14px;
  flex-shrink: 0;
  transition: background 0.2s;
}

.expand-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.category-cell {
  font-size: 16px;
  padding-left: 16px !important;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  color: white;
}

.positive {
  color: #10B981;
  font-weight: 600;
}

.negative {
  color: #EF4444;
  font-weight: 600;
}

.contribution {
  color: #10B981;
  font-weight: 600;
}

.achievement-high {
  color: #10B981;
  font-weight: 500;
}

.achievement-medium {
  color: #F59E0B;
  font-weight: 500;
}

.achievement-low {
  color: #EF4444;
  font-weight: 500;
}

.total-row {
  background: #F5F5F5;
  font-weight: 600;
}

.total-row td {
  border-top: 2px solid #333;
  border-bottom: 2px solid #333;
  color: #333;
}

.with-indicator {
  display: inline-block;
  margin-right: 5px;
}

.indicator-up {
  color: #10B981;
  font-size: 12px;
  display: inline-block;
}

.charts-container {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.chart-wrapper {
  background: #2a2a2a;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  border: none;
  min-height: 250px;
  display: flex;
  flex-direction: column;
}

.chart-title {
  font-size: 16px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 15px;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.chart-container {
  height: 200px;
  position: relative;
  background: #2a2a2a;
  flex: 1;
  width: 100%;
  min-height: 200px;
  display: block;
}

.chart-container canvas {
  display: block !important;
  width: 100% !important;
  height: 100% !important;
  max-width: 100%;
}

@media (max-width: 1200px) {
  .content-container {
    grid-template-columns: 1fr;
  }
  
  .charts-container {
    flex-direction: row;
  }
  
  .chart-wrapper {
    flex: 1;
  }
}

@media (max-width: 768px) {
  .prepaid-card-sales-section {
    padding: 15px;
  }
  
  .section-title {
    font-size: 24px;
  }
  
  .card-icon {
    font-size: 28px;
  }
  
  .charts-container {
    flex-direction: column;
  }
  
  .sales-table {
    font-size: 11px;
  }
  
  .sales-table th,
  .sales-table td {
    padding: 8px 4px;
  }
}
</style>

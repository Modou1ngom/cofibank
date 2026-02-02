<template>
  <div class="chart-container">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

export default {
  name: 'PrestationClientsChart',
  setup() {
    const chartCanvas = ref(null);
    let chartInstance = null;

    onMounted(() => {
      if (chartCanvas.value) {
        const ctx = chartCanvas.value.getContext('2d');
        
        // Données représentatives (valeurs positives pour l'affichage)
        const dataValues = [25, 20, 18, 15, 12, 10];
        
        chartInstance = new ChartJS(ctx, {
          type: 'doughnut',
          data: {
            labels: ['Services', 'Caisse', 'Virements', 'Agios', 'Monétique', 'Etranger'],
            datasets: [{
              data: dataValues,
              backgroundColor: [
                '#3B82F6',
                '#10B981',
                '#F59E0B',
                '#EF4444',
                '#8B5CF6',
                '#EC4899'
              ],
              borderWidth: 2,
              borderColor: '#FFFFFF'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right',
                labels: {
                  padding: 15,
                  font: {
                    size: 12
                  }
                }
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    const label = context.label || '';
                    const value = context.parsed || 0;
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = ((value / total) * 100).toFixed(1);
                    return `${label}: ${percentage}%`;
                  }
                }
              }
            }
          }
        });
      }
    });

    return {
      chartCanvas
    };
  }
}
</script>

<style scoped>
.chart-container {
  height: 100%;
  width: 100%;
  position: relative;
}
</style>


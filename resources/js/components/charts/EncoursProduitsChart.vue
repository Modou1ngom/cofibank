<template>
  <div class="chart-container">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
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
  name: 'EncoursProduitsChart',
  setup() {
    const chartCanvas = ref(null);
    let chartInstance = null;

    onMounted(() => {
      if (chartCanvas.value) {
        const ctx = chartCanvas.value.getContext('2d');
        
        chartInstance = new ChartJS(ctx, {
          type: 'bar',
          data: {
            labels: ['avr 10', 'mai 10', 'jun 10', 'jul 10', 'aoû 10', 'sep 10', 'oct 10', 'nov 10', 'déc 10', 'jan 11', 'fév 11', 'mar 11', 'avr 11'],
            datasets: [
              {
                label: 'Cautions',
                data: [400000, 450000, 420000, 480000, 460000, 440000, 490000, 470000, 500000, 480000, 490000, 510000, 520000],
                backgroundColor: '#DC2626',
                stack: 'Stack 0'
              },
              {
                label: 'Comptes à vue',
                data: [800000, 850000, 820000, 880000, 860000, 840000, 890000, 870000, 900000, 880000, 890000, 910000, 920000],
                backgroundColor: '#2563EB',
                stack: 'Stack 0'
              },
              {
                label: 'Crédits',
                data: [1200000, 1250000, 1220000, 1280000, 1260000, 1240000, 1290000, 1270000, 1300000, 1280000, 1290000, 1310000, 1320000],
                backgroundColor: '#10B981',
                stack: 'Stack 0'
              }
            ]
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
                mode: 'index',
                intersect: false
              }
            },
            scales: {
              x: {
                stacked: true,
                ticks: {
                  font: {
                    size: 11
                  }
                }
              },
              y: {
                stacked: true,
                min: -2500000,
                max: 2500000,
                ticks: {
                  callback: function(value) {
                    return new Intl.NumberFormat('fr-FR', {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 0
                    }).format(value);
                  },
                  font: {
                    size: 11
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


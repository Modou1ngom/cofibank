<template>
  <div class="chart-container">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script>
import { ref, onMounted, watch } from 'vue';
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
  name: 'NouveauxClientsChart',
  props: {
    data: {
      type: Array,
      required: true
    }
  },
  setup(props) {
    const chartCanvas = ref(null);
    let chartInstance = null;

    const createChart = () => {
      if (chartCanvas.value && props.data) {
        const ctx = chartCanvas.value.getContext('2d');
        
        if (chartInstance) {
          chartInstance.destroy();
        }
        
        chartInstance = new ChartJS(ctx, {
          type: 'bar',
          data: {
            labels: props.data.map(item => item.month),
            datasets: [
              {
                label: 'Nouveaux Clients',
                data: props.data.map(item => item.value),
                backgroundColor: '#1A4D3A',
                borderRadius: 4,
                borderSkipped: false
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return `Nouveaux clients: ${context.parsed.y.toLocaleString('fr-FR')}`;
                  }
                }
              }
            },
            scales: {
              x: {
                grid: {
                  display: false
                },
                ticks: {
                  font: {
                    size: 11
                  }
                }
              },
              y: {
                beginAtZero: true,
                ticks: {
                  callback: function(value) {
                    return value.toLocaleString('fr-FR');
                  },
                  font: {
                    size: 11
                  }
                },
                grid: {
                  color: '#F0F0F0'
                }
              }
            }
          }
        });
      }
    };

    onMounted(() => {
      createChart();
    });

    watch(() => props.data, () => {
      createChart();
    }, { deep: true });

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


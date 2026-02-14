<template>
  <div class="python-evolution-chart">
    <div v-if="loading" class="chart-loading">
      <p>Chargement du graphique...</p>
    </div>
    <div v-else-if="error" class="chart-error">
      <p>{{ error }}</p>
      <button @click="loadChart" class="retry-btn">Réessayer</button>
    </div>
    <div v-show="!loading && !error" ref="chartContainer" class="chart-wrapper"></div>
  </div>
</template>

<script>
import { ref, onMounted, watch, onBeforeUnmount, nextTick } from 'vue';
import axios from 'axios';
import Plotly from 'plotly.js-dist';

export default {
  name: 'PythonEvolutionChart',
  props: {
    labels: {
      type: Array,
      required: true
    },
    current: {
      type: Array,
      required: true
    },
    previous: {
      type: Array,
      default: null
    },
    title: {
      type: String,
      default: 'Évolution temporelle'
    },
    ylabel: {
      type: String,
      default: 'Valeur'
    },
    height: {
      type: Number,
      default: 400
    }
  },
  setup(props) {
    const chartContainer = ref(null);
    const loading = ref(false);
    const error = ref(null);

    const loadChart = async () => {
      loading.value = true;
      error.value = null;

      try {
        // Vérifier que Plotly est disponible
        if (typeof Plotly === 'undefined') {
          error.value = 'Plotly n\'est pas chargé. Vérifiez l\'installation.';
          loading.value = false;
          return;
        }

        // Attendre que le conteneur soit disponible
        await nextTick();
        
        // Vérifier que le conteneur existe
        if (!chartContainer.value) {
          console.warn('Conteneur de graphique non disponible, nouvelle tentative...');
          // Réessayer après un court délai
          await new Promise(resolve => setTimeout(resolve, 100));
          if (!chartContainer.value) {
            error.value = 'Conteneur de graphique non disponible. Veuillez recharger la page.';
            loading.value = false;
            return;
          }
        }

        console.log('Chargement du graphique avec les données:', {
          labels: props.labels,
          current: props.current,
          previous: props.previous
        });

        const response = await axios.post('/api/charts/evolution', {
          labels: props.labels,
          current: props.current,
          previous: props.previous,
          title: props.title,
          ylabel: props.ylabel
        });

        console.log('Réponse reçue:', response.data);

        if (response.data && response.data.chart) {
          // Afficher le graphique Plotly
          if (chartContainer.value) {
            console.log('Création du graphique Plotly...');
            await Plotly.newPlot(
              chartContainer.value,
              response.data.chart.data,
              response.data.chart.layout,
              {
                responsive: true,
                displayModeBar: true,
                displaylogo: false,
                modeBarButtonsToRemove: ['pan2d', 'lasso2d', 'select2d'],
                locale: 'fr'
              }
            );
            console.log('Graphique créé avec succès');
          } else {
            error.value = 'Conteneur de graphique non disponible';
          }
        } else {
          console.error('Format de données invalide:', response.data);
          error.value = 'Format de données invalide reçu du serveur';
        }
      } catch (err) {
        console.error('Erreur lors de la génération du graphique:', err);
        console.error('Détails de l\'erreur:', {
          message: err.message,
          response: err.response?.data,
          status: err.response?.status
        });
        error.value = err.response?.data?.message || err.message || 'Erreur lors de la génération du graphique';
      } finally {
        loading.value = false;
      }
    };

    const resizeChart = () => {
      if (chartContainer.value && chartContainer.value.offsetParent !== null) {
        try {
          Plotly.Plots.resize(chartContainer.value);
        } catch (resizeErr) {
          console.warn('Erreur lors du redimensionnement du graphique:', resizeErr);
        }
      }
    };

    onMounted(async () => {
      // Attendre que le DOM soit complètement rendu
      await nextTick();
      await nextTick(); // Double nextTick pour s'assurer que tout est rendu
      
      // Attendre un peu plus pour s'assurer que le conteneur est disponible
      setTimeout(async () => {
        // Vérifier que le conteneur est disponible
        if (!chartContainer.value) {
          console.error('Le conteneur de graphique n\'est pas disponible après onMounted');
          // Réessayer encore une fois
          await nextTick();
          if (!chartContainer.value) {
            error.value = 'Conteneur de graphique non disponible. Veuillez recharger la page.';
            loading.value = false;
            return;
          }
        }
        loadChart();
      }, 300);
      
      window.addEventListener('resize', resizeChart);
    });

    watch([() => props.labels, () => props.current, () => props.previous, () => props.title], () => {
      loadChart();
    }, { deep: true });

    onBeforeUnmount(() => {
      window.removeEventListener('resize', resizeChart);
      if (chartContainer.value) {
        Plotly.purge(chartContainer.value);
      }
    });

    // Exposer la méthode d'export
    const exportChart = async (format) => {
      if (!chartContainer.value) {
        throw new Error('Graphique non disponible');
      }
      
      const Plotly = (await import('plotly.js-dist')).default;
      
      if (format === 'png') {
        return await Plotly.toImage(chartContainer.value, {
          format: 'png',
          width: 1200,
          height: 600
        });
      }
      
      throw new Error('Format non supporté');
    };

    return {
      chartContainer,
      loading,
      error,
      loadChart,
      exportChart
    };
  }
}
</script>

<style scoped>
.python-evolution-chart {
  width: 100%;
  height: 100%;
  position: relative;
}

.chart-wrapper {
  width: 100%;
  min-height: 400px;
}

.chart-loading,
.chart-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 400px;
  width: 100%;
  background: #f5f5f5;
  border-radius: 4px;
  gap: 10px;
}

.chart-error {
  color: #dc2626;
}

.chart-loading p,
.chart-error p {
  font-size: 14px;
}

.retry-btn {
  padding: 8px 16px;
  background: #1A4D3A;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.retry-btn:hover {
  background: #153d2a;
}
</style>


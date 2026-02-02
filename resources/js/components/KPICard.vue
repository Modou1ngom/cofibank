<template>
  <div class="kpi-card">
    <div class="kpi-label">{{ label }}</div>
    <div class="kpi-value">
      <span v-if="currency">{{ formatNumber(value) }} {{ currency }}</span>
      <span v-else-if="percentage">{{ value.toFixed(2) }}%</span>
      <span v-else>{{ formatNumber(value) }}</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'KPICard',
  props: {
    label: {
      type: String,
      required: true
    },
    value: {
      type: Number,
      required: true
    },
    currency: {
      type: String,
      default: null
    },
    percentage: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    formatNumber(num) {
      if (this.currency === 'USD' || this.currency === 'FCFA') {
        return new Intl.NumberFormat('fr-FR').format(num);
      }
      return new Intl.NumberFormat('fr-FR').format(num);
    }
  }
}
</script>

<style scoped>
.kpi-card {
  background: white;
  border: 2px solid #10B981;
  border-radius: 4px;
  padding: 15px;
  text-align: center;
  transition: all 0.2s;
}

.kpi-card:hover {
  border-color: #059669;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
}

.kpi-label {
  font-size: 12px;
  color: #666;
  margin-bottom: 8px;
  font-weight: 500;
}

.kpi-value {
  font-size: 20px;
  font-weight: bold;
  color: #065F46;
}
</style>


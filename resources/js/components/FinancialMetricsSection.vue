<template>
  <div class="financial-metrics">
    <table class="metrics-table">
      <thead>
        <tr>
          <th></th>
          <th>Mois</th>
          <th>Mois-1</th>
          <th>Mois-1N</th>
          <th>Année-1</th>
          <th>Cumul Année</th>
          <th>Cumul Année-1</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="metric-name"><strong>Marge sur Intérêts</strong></td>
          <td class="positive">520 $</td>
          <td>518 $</td>
          <td>0 $</td>
          <td>508 $</td>
          <td>2 063 $</td>
          <td>2 126 $</td>
        </tr>
        <tr>
          <td class="metric-name"><strong>Commissions</strong></td>
          <td class="positive">10 $</td>
          <td>10 $</td>
          <td>1 $</td>
          <td>9 $</td>
          <td>39 $</td>
          <td>37 $</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'FinancialMetricsSection'
}
</script>

<style scoped>
.financial-metrics {
  margin-bottom: 30px;
}

.metrics-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border: 1px solid #10B981;
  border-radius: 4px;
  overflow: hidden;
}

.metrics-table thead {
  background: #D1FAE5;
}

.metrics-table th {
  padding: 12px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  color: #065F46;
  border-bottom: 2px solid #10B981;
}

.metrics-table td {
  padding: 12px;
  font-size: 14px;
  color: #666;
  border-bottom: 1px solid #EEE;
}

.metrics-table tbody tr:hover {
  background: #ECFDF5;
}

.metric-name {
  font-weight: 500;
  color: #333;
  width: 200px;
}

.positive {
  color: #10B981;
  font-weight: 600;
}
</style>


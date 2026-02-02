<template>
  <div class="validation-section">
    <div class="section-header">
      <h2 class="section-title">Validation des Objectifs</h2>
      <button @click="loadPendingObjectives" class="btn-refresh" :disabled="loading">
        {{ loading ? 'Chargement...' : '🔄 Actualiser' }}
      </button>
    </div>

    <div v-if="loading" class="loading-message">
      Chargement des objectifs en attente...
    </div>

    <div v-else-if="pendingObjectives.length === 0" class="empty-message">
      Aucun objectif en attente de validation.
    </div>

    <div v-else class="objectives-list">
      <div v-for="objective in pendingObjectives" :key="objective.id" class="objective-card">
        <div class="objective-header">
          <div class="objective-info">
            <h3>{{ objective.agency_name || objective.agency_code }}</h3>
            <span class="objective-category">{{ objective.category }}</span>
            <span class="objective-type">{{ objective.type }}</span>
          </div>
          <div class="objective-value">
            <span class="value-label">Valeur</span>
            <span class="value-amount">{{ formatNumber(objective.value) }}</span>
          </div>
        </div>

        <div class="objective-details">
          <div class="detail-item">
            <span class="detail-label">Période:</span>
            <span class="detail-value">
              {{ formatPeriod(objective) }}
            </span>
          </div>
          <div class="detail-item" v-if="objective.description">
            <span class="detail-label">Description:</span>
            <span class="detail-value">{{ objective.description }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Créé par:</span>
            <span class="detail-value">{{ objective.creator?.name || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Date de création:</span>
            <span class="detail-value">{{ formatDate(objective.created_at) }}</span>
          </div>
        </div>

        <div class="objective-actions">
          <button 
            @click="validateObjective(objective.id)" 
            class="btn-validate"
            :disabled="processing"
          >
            ✅ Valider
          </button>
          <button 
            @click="showRejectModal(objective)" 
            class="btn-reject"
            :disabled="processing"
          >
            ❌ Rejeter
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de rejet -->
    <div v-if="showRejectDialog" class="modal-overlay" @click="closeRejectModal">
      <div class="modal-content" @click.stop>
        <h3>Rejeter l'objectif</h3>
        <p>Veuillez indiquer la raison du rejet :</p>
        <textarea 
          v-model="rejectionReason" 
          rows="4" 
          class="rejection-textarea"
          placeholder="Raison du rejet..."
        ></textarea>
        <div class="modal-actions">
          <button @click="closeRejectModal" class="btn-cancel">Annuler</button>
          <button @click="rejectObjective" class="btn-confirm-reject" :disabled="!rejectionReason.trim()">
            Confirmer le rejet
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { ProfileManager } from '../utils/profiles.js';

export default {
  name: 'ValidationSection',
  data() {
    return {
      loading: false,
      processing: false,
      pendingObjectives: [],
      showRejectDialog: false,
      currentObjectiveId: null,
      rejectionReason: ''
    }
  },
  mounted() {
    this.loadPendingObjectives();
  },
  methods: {
    async loadPendingObjectives() {
      try {
        this.loading = true;
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/objectives/pending-validation', {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });

        if (response.data.success) {
          this.pendingObjectives = response.data.data;
        } else {
          console.error('Erreur lors du chargement:', response.data.message);
        }
      } catch (error) {
        console.error('Erreur lors du chargement des objectifs:', error);
        if (error.response?.status === 403) {
          alert('Vous n\'avez pas la permission de voir les objectifs en attente de validation.');
        }
      } finally {
        this.loading = false;
      }
    },
    async validateObjective(id) {
      if (!confirm('Êtes-vous sûr de vouloir valider cet objectif ?')) {
        return;
      }

      try {
        this.processing = true;
        const token = localStorage.getItem('token');
        const response = await axios.post(`/api/objectives/${id}/validate`, {}, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });

        if (response.data.success) {
          alert('Objectif validé avec succès !');
          this.loadPendingObjectives();
        } else {
          alert('Erreur: ' + (response.data.message || 'Erreur lors de la validation'));
        }
      } catch (error) {
        console.error('Erreur lors de la validation:', error);
        alert('Erreur lors de la validation: ' + (error.response?.data?.message || error.message));
      } finally {
        this.processing = false;
      }
    },
    showRejectModal(objective) {
      this.currentObjectiveId = objective.id;
      this.rejectionReason = '';
      this.showRejectDialog = true;
    },
    closeRejectModal() {
      this.showRejectDialog = false;
      this.currentObjectiveId = null;
      this.rejectionReason = '';
    },
    async rejectObjective() {
      if (!this.rejectionReason.trim()) {
        alert('Veuillez indiquer la raison du rejet.');
        return;
      }

      try {
        this.processing = true;
        const token = localStorage.getItem('token');
        const response = await axios.post(`/api/objectives/${this.currentObjectiveId}/reject`, {
          rejection_reason: this.rejectionReason
        }, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });

        if (response.data.success) {
          alert('Objectif rejeté.');
          this.closeRejectModal();
          this.loadPendingObjectives();
        } else {
          alert('Erreur: ' + (response.data.message || 'Erreur lors du rejet'));
        }
      } catch (error) {
        console.error('Erreur lors du rejet:', error);
        alert('Erreur lors du rejet: ' + (error.response?.data?.message || error.message));
      } finally {
        this.processing = false;
      }
    },
    formatNumber(value) {
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(value);
    },
    formatPeriod(objective) {
      if (objective.period === 'month') {
        const months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        return `${months[objective.month - 1]} ${objective.year}`;
      } else if (objective.period === 'quarter') {
        return `T${objective.quarter} ${objective.year}`;
      } else {
        return `Année ${objective.year}`;
      }
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
}
</script>

<style scoped>
.validation-section {
  padding: 20px;
  background: #f5f5f5;
  min-height: 100%;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.section-title {
  font-size: 24px;
  font-weight: bold;
  color: #333;
}

.btn-refresh {
  padding: 8px 16px;
  background: #6E8B7A;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.loading-message, .empty-message {
  text-align: center;
  padding: 40px;
  color: #666;
  font-size: 16px;
}

.objectives-list {
  display: grid;
  gap: 20px;
}

.objective-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.objective-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.objective-info h3 {
  margin: 0 0 8px 0;
  font-size: 18px;
  color: #333;
}

.objective-category, .objective-type {
  display: inline-block;
  padding: 4px 8px;
  margin-right: 8px;
  background: #e3f2fd;
  color: #1976d2;
  border-radius: 4px;
  font-size: 12px;
}

.objective-value {
  text-align: right;
}

.value-label {
  display: block;
  font-size: 12px;
  color: #666;
  margin-bottom: 4px;
}

.value-amount {
  display: block;
  font-size: 24px;
  font-weight: bold;
  color: #6E8B7A;
}

.objective-details {
  margin-bottom: 15px;
}

.detail-item {
  margin-bottom: 8px;
  display: flex;
}

.detail-label {
  font-weight: 600;
  color: #666;
  min-width: 120px;
}

.detail-value {
  color: #333;
}

.objective-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.btn-validate, .btn-reject {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
}

.btn-validate {
  background: #4caf50;
  color: white;
}

.btn-validate:hover:not(:disabled) {
  background: #45a049;
}

.btn-reject {
  background: #f44336;
  color: white;
}

.btn-reject:hover:not(:disabled) {
  background: #da190b;
}

.btn-validate:disabled, .btn-reject:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 30px;
  border-radius: 8px;
  max-width: 500px;
  width: 90%;
}

.modal-content h3 {
  margin-top: 0;
  margin-bottom: 15px;
}

.rejection-textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: inherit;
  font-size: 14px;
  margin-bottom: 20px;
  resize: vertical;
}

.modal-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.btn-cancel, .btn-confirm-reject {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.btn-cancel {
  background: #ccc;
  color: #333;
}

.btn-confirm-reject {
  background: #f44336;
  color: white;
}

.btn-confirm-reject:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>

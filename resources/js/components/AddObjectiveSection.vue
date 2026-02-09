<template>
  <div class="add-objective-section">
    <!-- Message pour MD : ne peut que valider, pas créer d'objectifs -->
    <div v-if="profileCode === 'MD'" class="info-message" style="background-color: #e0f2fe; border-left: 4px solid #0ea5e9; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">
      <h3 style="margin: 0 0 10px 0; color: #0c4a6e;">📋 Directeur Général</h3>
      <p style="margin: 0; color: #666; font-size: 1em;">
        En tant que Directeur Général, vous pouvez uniquement <strong>valider</strong> les objectifs créés par les autres profils.<br>
        Pour valider des objectifs, veuillez utiliser la section "Valider" dans le menu.
      </p>
    </div>

    <div v-else>
      <div class="section-header">
        <h2 class="section-title">Ajouter un Objectif</h2>
        <div v-if="form.type && form.period && form.year" class="period-header">
          <span class="period-title">{{ getObjectiveTypeLabel(form.type) }} - {{ getFormattedPeriod() }}</span>
        </div>
      </div>

      <div class="form-card-container">
      <div class="form-card">
        <form @submit.prevent="submitObjective" class="objective-form">
        <!-- Section Période en haut -->
        <div class="period-selection-section">
          <div class="period-field">
            <label for="period">Période *</label>
            <select 
              id="period" 
              v-model="form.period" 
              required 
              class="period-select"
            >
              <option value="">Sélectionner une période</option>
              <option value="month">Mensuel</option>
              <option value="quarter">Trimestriel</option>
              <option value="year">Annuel</option>
            </select>
          </div>
          
          <div class="period-field" v-if="form.period === 'month'">
            <label for="month">Mois *</label>
            <select 
              id="month" 
              v-model.number="form.month" 
              required 
              class="period-select"
            >
              <option :value="null">Sélectionner un mois</option>
              <option v-for="(month, index) in months" :key="index" :value="index + 1">
                {{ month }}
              </option>
            </select>
          </div>
          
          <div class="period-field" v-if="form.period === 'quarter'">
            <label for="quarter">Trimestre *</label>
            <select 
              id="quarter" 
              v-model.number="form.quarter" 
              required 
              class="period-select"
            >
              <option :value="null">Sélectionner un trimestre</option>
              <option :value="1">T1 (Janvier - Mars)</option>
              <option :value="2">T2 (Avril - Juin)</option>
              <option :value="3">T3 (Juillet - Septembre)</option>
              <option :value="4">T4 (Octobre - Décembre)</option>
            </select>
          </div>
          
          <div class="period-field" v-if="form.period">
            <label for="year">Année *</label>
            <select 
              id="year" 
              v-model.number="form.year" 
              required 
              class="period-select"
            >
              <option :value="null">Sélectionner une année</option>
              <option v-for="year in years" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
          </div>
        </div>

        <div class="form-row" v-if="(shouldShowTypeField && profileCode !== 'CHEF_AGENCE') || (determinedCategory === 'TERRITOIRE' || (isResponsableZone && (determinedCategory === 'POINT SERVICES' || determinedCategory === 'GRAND COMPTE')))">
          <div class="form-group" v-if="shouldShowTypeField && profileCode !== 'CHEF_AGENCE'">
            <label for="type">Type d'objectif *</label>
            <select 
              id="type" 
              v-model="form.type" 
              required 
              class="form-select"
              @change="onTypeChange"
            >
              <option value="">Sélectionner un type</option>
              <option value="CLIENT">Objectif Client</option>
              <option value="PRODUCTION">Production</option>
              <option value="ENCOURS_CREDIT">Objectif Encours Crédit</option>
              <option value="COLLECT">Objectif Collecte</option>
              <option value="DEPOT_GARANTIE">Dépôt de Garantie</option>
              <option value="EPARGNE_SIMPLE">Épargne Simple</option>
              <option value="EPARGNE_PROJET">Épargne Projet</option>
              <!--<option value="PRODUCTION_ENCOURS">Objectif Production Encours</option>-->
            </select>
          </div>

          <!-- Pour Responsable Zone, afficher le sélecteur de territoire -->
          <div class="form-group" v-if="determinedCategory === 'TERRITOIRE' || (isResponsableZone && (determinedCategory === 'POINT SERVICES' || determinedCategory === 'GRAND COMPTE'))">
            <label for="territory">Territoire *</label>
            <select 
              id="territory" 
              v-model="form.territory" 
              required 
              class="form-select"
              @change="onTerritoryChange"
            >
              <option value="">Sélectionner un territoire</option>
              <option value="DAKAR_VILLE">Dakar Ville</option>
              <option value="DAKAR_BANLIEUE">Dakar Banlieue</option>
              <option value="PROVINCE_CENTRE_SUD">Province Centre-Sud</option>
              <option value="PROVINCE_NORD">Province Nord</option>
            </select>
           
          </div>
        </div>

        <!-- Afficher l'objectif DGA du territoire comme référence pour Responsable Zone -->
        <!-- Carte Objectif DGA - Affichée pour tous les types d'objectifs, même sans objectif fixé -->
        <div v-if="isResponsableZone && form.territory && form.type" class="form-row">
          <div class="form-group full-width">
            <div class="dga-objective-card">
              <div class="dga-card-header">
                <div class="dga-card-icon">🎯</div>
                <div class="dga-card-title">
                  <h3>Objectif de la zone</h3>
                  <p class="dga-card-subtitle">{{ getTerritoryName(form.territory) }}</p>
                </div>
              </div>
              <div class="dga-card-body">
                <!-- Pour PRODUCTION, afficher NOMBRES et VOLUME séparément avec répartition -->
                <template v-if="determinedType === 'PRODUCTION' || form.type === 'PRODUCTION'">
                  <div class="dga-values-grid">
                    <div class="dga-value-item">
                      <div class="dga-value-label">NOMBRES TOTAL</div>
                      <div class="dga-value-number" :class="{ 'empty-value': dgaObjectiveNombres === null }">
                        {{ dgaObjectiveNombres !== null ? formatNumber(dgaObjectiveNombres) : '-' }}
                      </div>
                      <div class="dga-value-distributed" v-if="agencyObjectivesSumNombres > 0">
                        <span class="distributed-label">Distribué:</span>
                        <span class="distributed-value">{{ formatNumber(agencyObjectivesSumNombres) }}</span>
                      </div>
                      <div class="dga-value-remaining" :class="{ 'negative': remainingNombres < 0 }">
                        <span class="remaining-label">RESTANT:</span>
                        <span class="remaining-value">
                          {{ dgaObjectiveNombres !== null ? formatNumber(remainingNombres) : '-' }}
                        </span>
                      </div>
                    </div>
                    <div class="dga-value-item">
                      <div class="dga-value-label">VOLUME TOTAL</div>
                      <div class="dga-value-number volume" :class="{ 'empty-value': dgaObjectiveVolume === null }">
                        {{ dgaObjectiveVolume !== null ? formatCurrency(dgaObjectiveVolume) : '-' }}
                      </div>
                      <div class="dga-value-distributed" v-if="agencyObjectivesSumVolume > 0">
                        <span class="distributed-label">Distribué:</span>
                        <span class="distributed-value">{{ formatCurrency(agencyObjectivesSumVolume) }}</span>
                      </div>
                      <div class="dga-value-remaining" :class="{ 'negative': remainingVolume < 0 }">
                        <span class="remaining-label">RESTANT:</span>
                        <span class="remaining-value">
                          {{ dgaObjectiveVolume !== null ? formatCurrency(remainingVolume) : '-' }}
                        </span>
                      </div>
                    </div>
                  </div>
                </template>
                <!-- Pour les autres types (CLIENT, ENCOURS_CREDIT, PRODUCTION_ENCOURS, etc.), afficher la valeur unique avec répartition -->
                <template v-else>
                  <div class="dga-single-value">
                    <div class="dga-value-label">Valeur totale de l'objectif</div>
                    <div class="dga-value-number" :class="{ 'empty-value': dgaObjectiveValue === null }">
                      {{ dgaObjectiveValue !== null ? formatNumber(dgaObjectiveValue) : '-' }}
                    </div>
                    <div class="dga-value-distributed" v-if="agencyObjectivesSumValue > 0">
                      <span class="distributed-label">Distribué:</span>
                      <span class="distributed-value">{{ formatNumber(agencyObjectivesSumValue) }}</span>
                    </div>
                    <div class="dga-value-remaining" :class="{ 'negative': remainingValue < 0 }">
                      <span class="remaining-label">RESTANT:</span>
                      <span class="remaining-value">
                        {{ dgaObjectiveValue !== null ? formatNumber(remainingValue) : '-' }}
                      </span>
                    </div>
                  </div>
                </template>
              </div>
              <div class="dga-card-footer">
                <span class="dga-note-icon">💡</span>
                <span class="dga-note-text" v-if="dgaObjectiveValue !== null || dgaObjectiveNombres !== null || dgaObjectiveVolume !== null">
                  Répartissez cet objectif entre vos agences
                </span>
                <span class="dga-note-text" v-else>
                  En attente de l'objectif DGA pour ce territoire et cette période
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Afficher tous les objectifs de l'agence comme référence pour CHEF_AGENCE -->
        <div v-if="profileCode === 'CHEF_AGENCE' && form.period && form.year" class="form-row">
          <div class="form-group full-width">
            <div class="dga-objective-card">
              <div class="dga-card-header">
                <div class="dga-card-icon">🎯</div>
                <div class="dga-card-title">
                  <h3>Objectifs de l'agence</h3>
                  <p class="dga-card-subtitle">{{ getAgencyName() }}</p>
                </div>
              </div>
              <div class="dga-card-body">
                <!-- Tableau compact de tous les objectifs -->
                <table class="agency-objectives-table">
                  <thead>
                    <tr>
                      <th>Type</th>
                      <th>Total</th>
                      <th>Distribué</th>
                      <th>Restant</th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- CLIENT -->
                    <tr>
                      <td class="objective-type-cell"><strong>Objectif Client</strong></td>
                      <td class="objective-value-cell" :class="{ 'empty-value': agencyObjectives.CLIENT.value === null || agencyObjectives.CLIENT.value === undefined }">
                        {{ (agencyObjectives.CLIENT.value !== null && agencyObjectives.CLIENT.value !== undefined) ? formatNumber(agencyObjectives.CLIENT.value) : '-' }}
                      </td>
                      <td class="objective-value-cell">
                        {{ cafObjectivesSums.CLIENT.value > 0 ? formatNumber(cafObjectivesSums.CLIENT.value) : '0' }}
                      </td>
                      <td class="objective-value-cell" :class="{ 'negative': remainingCAFClientValue < 0 }">
                        {{ (agencyObjectives.CLIENT.value !== null && agencyObjectives.CLIENT.value !== undefined) ? formatNumber(remainingCAFClientValue) : '-' }}
                      </td>
                    </tr>
                    <!-- PRODUCTION NOMBRES -->
                    <tr>
                      <td class="objective-type-cell"><strong>Production - NOMBRES</strong></td>
                      <td class="objective-value-cell" :class="{ 'empty-value': agencyObjectives.PRODUCTION.value_nombres === null }">
                        {{ agencyObjectives.PRODUCTION.value_nombres !== null && agencyObjectives.PRODUCTION.value_nombres !== undefined ? formatNumber(agencyObjectives.PRODUCTION.value_nombres) : '-' }}
                      </td>
                      <td class="objective-value-cell">
                        {{ cafObjectivesSums.PRODUCTION.value_nombres > 0 ? formatNumber(cafObjectivesSums.PRODUCTION.value_nombres) : '0' }}
                      </td>
                      <td class="objective-value-cell" :class="{ 'negative': remainingCAFNombres < 0 }">
                        {{ agencyObjectives.PRODUCTION.value_nombres !== null && agencyObjectives.PRODUCTION.value_nombres !== undefined ? formatNumber(remainingCAFNombres) : '-' }}
                      </td>
                    </tr>
                    <!-- PRODUCTION VOLUME -->
                    <tr>
                      <td class="objective-type-cell"><strong>Production - VOLUME</strong></td>
                      <td class="objective-value-cell" :class="{ 'empty-value': agencyObjectives.PRODUCTION.value_volume === null }">
                        {{ agencyObjectives.PRODUCTION.value_volume !== null && agencyObjectives.PRODUCTION.value_volume !== undefined ? formatCurrency(agencyObjectives.PRODUCTION.value_volume) : '-' }}
                      </td>
                      <td class="objective-value-cell">
                        {{ cafObjectivesSums.PRODUCTION.value_volume > 0 ? formatCurrency(cafObjectivesSums.PRODUCTION.value_volume) : '0' }}
                      </td>
                      <td class="objective-value-cell" :class="{ 'negative': remainingCAFVolume < 0 }">
                        {{ agencyObjectives.PRODUCTION.value_volume !== null && agencyObjectives.PRODUCTION.value_volume !== undefined ? formatCurrency(remainingCAFVolume) : '-' }}
                      </td>
                    </tr>
                    <!-- ENCOURS_CREDIT -->
                    <tr>
                      <td class="objective-type-cell"><strong>Encours Crédit</strong></td>
                      <td class="objective-value-cell" :class="{ 'empty-value': agencyObjectives.ENCOURS_CREDIT.value === null }">
                        {{ agencyObjectives.ENCOURS_CREDIT.value !== null && agencyObjectives.ENCOURS_CREDIT.value !== undefined ? formatNumber(agencyObjectives.ENCOURS_CREDIT.value) : '-' }}
                      </td>
                      <td class="objective-value-cell">
                        {{ cafObjectivesSums.ENCOURS_CREDIT.value > 0 ? formatNumber(cafObjectivesSums.ENCOURS_CREDIT.value) : '0' }}
                      </td>
                      <td class="objective-value-cell" :class="{ 'negative': remainingCAFEncoursValue < 0 }">
                        {{ agencyObjectives.ENCOURS_CREDIT.value !== null && agencyObjectives.ENCOURS_CREDIT.value !== undefined ? formatNumber(remainingCAFEncoursValue) : '-' }}
                      </td>
                    </tr>
                    <!-- COLLECT -->
                    <tr>
                      <td class="objective-type-cell"><strong>Collecte</strong></td>
                      <td class="objective-value-cell" :class="{ 'empty-value': agencyObjectives.COLLECT.value === null }">
                        {{ agencyObjectives.COLLECT.value !== null && agencyObjectives.COLLECT.value !== undefined ? formatCurrency(agencyObjectives.COLLECT.value) : '-' }}
                      </td>
                      <td class="objective-value-cell">
                        {{ cafObjectivesSums.COLLECT.value > 0 ? formatCurrency(cafObjectivesSums.COLLECT.value) : '0' }}
                      </td>
                      <td class="objective-value-cell" :class="{ 'negative': remainingCAFCollectValue < 0 }">
                        {{ agencyObjectives.COLLECT.value !== null && agencyObjectives.COLLECT.value !== undefined ? formatCurrency(remainingCAFCollectValue) : '-' }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="dga-card-footer">
                <span class="dga-note-icon">💡</span>
                <span class="dga-note-text">
                  Répartissez ces objectifs entre vos CAF
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Pour CHEF_AGENCE, afficher un label informatif et le sélecteur de CAF -->
       
        <div class="form-row" v-if="profileCode === 'CHEF_AGENCE'">
          <div class="form-group">
            <label for="caf">CAF (Chargé d'affaires) *</label>
            <select 
              id="caf" 
              v-model="form.caf" 
              required 
              class="form-select"
              :disabled="loadingCAFs"
            >
              <option value=""> 
                <span v-if="loadingCAFs">Chargement des CAF...</span>
                <span v-else>Sélectionner un CAF</span>
              </option>
              <option v-for="caf in cafs" :key="caf.id || caf.email" :value="caf.id || caf.email">
                {{ caf.name }} ({{ caf.email }})
              </option>
            </select>
          </div>
        </div>

        <!-- Tableau des objectifs pour CHEF_AGENCE -->
        <div v-if="profileCode === 'CHEF_AGENCE' && form.caf" class="objectives-table-container">
          <div class="objectives-table-header">
            <h3 class="objectives-table-title">📋 Objectifs à fixer</h3>
            <p class="objectives-table-subtitle">Remplissez les valeurs pour chaque type d'objectif</p>
          </div>
          <table class="objectives-table">
            <thead>
              <tr>
                <th>Type d'objectif</th>
                <th>NOMBRES</th>
                <th>VOLUME (FCFA)</th>
                <th>Valeur</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="objectiveType in cafObjectiveTypes" :key="objectiveType.code">
                <td class="type-cell">
                  <strong>{{ objectiveType.label }}</strong>
                </td>
                <td v-if="objectiveType.code === 'PRODUCTION'" class="value-cell">
                  <input 
                    type="number" 
                    v-model.number="cafObjectives[objectiveType.code].value_nombres" 
                    min="0"
                    step="1"
                    class="table-input"
                    placeholder="Nombre"
                  />
                </td>
                <td v-else class="value-cell">
                  <span class="empty-cell">-</span>
                </td>
                <td v-if="objectiveType.code === 'PRODUCTION'" class="value-cell">
                  <input 
                    type="number" 
                    v-model.number="cafObjectives[objectiveType.code].value_volume" 
                    min="0"
                    step="1"
                    class="table-input"
                    placeholder="Volume"
                  />
                </td>
                <td v-else class="value-cell">
                  <span class="empty-cell">-</span>
                </td>
                <td v-if="objectiveType.code !== 'PRODUCTION'" class="value-cell">
                  <input 
                    type="number" 
                    v-model.number="cafObjectives[objectiveType.code].value" 
                    min="0"
                    :step="objectiveType.code === 'COLLECT' ? '0.01' : '1'"
                    class="table-input"
                    :placeholder="objectiveType.code === 'COLLECT' ? 'Montant (FCFA)' : 'Valeur'"
                  />
                </td>
                <td v-else class="value-cell">
                  <span class="empty-cell">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Champs NOMBRES et VOLUME pour PRODUCTION (pour les autres profils) -->
        <div class="form-row" v-if="(determinedType === 'PRODUCTION' || form.type === 'PRODUCTION') && shouldShowValueFields && profileCode !== 'CHEF_AGENCE'">
          <div class="form-group" v-if="shouldShowAgencyField">
            <label for="agency">Agence / Point de Service *</label>
            <select 
              id="agency" 
              v-model="form.agency" 
              required 
              class="form-select"
              :disabled="loadingAgencies || (isResponsableZone && !form.territory)"
            >
              <option value="">
                <span v-if="loadingAgencies">Chargement...</span>
                <span v-else-if="isResponsableZone && !form.territory">Sélectionnez d'abord un territoire</span>
                <span v-else>Sélectionner une agence</span>
              </option>
              <option v-for="agency in agencies" :key="agency.code || agency.name" :value="agency.code || agency.name">
                {{ agency.name }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="value_nombres">NOMBRES *</label>
            <input 
              id="value_nombres" 
              type="number" 
              v-model.number="form.value_nombres" 
              required 
              min="0"
              step="1"
              class="form-input"
              placeholder="Entrez le nombre d'objectifs"
            />
          </div>
        </div>

        <div class="form-row" v-if="(determinedType === 'PRODUCTION' || form.type === 'PRODUCTION') && shouldShowValueFields && profileCode !== 'CHEF_AGENCE'">
          <div class="form-group">
            <label for="value_volume">VOLUME (FCFA) *</label>
            <input 
              id="value_volume" 
              type="number" 
              v-model.number="form.value_volume" 
              required 
              min="0"
              step="1"
              class="form-input"
              placeholder="Entrez le volume en FCFA"
            />
          </div>
        </div>

        <!-- Champ VALUE unique pour les autres types (non PRODUCTION, non CHEF_AGENCE) -->
        <div class="form-row" v-else-if="shouldShowValueFields && profileCode !== 'CHEF_AGENCE'">
          <div class="form-group" v-if="shouldShowAgencyField">
            <label for="agency">Agence / Point de Service *</label>
            <select 
              id="agency" 
              v-model="form.agency" 
              required 
              class="form-select"
              :disabled="loadingAgencies || (isResponsableZone && !form.territory)"
            >
              <option value="">
                <span v-if="loadingAgencies">Chargement...</span>
                <span v-else-if="isResponsableZone && !form.territory">Sélectionnez d'abord un territoire</span>
                <span v-else>Sélectionner une agence</span>
              </option>
              <option v-for="agency in agencies" :key="agency.code || agency.name" :value="agency.code || agency.name">
                {{ agency.name }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="value">Valeur de l'objectif *</label>
            <input 
              id="value" 
              type="number" 
              v-model.number="form.value" 
              required 
              min="0"
              step="1"
              class="form-input"
              placeholder="Entrez la valeur de l'objectif"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group full-width">
            <label for="description">Description (optionnel)</label>
            <textarea 
              id="description" 
              v-model="form.description" 
              rows="3"
              class="form-textarea"
              placeholder="Ajoutez une description pour cet objectif"
            ></textarea>
          </div>
        </div>

        <div v-if="errorMessage" class="error-message">
          {{ errorMessage }}
        </div>

        <div v-if="successMessage" class="success-message">
          {{ successMessage }}
        </div>

        <div class="form-actions">
          <button type="button" @click="resetForm" class="btn-reset">Réinitialiser</button>
          <button type="submit" :disabled="loading" class="btn-submit">
            {{ loading ? 'Enregistrement...' : 'Enregistrer l\'objectif' }}
          </button>
        </div>
        </form>
      </div>
    </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { ProfileManager } from '../utils/profiles.js';

export default {
  name: 'AddObjectiveSection',
  data() {
    return {
      loading: false,
      loadingAgencies: false,
      errorMessage: '',
      successMessage: '',
      agencies: [],
      cafs: [], // Liste des CAF pour le chef d'agence
      loadingCAFs: false, // État de chargement des CAF
      // Structure pour stocker les objectifs CAF par type
      cafObjectives: {
        CLIENT: { value: null },
        PRODUCTION: { value_nombres: null, value_volume: null },
        ENCOURS_CREDIT: { value: null },
        COLLECT: { value: null },
        DEPOT_GARANTIE: { value: null },
        EPARGNE_SIMPLE: { value: null },
        EPARGNE_PROJET: { value: null }
      },
      dgaObjectiveValue: null, // Valeur de l'objectif DGA pour référence
      dgaObjectiveNombres: null, // NOMBRES de l'objectif DGA pour PRODUCTION
      dgaObjectiveVolume: null, // VOLUME de l'objectif DGA pour PRODUCTION
      agencyObjectivesSumNombres: 0, // Somme des NOMBRES déjà fixés pour les agences
      agencyObjectivesSumVolume: 0, // Somme des VOLUME déjà fixés pour les agences
      agencyObjectivesSumValue: 0, // Somme des valeurs déjà fixées pour les agences (autres types)
      mdFilialeObjectiveValue: null, // Valeur de l'objectif MD (filiale) pour référence
      // Objectifs de l'agence pour CHEF_AGENCE - stockés par type
      agencyObjectives: {
        CLIENT: { value: null },
        PRODUCTION: { value_nombres: null, value_volume: null },
        ENCOURS_CREDIT: { value: null },
        COLLECT: { value: null },
        DEPOT_GARANTIE: { value: null },
        EPARGNE_SIMPLE: { value: null },
        EPARGNE_PROJET: { value: null }
      },
      // Sommes des objectifs CAF par type
      cafObjectivesSums: {
        CLIENT: { value: 0 },
        PRODUCTION: { value_nombres: 0, value_volume: 0 },
        ENCOURS_CREDIT: { value: 0 },
        COLLECT: { value: 0 },
        DEPOT_GARANTIE: { value: 0 },
        EPARGNE_SIMPLE: { value: 0 },
        EPARGNE_PROJET: { value: 0 }
      },
      months: [
        'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
      ],
      years: [],
      form: {
        type: '',
        territory: '',
        agency: '',
        caf: '', // CAF sélectionné pour le chef d'agence
        value: null, // Gardé pour compatibilité
        value_nombres: null,
        value_volume: null,
        period: '',
        month: null,
        quarter: null,
        year: null,
        description: '',
        zone: ''
      }
    }
  },
  computed: {
    profileCode() {
      return ProfileManager.getProfileCode();
    },
    cafObjectiveTypes() {
      return [
        { code: 'CLIENT', label: 'Objectif Client' },
        { code: 'PRODUCTION', label: 'Production' },
        { code: 'ENCOURS_CREDIT', label: 'Objectif Encours Crédit' },
        { code: 'COLLECT', label: 'Objectif Collecte' },
        { code: 'DEPOT_GARANTIE', label: 'Dépôt de Garantie' },
        { code: 'EPARGNE_SIMPLE', label: 'Épargne Simple' },
        { code: 'EPARGNE_PROJET', label: 'Épargne Projet' }
      ];
    },
    isResponsableZone() {
      return this.profileCode === 'RESPONSABLE_ZONE';
    },
    determinedCategory() {
      const profileCode = this.profileCode;
      
      // DGA répartit l'objectif MD (filiale) entre les territoires
      if (profileCode === 'DGA') {
        return 'TERRITOIRE';
      }
      
      // Responsable Zone peut créer pour les agences dans son territoire (POINT SERVICES par défaut)
      if (profileCode === 'RESPONSABLE_ZONE') {
        return 'POINT SERVICES';
      }
      
      // Chef d'Agence peut créer pour ses CAF (POINT SERVICES par défaut)
      if (profileCode === 'CHEF_AGENCE') {
        return 'POINT SERVICES';
      }
      
      // MD crée pour la filiale (objectif global annuel)
      if (profileCode === 'MD') {
        return 'FILIALE';
      }
      
      // Admin peut créer pour toutes les catégories (TERRITOIRE par défaut)
      if (ProfileManager.isAdmin()) {
        return 'TERRITOIRE';
      }
      
      // Par défaut, retourner POINT SERVICES
      return 'POINT SERVICES';
    },
    determinedType() {
      // Pour tous les profils, utiliser le type sélectionné dans le formulaire
      // Ne pas retourner de valeur par défaut pour permettre le chargement conditionnel
      return this.form.type || null;
    },
    shouldShowTypeField() {
      // Afficher le champ type pour tous les profils, y compris CHEF_AGENCE
      // Le CHEF_AGENCE peut maintenant sélectionner le type d'objectif pour ses CAF
      return true;
    },
    canCreateObjectives() {
      return ProfileManager.canCreateZoneObjectives() || 
             ProfileManager.canCreateAgencyObjectives() || 
             ProfileManager.canCreateCAFObjectives() ||
             ProfileManager.isAdmin() ||
             this.profileCode === 'MD';
    },
    shouldShowAgencyField() {
      const profileCode = this.profileCode;
      
      // DGA et CHEF_AGENCE ne voient jamais le champ agence
      if (profileCode === 'DGA' || profileCode === 'CHEF_AGENCE') {
        return false;
      }
      
      // Pour les autres profils, afficher le champ agence selon la catégorie
      if (this.determinedCategory === 'POINT SERVICES' || this.determinedCategory === 'GRAND COMPTE') {
        return true;
      }
      
      // Pour TERRITOIRE, afficher seulement si ce n'est pas le DGA et qu'un territoire est sélectionné
      if (this.determinedCategory === 'TERRITOIRE' && this.form.territory) {
        return profileCode !== 'DGA';
      }
      
      return false;
    },
    shouldShowValueFields() {
      // Le DGA doit toujours voir les champs de valeur, même sans champ agence
      if (this.profileCode === 'DGA') {
        return true;
      }
      // Pour les autres profils, utiliser la même logique que shouldShowAgencyField
      return this.shouldShowAgencyField || this.profileCode === 'CHEF_AGENCE';
    },
    remainingNombres() {
      if (this.dgaObjectiveNombres === null) return 0;
      return (this.dgaObjectiveNombres || 0) - (this.agencyObjectivesSumNombres || 0);
    },
    remainingVolume() {
      if (this.dgaObjectiveVolume === null) return 0;
      return (this.dgaObjectiveVolume || 0) - (this.agencyObjectivesSumVolume || 0);
    },
    remainingValue() {
      if (this.dgaObjectiveValue === null) return 0;
      return (this.dgaObjectiveValue || 0) - (this.agencyObjectivesSumValue || 0);
    },
    remainingCAFNombres() {
      const obj = this.agencyObjectives.PRODUCTION;
      if (obj.value_nombres === null || obj.value_nombres === undefined) return 0;
      const total = parseInt(obj.value_nombres, 10) || 0;
      const distributed = parseInt(this.cafObjectivesSums.PRODUCTION.value_nombres, 10) || 0;
      return total - distributed;
    },
    remainingCAFVolume() {
      const obj = this.agencyObjectives.PRODUCTION;
      if (obj.value_volume === null || obj.value_volume === undefined) return 0;
      const total = parseInt(obj.value_volume, 10) || 0;
      const distributed = parseInt(this.cafObjectivesSums.PRODUCTION.value_volume, 10) || 0;
      return total - distributed;
    },
    remainingCAFClientValue() {
      const obj = this.agencyObjectives.CLIENT;
      if (obj.value === null || obj.value === undefined) return 0;
      const total = parseInt(obj.value, 10) || 0;
      const distributed = parseInt(this.cafObjectivesSums.CLIENT.value, 10) || 0;
      return total - distributed;
    },
    remainingCAFEncoursValue() {
      const obj = this.agencyObjectives.ENCOURS_CREDIT;
      if (obj.value === null || obj.value === undefined) return 0;
      const total = parseInt(obj.value, 10) || 0;
      const distributed = parseInt(this.cafObjectivesSums.ENCOURS_CREDIT.value, 10) || 0;
      return total - distributed;
    },
    remainingCAFCollectValue() {
      const obj = this.agencyObjectives.COLLECT;
      if (obj.value === null || obj.value === undefined) return 0;
      const total = parseFloat(obj.value) || 0;
      const distributed = parseFloat(this.cafObjectivesSums.COLLECT.value) || 0;
      return total - distributed;
    },
    remainingCAFValue() {
      // Pour compatibilité avec l'ancien code, retourner la valeur pour CLIENT
      return this.remainingCAFClientValue;
    }
  },
  mounted() {
    this.generateYears();
    if (!this.canCreateObjectives) {
      this.errorMessage = 'Vous n\'avez pas la permission de créer des objectifs.';
    }
    // Pour CHEF_AGENCE, initialiser le type par défaut à CLIENT et charger les CAF
    if (this.profileCode === 'CHEF_AGENCE') {
      // Type par défaut à CLIENT, mais l'utilisateur peut le changer
      if (!this.form.type) {
        this.form.type = 'CLIENT';
      }
      // Initialiser les valeurs par défaut pour charger l'objectif de l'agence
      if (!this.form.year) {
        this.form.year = new Date().getFullYear();
      }
      if (!this.form.period) {
        this.form.period = 'month';
        this.form.month = new Date().getMonth() + 1;
      }
      this.loadCAFs(); // Charger la liste des CAF
      // Charger tous les objectifs de l'agence après initialisation
      this.$nextTick(() => {
        if (this.form.period && this.form.year) {
          this.loadAgencyObjective();
        }
      });
    }
    // Pour Responsable Zone, charger automatiquement le territoire de l'utilisateur
    if (this.isResponsableZone) {
      this.loadUserTerritory();
    }
  },
  watch: {
    'form.territory'(newVal) {
      if (newVal) {
        if (this.determinedCategory === 'TERRITOIRE') {
          this.loadAgencies();
        }
        // Pour Responsable Zone, charger les agences et l'objectif DGA quand le territoire change
        if (this.isResponsableZone) {
          this.loadAgencies();
          if (this.form.type) {
            this.loadDGAObjective();
            this.loadAgencyObjectivesSum();
          }
        }
        // Pour DGA, charger l'objectif MD (filiale) quand le territoire est sélectionné
        if (this.profileCode === 'DGA' && this.determinedCategory === 'TERRITOIRE') {
        this.loadMDFilialeObjective();
          }
        } else {
        this.dgaObjectiveValue = null;
        this.dgaObjectiveNombres = null;
        this.dgaObjectiveVolume = null;
      if (this.profileCode === 'DGA') {
        this.mdFilialeObjectiveValue = null;
        }
      }
    },
    'form.agency'(newVal) {
      // L'objectif DGA est déjà chargé quand le territoire est sélectionné
      // On peut juste réinitialiser la valeur de l'objectif de l'agence
      if (newVal && this.isResponsableZone) {
        // Réinitialiser les valeurs pour que l'utilisateur entre de nouvelles valeurs
        // qui font partie de la répartition de l'objectif DGA
        this.form.value_nombres = null;
        this.form.value_volume = null;
      }
    },
    'form.type'(newVal) {
      // Recharger l'objectif DGA si le type change
      console.log('Type d\'objectif changé:', newVal, 'Territoire:', this.form.territory, 'Is Responsable Zone:', this.isResponsableZone);
      if (newVal && this.form.territory && this.isResponsableZone) {
        console.log('Chargement de l\'objectif DGA suite au changement de type');
        this.loadDGAObjective();
        this.loadAgencyObjectivesSum();
      }
    },
    'form.period'(newVal) {
      // Recharger l'objectif DGA si la période change
      if (newVal && this.form.territory && this.isResponsableZone && this.form.type) {
        this.loadDGAObjective();
        this.loadAgencyObjectivesSum();
      }
      // Pour CHEF_AGENCE, recharger tous les objectifs de l'agence si la période change
      if (newVal && this.profileCode === 'CHEF_AGENCE' && this.form.year) {
        this.loadAgencyObjective();
      }
    },
    'form.month'(newVal) {
      // Recharger l'objectif DGA si le mois change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
      }
      // Pour CHEF_AGENCE, recharger tous les objectifs de l'agence si le mois change
      if (newVal && this.profileCode === 'CHEF_AGENCE' && this.form.period && this.form.year) {
        this.loadAgencyObjective();
      }
    },
    'form.quarter'(newVal) {
      // Recharger l'objectif DGA si le trimestre change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
      }
      // Pour CHEF_AGENCE, recharger tous les objectifs de l'agence si le trimestre change
      if (newVal && this.profileCode === 'CHEF_AGENCE' && this.form.period && this.form.year) {
        this.loadAgencyObjective();
      }
    },
    'form.year'(newVal) {
      // Pour DGA avec TERRITOIRE, recharger l'objectif MD (filiale) si l'année change
      if (newVal && this.profileCode === 'DGA' && this.determinedCategory === 'TERRITOIRE') {
        this.loadMDFilialeObjective();
      }
      // Pour Responsable Zone, recharger l'objectif DGA si l'année change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
      }
      // Pour CHEF_AGENCE, recharger tous les objectifs de l'agence si l'année change
      if (newVal && this.profileCode === 'CHEF_AGENCE' && this.form.period) {
        this.loadAgencyObjective();
      }
    },
    'form.type'(newVal) {
      // Pour Responsable Zone, recharger l'objectif DGA si le type change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
        this.loadAgencyObjectivesSum();
      }
      // Pour CHEF_AGENCE, pas besoin de recharger car on charge tous les types
    },
    'determinedType'(newVal) {
      // Pour Responsable Zone, recharger l'objectif DGA si le type déterminé change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
        this.loadAgencyObjectivesSum();
      }
      // Pour CHEF_AGENCE, pas besoin de recharger car on charge tous les types
    },
    'form.period'(newVal) {
      // Recharger l'objectif DGA si la période change
      if (newVal && this.form.territory && this.isResponsableZone && this.form.type) {
        this.loadDGAObjective();
        this.loadAgencyObjectivesSum();
      }
      // Pour CHEF_AGENCE, recharger tous les objectifs de l'agence si la période change
      if (newVal && this.profileCode === 'CHEF_AGENCE' && this.form.year) {
        this.loadAgencyObjective();
      }
    },
    'form.month'(newVal) {
      // Recharger l'objectif DGA si le mois change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
      }
      // Pour CHEF_AGENCE, recharger tous les objectifs de l'agence si le mois change
      if (newVal && this.profileCode === 'CHEF_AGENCE' && this.form.period && this.form.year) {
        this.loadAgencyObjective();
      }
    },
    'form.quarter'(newVal) {
      // Recharger l'objectif DGA si le trimestre change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
      }
      // Pour CHEF_AGENCE, recharger tous les objectifs de l'agence si le trimestre change
      if (newVal && this.profileCode === 'CHEF_AGENCE' && this.form.period && this.form.year) {
        this.loadAgencyObjective();
      }
    }
  },
  methods: {
    generateYears() {
      const currentYear = new Date().getFullYear();
      for (let i = currentYear - 2; i <= currentYear + 2; i++) {
        this.years.push(i);
      }
    },
    async loadCAFs() {
      try {
        this.loadingCAFs = true;
        this.cafs = [];
        
        // Charger les utilisateurs avec le profil CAF
        const token = localStorage.getItem('token');
        const params = {
          profile: 'CAF'
        };
        
        // Pour CHEF_AGENCE (Responsable Agence), filtrer par son agence
        if (this.profileCode === 'CHEF_AGENCE') {
          const user = ProfileManager.getCurrentUser();
          if (user && user.agency_id) {
            params.agency_id = user.agency_id;
          } else if (user && user.agency) {
            params.agency_id = user.agency.id || user.agency_id;
          }
        }
        
        const response = await axios.get('/api/users', {
          params: params,
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        if (response.data && Array.isArray(response.data)) {
          this.cafs = response.data;
        } else if (response.data?.data && Array.isArray(response.data.data)) {
          this.cafs = response.data.data;
        } else if (response.data?.users && Array.isArray(response.data.users)) {
          this.cafs = response.data.users;
        } else {
          // Si l'endpoint n'existe pas, créer une liste vide ou utiliser des données de test
          console.warn('⚠️ Endpoint /api/users non disponible, utilisation de données de test');
          // Pour l'instant, on peut utiliser une liste vide ou créer un endpoint
          this.cafs = [];
        }
        
        console.log(`✅ ${this.cafs.length} CAF(s) chargé(s) pour le Responsable Agence`);
      } catch (error) {
        console.error('Erreur lors du chargement des CAF:', error);
        // En cas d'erreur, essayer de charger depuis un autre endpoint ou utiliser des données par défaut
        this.cafs = [];
      } finally {
        this.loadingCAFs = false;
      }
    },
    async loadAgencies() {
      try {
        this.loadingAgencies = true;
        this.agencies = [];
        
        if (this.determinedCategory === 'TERRITOIRE' && this.form.territory) {
          // Mapper les valeurs du territoire
          const territoryMap = {
            'DAKAR_VILLE': 'territoire_dakar_ville',
            'DAKAR_BANLIEUE': 'territoire_dakar_banlieue',
            'PROVINCE_CENTRE_SUD': 'territoire_province_centre_sud',
            'PROVINCE_NORD': 'territoire_province_nord'
          };
          
          const territoryKey = territoryMap[this.form.territory];
          if (!territoryKey) {
            console.warn('Territoire non reconnu:', this.form.territory);
            return;
          }
          
          const response = await axios.get(`/api/oracle/data/clients`, {
            params: {
              period: 'month',
              month: new Date().getMonth() + 1,
              year: new Date().getFullYear()
            }
          });
          
          // Extraire les données depuis la structure hiérarchique
          let data = response.data?.data || response.data;
          const hierarchicalData = data?.hierarchicalData || data;
          
          if (hierarchicalData?.TERRITOIRE?.[territoryKey]?.agencies) {
            const agenciesList = hierarchicalData.TERRITOIRE[territoryKey].agencies;
            const agenciesSet = new Set();
            
            agenciesList.forEach(agency => {
              const name = agency.name || agency.AGENCE || agency.NOM_AGENCE;
              const code = agency.code || agency.CODE_AGENCE || agency.AGENCE || name;
              if (name) {
                agenciesSet.add(JSON.stringify({ code: code, name: name }));
              }
            });
            
            this.agencies = Array.from(agenciesSet).map(item => JSON.parse(item));
            console.log(`✅ ${this.agencies.length} agences chargées pour ${territoryKey}`);
          } else {
            console.warn('⚠️ Aucune agence trouvée pour le territoire:', territoryKey);
            console.log('Structure des données:', hierarchicalData?.TERRITOIRE);
          }
        } else if (this.determinedCategory === 'POINT SERVICES') {
          // Pour Responsable Zone, charger les agences du territoire sélectionné
          if (this.isResponsableZone && this.form.territory) {
            const territoryMap = {
              'DAKAR_VILLE': 'territoire_dakar_ville',
              'DAKAR_BANLIEUE': 'territoire_dakar_banlieue',
              'PROVINCE_CENTRE_SUD': 'territoire_province_centre_sud',
              'PROVINCE_NORD': 'territoire_province_nord'
            };
            
            const territoryKey = territoryMap[this.form.territory];
            if (!territoryKey) {
              console.warn('Territoire non reconnu:', this.form.territory);
              return;
            }
            
            const response = await axios.get(`/api/oracle/data/clients`, {
              params: {
                period: 'month',
                month: new Date().getMonth() + 1,
                year: new Date().getFullYear()
              }
            });
            
            let data = response.data?.data || response.data;
            const hierarchicalData = data?.hierarchicalData || data;
            
            // Charger les agences du territoire sélectionné
            const agenciesSet = new Set();
            
            // 1. Charger les agences directement du territoire
            if (hierarchicalData?.TERRITOIRE?.[territoryKey]?.agencies) {
              const agenciesList = hierarchicalData.TERRITOIRE[territoryKey].agencies;
              
              agenciesList.forEach(agency => {
                const name = agency.name || agency.AGENCE || agency.NOM_AGENCE || agency.NOM;
                const code = agency.code || agency.CODE_AGENCE || agency.CODE || agency.AGENCE || name;
                if (name && name.trim() && name.toUpperCase() !== 'INCONNU' && name.toUpperCase() !== 'UNKNOWN') {
                  agenciesSet.add(JSON.stringify({ code: code, name: name.trim() }));
                }
              });
            }
            
            // 2. Charger aussi les agences des POINT SERVICES dans ce territoire
            if (hierarchicalData?.['POINT SERVICES']) {
              Object.keys(hierarchicalData['POINT SERVICES']).forEach(servicePointKey => {
                const servicePoint = hierarchicalData['POINT SERVICES'][servicePointKey];
                // Vérifier si ce point de service appartient au territoire
                if (servicePoint.agencies && Array.isArray(servicePoint.agencies)) {
                  servicePoint.agencies.forEach(agency => {
                    const name = agency.name || agency.AGENCE || agency.NOM_AGENCE || agency.NOM;
                    const code = agency.code || agency.CODE_AGENCE || agency.CODE || agency.AGENCE || name;
                    if (name && name.trim() && name.toUpperCase() !== 'INCONNU' && name.toUpperCase() !== 'UNKNOWN') {
                      agenciesSet.add(JSON.stringify({ code: code, name: name.trim() }));
                    }
                  });
                }
              });
            }
              
              this.agencies = Array.from(agenciesSet).map(item => JSON.parse(item));
            console.log(`✅ ${this.agencies.length} agences chargées pour ${territoryKey}`, this.agencies);
            
            if (this.agencies.length === 0) {
              console.warn('⚠️ Aucune agence trouvée pour le territoire:', territoryKey);
              console.log('Structure des données TERRITOIRE:', hierarchicalData?.TERRITOIRE);
              console.log('Structure des données POINT SERVICES:', hierarchicalData?.['POINT SERVICES']);
            }
          } else if (this.profileCode === 'CHEF_AGENCE') {
            // Pour CHEF_AGENCE, charger toutes les agences disponibles depuis TERRITOIRE
            const response = await axios.get(`/api/oracle/data/clients`, {
              params: {
                period: 'month',
                month: new Date().getMonth() + 1,
                year: new Date().getFullYear()
              }
            });
            
            // Extraire les données depuis la structure hiérarchique
            let data = response.data?.data || response.data;
            const hierarchicalData = data?.hierarchicalData || data;
            
            // Charger toutes les agences depuis TERRITOIRE
            const agenciesSet = new Set();
            
            // Charger les agences de tous les territoires
            if (hierarchicalData?.TERRITOIRE) {
              Object.keys(hierarchicalData.TERRITOIRE).forEach(territoryKey => {
                const territory = hierarchicalData.TERRITOIRE[territoryKey];
                if (territory.agencies && Array.isArray(territory.agencies)) {
                  territory.agencies.forEach(agency => {
                    const name = agency.name || agency.AGENCE || agency.NOM_AGENCE || agency.NOM;
                    const code = agency.code || agency.CODE_AGENCE || agency.CODE || agency.AGENCE || name;
                    if (name && name.trim() && name.toUpperCase() !== 'INCONNU' && name.toUpperCase() !== 'UNKNOWN') {
                      agenciesSet.add(JSON.stringify({ code: code, name: name.trim() }));
                    }
                  });
                }
                // Aussi charger depuis territory.data si disponible
                if (territory.data && Array.isArray(territory.data)) {
                  territory.data.forEach(agency => {
                    const name = agency.name || agency.AGENCE || agency.NOM_AGENCE || agency.NOM;
                    const code = agency.code || agency.CODE_AGENCE || agency.CODE || agency.AGENCE || name;
                    if (name && name.trim() && name.toUpperCase() !== 'INCONNU' && name.toUpperCase() !== 'UNKNOWN') {
                      agenciesSet.add(JSON.stringify({ code: code, name: name.trim() }));
                    }
                  });
                }
              });
            }
            
            this.agencies = Array.from(agenciesSet).map(item => JSON.parse(item));
            console.log(`✅ ${this.agencies.length} agence(s) chargée(s) pour le chef d'agence`);
            
            if (this.agencies.length === 0) {
              console.warn('⚠️ Aucune agence trouvée');
              console.log('Structure des données TERRITOIRE:', hierarchicalData?.TERRITOIRE);
            }
          } else {
            // Pour les autres profils, charger tous les points de service
            const response = await axios.get(`/api/oracle/data/clients`, {
              params: {
                period: 'month',
                month: new Date().getMonth() + 1,
                year: new Date().getFullYear()
              }
            });
            
            // Extraire les données depuis la structure hiérarchique
            let data = response.data?.data || response.data;
            const hierarchicalData = data?.hierarchicalData || data;
            
            if (hierarchicalData?.['POINT SERVICES']?.service_points?.agencies) {
              const agenciesList = hierarchicalData['POINT SERVICES'].service_points.agencies;
              const agenciesSet = new Set();
              
              agenciesList.forEach(agency => {
                const name = agency.name || agency.AGENCE || agency.NOM_AGENCE;
                const code = agency.code || agency.CODE_AGENCE || agency.AGENCE || name;
                if (name) {
                  agenciesSet.add(JSON.stringify({ code: code, name: name }));
                }
              });
              
              this.agencies = Array.from(agenciesSet).map(item => JSON.parse(item));
              console.log(`✅ ${this.agencies.length} points de service chargés`);
            } else {
              console.warn('⚠️ Aucun point de service trouvé');
              console.log('Structure des données:', hierarchicalData?.['POINT SERVICES']);
            }
          }
        } else if (this.determinedCategory === 'GRAND COMPTE') {
          // Pour Responsable Zone, charger les agences du territoire sélectionné
          if (this.isResponsableZone && this.form.territory) {
            const territoryMap = {
              'DAKAR_VILLE': 'territoire_dakar_ville',
              'DAKAR_BANLIEUE': 'territoire_dakar_banlieue',
              'PROVINCE_CENTRE_SUD': 'territoire_province_centre_sud',
              'PROVINCE_NORD': 'territoire_province_nord'
            };
            
            const territoryKey = territoryMap[this.form.territory];
            if (!territoryKey) {
              console.warn('Territoire non reconnu:', this.form.territory);
              return;
            }
            
            const response = await axios.get(`/api/oracle/data/clients`, {
              params: {
                period: 'month',
                month: new Date().getMonth() + 1,
                year: new Date().getFullYear()
              }
            });
            
            let data = response.data?.data || response.data;
            const hierarchicalData = data?.hierarchicalData || data;
            
            // Charger les agences du territoire sélectionné
            const agenciesSet = new Set();
            
            // 1. Charger les agences directement du territoire
            if (hierarchicalData?.TERRITOIRE?.[territoryKey]?.agencies) {
              const agenciesList = hierarchicalData.TERRITOIRE[territoryKey].agencies;
              
              agenciesList.forEach(agency => {
                const name = agency.name || agency.AGENCE || agency.NOM_AGENCE || agency.NOM;
                const code = agency.code || agency.CODE_AGENCE || agency.CODE || agency.AGENCE || name;
                if (name && name.trim() && name.toUpperCase() !== 'INCONNU' && name.toUpperCase() !== 'UNKNOWN') {
                  agenciesSet.add(JSON.stringify({ code: code, name: name.trim() }));
                }
              });
            }
            
            // 2. Charger aussi les agences des POINT SERVICES dans ce territoire
            if (hierarchicalData?.['POINT SERVICES']) {
              Object.keys(hierarchicalData['POINT SERVICES']).forEach(servicePointKey => {
                const servicePoint = hierarchicalData['POINT SERVICES'][servicePointKey];
                // Vérifier si ce point de service appartient au territoire
                if (servicePoint.agencies && Array.isArray(servicePoint.agencies)) {
                  servicePoint.agencies.forEach(agency => {
                    const name = agency.name || agency.AGENCE || agency.NOM_AGENCE || agency.NOM;
                    const code = agency.code || agency.CODE_AGENCE || agency.CODE || agency.AGENCE || name;
                    if (name && name.trim() && name.toUpperCase() !== 'INCONNU' && name.toUpperCase() !== 'UNKNOWN') {
                      agenciesSet.add(JSON.stringify({ code: code, name: name.trim() }));
                    }
                  });
                }
              });
            }
              
              this.agencies = Array.from(agenciesSet).map(item => JSON.parse(item));
            console.log(`✅ ${this.agencies.length} agences chargées pour ${territoryKey}`, this.agencies);
            
            if (this.agencies.length === 0) {
              console.warn('⚠️ Aucune agence trouvée pour le territoire:', territoryKey);
              console.log('Structure des données TERRITOIRE:', hierarchicalData?.TERRITOIRE);
              console.log('Structure des données POINT SERVICES:', hierarchicalData?.['POINT SERVICES']);
            }
          } else {
            // Pour les autres profils, utiliser une liste statique pour GRAND COMPTE
            const response = await axios.get(`/api/oracle/data/clients`, {
              params: {
                period: 'month',
                month: new Date().getMonth() + 1,
                year: new Date().getFullYear()
              }
            });
            
            let data = response.data?.data || response.data;
            const grandCompte = data?.grandCompte;
            
            if (grandCompte && grandCompte.name) {
              this.agencies = [
                { 
                  code: grandCompte.code || grandCompte.CODE_AGENCE || 'GC001', 
                  name: grandCompte.name || 'GRAND COMPTE' 
                }
              ];
            } else {
              this.agencies = [
                { code: 'GC001', name: 'GRAND COMPTE' }
              ];
            }
          }
        }
      } catch (error) {
        console.error('Erreur lors du chargement des agences:', error);
        this.errorMessage = 'Erreur lors du chargement des agences. Veuillez réessayer.';
        this.agencies = [];
      } finally {
        this.loadingAgencies = false;
      }
    },
    onTypeChange() {
      this.form.territory = '';
      this.form.agency = '';
      this.agencies = [];
      this.dgaObjectiveValue = null;
      this.dgaObjectiveNombres = null;
      this.dgaObjectiveVolume = null;
    },
    onTerritoryChange() {
      // Charger les agences et l'objectif DGA
      if (this.determinedCategory === 'TERRITOIRE') {
        this.loadAgencies();
      } else if (this.isResponsableZone) {
        // Pour Responsable Zone, charger les agences du territoire sélectionné
        this.loadAgencies();
        this.loadDGAObjective();
      }
      // Pour DGA, charger l'objectif MD (filiale) quand le territoire change
      if (this.profileCode === 'DGA' && this.determinedCategory === 'TERRITOIRE') {
        this.loadMDFilialeObjective();
      }
    },
    async submitObjective() {
      this.loading = true;
      this.errorMessage = '';
      this.successMessage = '';

      try {
        // Pour CHEF_AGENCE, traiter tous les objectifs du tableau
        if (this.profileCode === 'CHEF_AGENCE') {
          await this.submitCAFObjectives();
          return;
        }

        // Validation côté client avant l'envoi pour les autres profils
        const objectiveType = this.determinedType;
        if (!objectiveType) {
          this.errorMessage = 'Veuillez sélectionner un type d\'objectif.';
          this.loading = false;
          return;
        }
        // Validation conditionnelle selon le type
        if (objectiveType === 'PRODUCTION') {
          // Pour PRODUCTION, valider NOMBRES et VOLUME
          if (!this.form.value_nombres || this.form.value_nombres < 0) {
            this.errorMessage = 'Veuillez entrer un nombre d\'objectifs valide (nombre entier positif).';
            this.loading = false;
            return;
          }
          if (!this.form.value_volume || this.form.value_volume < 0) {
            this.errorMessage = 'Veuillez entrer un volume valide (nombre entier positif).';
            this.loading = false;
            return;
          }
        } else {
          // Pour les autres types, valider VALUE
          if (!this.form.value || this.form.value < 0) {
            this.errorMessage = 'Veuillez entrer une valeur d\'objectif valide (nombre entier positif).';
            this.loading = false;
            return;
          }
        }
        if (!this.form.period) {
          this.errorMessage = 'Veuillez sélectionner une période.';
          this.loading = false;
          return;
        }
        if (!this.form.year) {
          this.errorMessage = 'Veuillez sélectionner une année.';
          this.loading = false;
          return;
        }
        if (this.form.period === 'month' && !this.form.month) {
          this.errorMessage = 'Veuillez sélectionner un mois.';
          this.loading = false;
          return;
        }
        if (this.form.period === 'quarter' && !this.form.quarter) {
          this.errorMessage = 'Veuillez sélectionner un trimestre.';
          this.loading = false;
          return;
        }

        // Pour DGA avec TERRITOIRE, pas besoin d'agence_code (fixe au niveau du territoire)
        // Pour MD avec FILIALE, pas besoin d'agence_code (fixe au niveau de la filiale)
        const profileCode = this.profileCode;
        const isDGAWithTerritory = profileCode === 'DGA' && this.determinedCategory === 'TERRITOIRE';
        const isMDWithFiliale = profileCode === 'MD' && this.determinedCategory === 'FILIALE';
        const isChefAgence = profileCode === 'CHEF_AGENCE';
        
        let agencyCode, agencyName;
        if (isDGAWithTerritory) {
          // Pour DGA, utiliser le territoire comme identifiant
          agencyCode = this.form.territory || 'TERRITOIRE';
          agencyName = this.getTerritoryName(this.form.territory) || 'Territoire';
        } else if (isMDWithFiliale) {
          // Pour MD avec FILIALE, utiliser 'FILIALE' comme identifiant
          agencyCode = 'FILIALE';
          agencyName = 'Filiale';
        } else if (isChefAgence) {
          // Pour CHEF_AGENCE, utiliser le CAF sélectionné
          if (!this.form.caf) {
            this.errorMessage = 'Veuillez sélectionner un CAF.';
            this.loading = false;
            return;
          }
          const selectedCAF = this.cafs.find(c => (c.id || c.email) === this.form.caf);
          agencyCode = this.form.caf;
          agencyName = selectedCAF ? selectedCAF.name : 'CAF';
        } else {
          // Pour les autres profils, utiliser l'agence sélectionnée
          if (!this.form.agency) {
            this.errorMessage = 'Veuillez sélectionner une agence.';
            this.loading = false;
            return;
          }
          const selectedAgency = this.agencies.find(a => (a.code || a.name) === this.form.agency);
          agencyCode = this.form.agency;
          agencyName = selectedAgency ? selectedAgency.name : this.form.agency;
        }
        
        // Convertir les valeurs en entiers selon le type
        // objectiveType est déjà déclaré plus haut dans la fonction
        const payload = {
          type: objectiveType,
          category: this.determinedCategory,
          territory: this.form.territory || null,
          agency_code: agencyCode,
          agency_name: agencyName,
          period: this.form.period,
          month: this.form.period === 'month' ? parseInt(this.form.month, 10) : null,
          quarter: this.form.period === 'quarter' ? parseInt(this.form.quarter, 10) : null,
          year: parseInt(this.form.year, 10),
          description: this.form.description || null
        };

        // Ajouter les valeurs selon le type
        if (objectiveType === 'PRODUCTION') {
          // PRODUCTION : NOMBRES et VOLUME sont tous les deux requis
          payload.value = parseInt(this.form.value_nombres, 10); // Pour compatibilité
          payload.value_nombres = parseInt(this.form.value_nombres, 10);
          payload.value_volume = parseInt(this.form.value_volume, 10);
          
          // Vérifier que les conversions sont valides
          if (isNaN(payload.value_nombres) || payload.value_nombres < 0) {
            this.errorMessage = 'Le nombre d\'objectifs doit être un nombre entier positif.';
            this.loading = false;
            return;
          }
          if (isNaN(payload.value_volume) || payload.value_volume < 0) {
            this.errorMessage = 'Le volume doit être un nombre entier positif.';
            this.loading = false;
            return;
          }
        } else {
          // Autres types : VALUE uniquement
          payload.value = parseInt(this.form.value, 10);
          payload.value_nombres = null;
          payload.value_volume = null;
          
          // Vérifier que la conversion est valide
          if (isNaN(payload.value) || payload.value < 0) {
            this.errorMessage = 'La valeur de l\'objectif doit être un nombre entier positif.';
            this.loading = false;
            return;
          }
        }
        if (isNaN(payload.year) || payload.year < 2020 || payload.year > 2100) {
          this.errorMessage = 'L\'année doit être entre 2020 et 2100.';
          this.loading = false;
          return;
        }
        if (payload.period === 'month' && (isNaN(payload.month) || payload.month < 1 || payload.month > 12)) {
          this.errorMessage = 'Le mois doit être entre 1 et 12.';
          this.loading = false;
          return;
        }
        if (payload.period === 'quarter' && (isNaN(payload.quarter) || payload.quarter < 1 || payload.quarter > 4)) {
          this.errorMessage = 'Le trimestre doit être entre 1 et 4.';
          this.loading = false;
          return;
        }

        const token = localStorage.getItem('token');
        const response = await axios.post('/api/objectives', payload, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });

        if (response.data.success) {
          const status = response.data.data?.status;
          if (status === 'pending_validation') {
            this.successMessage = 'Objectif créé et en attente de validation !';
          } else {
            this.successMessage = 'Objectif ajouté avec succès !';
          }
          
          // Recharger la somme des objectifs des agences pour mettre à jour l'objectif restant
          if (this.isResponsableZone && this.form.territory && this.form.type) {
            this.loadAgencyObjectivesSum();
          }
          
          setTimeout(() => {
            this.resetForm();
            this.successMessage = '';
          }, 3000);
        }

      } catch (error) {
        console.error('Erreur lors de l\'ajout de l\'objectif:', error);
        // Afficher les erreurs de validation détaillées si disponibles
        if (error.response?.data?.errors) {
          const errors = error.response.data.errors;
          const errorMessages = [];
          for (const field in errors) {
            errorMessages.push(...errors[field]);
          }
          this.errorMessage = errorMessages.join(', ') || error.response?.data?.message || 'Une erreur est survenue lors de l\'ajout de l\'objectif.';
        } else {
          this.errorMessage = error.response?.data?.message || 'Une erreur est survenue lors de l\'ajout de l\'objectif.';
        }
      } finally {
        this.loading = false;
      }
    },
    async submitCAFObjectives() {
      // Validation de base
      if (!this.form.caf) {
        this.errorMessage = 'Veuillez sélectionner un CAF.';
        this.loading = false;
        return;
      }
      if (!this.form.period) {
        this.errorMessage = 'Veuillez sélectionner une période.';
        this.loading = false;
        return;
      }
      if (!this.form.year) {
        this.errorMessage = 'Veuillez sélectionner une année.';
        this.loading = false;
        return;
      }
      if (this.form.period === 'month' && !this.form.month) {
        this.errorMessage = 'Veuillez sélectionner un mois.';
        this.loading = false;
        return;
      }
      if (this.form.period === 'quarter' && !this.form.quarter) {
        this.errorMessage = 'Veuillez sélectionner un trimestre.';
        this.loading = false;
        return;
      }

      const selectedCAF = this.cafs.find(c => (c.id || c.email) === this.form.caf);
      
      // Pour CHEF_AGENCE, utiliser le code de son agence (pas celui du CAF)
      const user = ProfileManager.getCurrentUser();
      let agencyCode, agencyName, category;
      
      if (user && user.agency) {
        // Utiliser le code de l'agence du Responsable Agence (pas l'ID du CAF)
        agencyCode = String(user.agency.code || user.agency_id);
        agencyName = user.agency.name || 'Agence';
        // Pour une agence normale (pas un point de service), utiliser TERRITOIRE comme catégorie
        category = 'TERRITOIRE';
      } else {
        // Fallback si l'agence n'est pas disponible
        agencyCode = String(this.form.caf);
        agencyName = selectedCAF ? selectedCAF.name : 'CAF';
        category = 'POINT SERVICES';
      }
      
      // Vérification de sécurité : s'assurer qu'on a bien un code d'agence
      if (!agencyCode || agencyCode === 'undefined' || agencyCode === 'null') {
        this.errorMessage = 'Impossible de récupérer le code de l\'agence. Veuillez vous reconnecter.';
        this.loading = false;
        return;
      }
      
      // Le CAF sélectionné sera stocké dans agency_name pour référence
      const cafName = selectedCAF ? selectedCAF.name : 'CAF';
      const cafIdentifier = String(this.form.caf); // ID ou email du CAF

      // Préparer les objectifs à soumettre
      const objectivesToSubmit = [];

      // CLIENT
      if (this.cafObjectives.CLIENT.value !== null && this.cafObjectives.CLIENT.value !== '' && this.cafObjectives.CLIENT.value > 0) {
        const clientValue = parseInt(this.cafObjectives.CLIENT.value, 10);
        if (isNaN(clientValue) || clientValue < 0) {
          this.errorMessage = 'Pour Objectif Client, veuillez entrer une valeur valide (nombre entier positif).';
          this.loading = false;
          return;
        }
        const clientObjective = {
          type: 'CLIENT',
          category: category,
          territory: this.form.territory || null,
          agency_code: agencyCode,
          agency_name: `${agencyName} - CAF: ${cafName}`,
          period: this.form.period,
          month: this.form.period === 'month' ? parseInt(this.form.month, 10) : null,
          quarter: this.form.period === 'quarter' ? parseInt(this.form.quarter, 10) : null,
          year: parseInt(this.form.year, 10),
          value: clientValue,
          description: this.form.description || null
        };
        // Ne pas inclure value_nombres et value_volume pour CLIENT
        objectivesToSubmit.push(clientObjective);
      }

      // PRODUCTION
      if ((this.cafObjectives.PRODUCTION.value_nombres !== null && this.cafObjectives.PRODUCTION.value_nombres !== '' && this.cafObjectives.PRODUCTION.value_nombres > 0) ||
          (this.cafObjectives.PRODUCTION.value_volume !== null && this.cafObjectives.PRODUCTION.value_volume !== '' && this.cafObjectives.PRODUCTION.value_volume > 0)) {
        const nombresValue = parseInt(this.cafObjectives.PRODUCTION.value_nombres, 10);
        const volumeValue = parseInt(this.cafObjectives.PRODUCTION.value_volume, 10);
        
        if (!this.cafObjectives.PRODUCTION.value_nombres || this.cafObjectives.PRODUCTION.value_nombres === '' || isNaN(nombresValue) || nombresValue < 0) {
          this.errorMessage = 'Pour Production, le nombre d\'objectifs est requis et doit être un nombre entier positif.';
          this.loading = false;
          return;
        }
        if (!this.cafObjectives.PRODUCTION.value_volume || this.cafObjectives.PRODUCTION.value_volume === '' || isNaN(volumeValue) || volumeValue < 0) {
          this.errorMessage = 'Pour Production, le volume est requis et doit être un nombre entier positif.';
          this.loading = false;
          return;
        }
        const productionObjective = {
          type: 'PRODUCTION',
          category: category,
          territory: this.form.territory || null,
          agency_code: agencyCode,
          agency_name: `${agencyName} - CAF: ${cafName}`,
          period: this.form.period,
          month: this.form.period === 'month' ? parseInt(this.form.month, 10) : null,
          quarter: this.form.period === 'quarter' ? parseInt(this.form.quarter, 10) : null,
          year: parseInt(this.form.year, 10),
          value: nombresValue, // Pour compatibilité
          value_nombres: nombresValue,
          value_volume: volumeValue,
          description: this.form.description || null
        };
        objectivesToSubmit.push(productionObjective);
      }

      // ENCOURS_CREDIT
      if (this.cafObjectives.ENCOURS_CREDIT.value !== null && this.cafObjectives.ENCOURS_CREDIT.value !== '' && this.cafObjectives.ENCOURS_CREDIT.value > 0) {
        const encoursValue = parseInt(this.cafObjectives.ENCOURS_CREDIT.value, 10);
        if (isNaN(encoursValue) || encoursValue < 0) {
          this.errorMessage = 'Pour Objectif Encours Crédit, veuillez entrer une valeur valide (nombre entier positif).';
          this.loading = false;
          return;
        }
        const encoursObjective = {
          type: 'ENCOURS_CREDIT',
          category: category,
          territory: this.form.territory || null,
          agency_code: agencyCode,
          agency_name: `${agencyName} - CAF: ${cafName}`,
          period: this.form.period,
          month: this.form.period === 'month' ? parseInt(this.form.month, 10) : null,
          quarter: this.form.period === 'quarter' ? parseInt(this.form.quarter, 10) : null,
          year: parseInt(this.form.year, 10),
          value: encoursValue,
          description: this.form.description || null
        };
        // Ne pas inclure value_nombres et value_volume pour ENCOURS_CREDIT
        objectivesToSubmit.push(encoursObjective);
      }

      // COLLECT
      if (this.cafObjectives.COLLECT.value !== null && this.cafObjectives.COLLECT.value !== '' && this.cafObjectives.COLLECT.value > 0) {
        const collectValue = parseFloat(this.cafObjectives.COLLECT.value);
        if (isNaN(collectValue) || collectValue < 0) {
          this.errorMessage = 'Pour Objectif Collecte, veuillez entrer une valeur valide (nombre positif).';
          this.loading = false;
          return;
        }
        const collectObjective = {
          type: 'COLLECT',
          category: category,
          territory: this.form.territory || null,
          agency_code: agencyCode,
          agency_name: `${agencyName} - CAF: ${cafName}`,
          period: this.form.period,
          month: this.form.period === 'month' ? parseInt(this.form.month, 10) : null,
          quarter: this.form.period === 'quarter' ? parseInt(this.form.quarter, 10) : null,
          year: parseInt(this.form.year, 10),
          value: collectValue,
          description: this.form.description || null
        };
        // Ne pas inclure value_nombres et value_volume pour COLLECT
        objectivesToSubmit.push(collectObjective);
      }

      if (objectivesToSubmit.length === 0) {
        this.errorMessage = 'Veuillez remplir au moins un objectif.';
        this.loading = false;
        return;
      }

      // Soumettre tous les objectifs
      const token = localStorage.getItem('token');
      let successCount = 0;
      let errorCount = 0;
      const errors = [];

      for (const objective of objectivesToSubmit) {
        try {
          // Nettoyer l'objectif : retirer les champs null pour les types qui ne les utilisent pas
          const cleanObjective = { ...objective };
          if (objective.type !== 'PRODUCTION') {
            // Pour les types non-PRODUCTION, ne pas envoyer value_nombres et value_volume
            delete cleanObjective.value_nombres;
            delete cleanObjective.value_volume;
          }
          
          console.log(`Envoi de l'objectif ${objective.type}:`, cleanObjective);
          
          const response = await axios.post('/api/objectives', cleanObjective, {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json'
            }
          });
          if (response.data.success) {
            successCount++;
          }
        } catch (error) {
          errorCount++;
          console.error(`Erreur lors de l'ajout de l'objectif ${objective.type}:`, error);
          console.error('Données envoyées:', objective);
          console.error('Réponse erreur:', error.response?.data);
          
          // Afficher les erreurs de validation détaillées
          if (error.response?.data?.errors) {
            const validationErrors = error.response.data.errors;
            const errorMessages = [];
            for (const field in validationErrors) {
              errorMessages.push(...validationErrors[field]);
            }
            errors.push(`${objective.type}: ${errorMessages.join(', ')}`);
          } else {
            const errorMsg = error.response?.data?.message || `Erreur pour ${objective.type}`;
            errors.push(errorMsg);
          }
        }
      }

      if (errorCount === 0) {
        this.successMessage = `${successCount} objectif(s) créé(s) avec succès !`;
        setTimeout(() => {
          this.resetForm();
          this.successMessage = '';
        }, 3000);
      } else if (successCount > 0) {
        this.successMessage = `${successCount} objectif(s) créé(s), ${errorCount} erreur(s).`;
        this.errorMessage = errors.join(', ');
      } else {
        this.errorMessage = `Erreur lors de la création des objectifs: ${errors.join(', ')}`;
      }

      this.loading = false;
    },
    getObjectiveTypeLabel(typeCode) {
      const typeMap = {
        'CLIENT': 'Client',
        'PRODUCTION': 'Production',
        'PRODUCTION_VOLUME': 'Production Volume',
        'ENCOURS_CREDIT': 'Encours Crédit',
        'COLLECT': 'Collecte',
        'PRODUCTION_ENCOURS': 'Production Encours'
      };
      return typeMap[typeCode] || typeCode;
    },
    getFormattedPeriod() {
      if (!this.form.period || !this.form.year) {
        return 'Période non définie';
      }
      
      const periodLabels = {
        'month': 'Mensuel',
        'quarter': 'Trimestriel',
        'year': 'Annuel'
      };
      
      const periodLabel = periodLabels[this.form.period] || this.form.period;
      
      if (this.form.period === 'month' && this.form.month) {
        const monthName = this.months[this.form.month - 1] || `Mois ${this.form.month}`;
        return `${periodLabel} (${monthName} ${this.form.year})`;
      } else if (this.form.period === 'quarter' && this.form.quarter) {
        return `${periodLabel} (T${this.form.quarter} ${this.form.year})`;
      } else if (this.form.period === 'year') {
        return `${periodLabel} (${this.form.year})`;
      }
      
      return periodLabel;
    },
    getTerritoryName(territoryCode) {
      const territoryMap = {
        'DAKAR_VILLE': 'Dakar Ville',
        'DAKAR_BANLIEUE': 'Dakar Banlieue',
        'PROVINCE_CENTRE_SUD': 'Province Centre-Sud',
        'PROVINCE_NORD': 'Province Nord'
      };
      return territoryMap[territoryCode] || territoryCode;
    },
    async loadMDFilialeObjective() {
      // Vérifier que tous les champs nécessaires sont remplis
      const objectiveType = this.determinedType;
      if (!objectiveType || !this.form.period || !this.form.year) {
        this.mdFilialeObjectiveValue = null;
        return;
      }

      // Pour les périodes mensuelles et trimestrielles, vérifier que month/quarter est rempli
      if (this.form.period === 'month' && !this.form.month) {
        this.mdFilialeObjectiveValue = null;
        return;
      }
      if (this.form.period === 'quarter' && !this.form.quarter) {
        this.mdFilialeObjectiveValue = null;
        return;
      }

      try {
        const token = localStorage.getItem('token');
        const params = {
          type: this.determinedType,
          period: this.form.period,
          year: this.form.year
        };

        if (this.form.period === 'month') {
          params.month = this.form.month;
        } else if (this.form.period === 'quarter') {
          params.quarter = this.form.quarter;
        }

        const response = await axios.get('/api/objectives/md-filiale-objective', {
          params: params,
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });

        if (response.data.success && response.data.data) {
          // Stocker la valeur de l'objectif MD (filiale) pour l'afficher comme référence
          this.mdFilialeObjectiveValue = response.data.data.value;
        } else {
          this.mdFilialeObjectiveValue = null;
        }
      } catch (error) {
        // Ne pas afficher d'erreur si c'est juste qu'il n'y a pas d'objectif MD
        if (error.response?.status !== 404) {
          console.error('Erreur lors du chargement de l\'objectif MD (filiale):', error);
        }
        this.mdFilialeObjectiveValue = null;
      }
    },
    async loadDGAObjective() {
      // Pour le responsable de zone, on peut charger l'objectif même sans type sélectionné
      // On essaiera de charger pour tous les types possibles ou attendre le type
      const objectiveType = this.determinedType;
      
      // Vérifier que le territoire est rempli (minimum requis)
      if (!this.form.territory) {
        this.dgaObjectiveValue = null;
        this.dgaObjectiveNombres = null;
        this.dgaObjectiveVolume = null;
        return;
      }

      // Si pas de type sélectionné, on ne peut pas charger l'objectif
      // Même pour le responsable de zone, on attend que l'utilisateur sélectionne un type
      if (!objectiveType || !this.form.type) {
        this.dgaObjectiveValue = null;
        this.dgaObjectiveNombres = null;
        this.dgaObjectiveVolume = null;
        return;
      }

      // Utiliser des valeurs par défaut si non définies
      const period = this.form.period || 'month';
      const year = this.form.year || new Date().getFullYear();
      // Utiliser le type déterminé, ou si pas de type et responsable de zone, ne pas charger (attendre la sélection)
      const type = objectiveType;
      
      // Si pas de type et responsable de zone, ne pas charger d'objectif par défaut
      // L'utilisateur doit sélectionner un type pour voir l'objectif DGA correspondant
      if (!type && this.isResponsableZone) {
        this.dgaObjectiveValue = null;
        this.dgaObjectiveNombres = null;
        this.dgaObjectiveVolume = null;
        return;
      }

      // Pour les périodes mensuelles et trimestrielles, vérifier que month/quarter est rempli
      if (period === 'month' && !this.form.month) {
        // Si pas de mois sélectionné, on ne charge pas (ou on pourrait charger le premier mois)
        this.dgaObjectiveValue = null;
        this.dgaObjectiveNombres = null;
        this.dgaObjectiveVolume = null;
        return;
      }
      if (period === 'quarter' && !this.form.quarter) {
        // Si pas de trimestre sélectionné, on ne charge pas (ou on pourrait charger le premier trimestre)
        this.dgaObjectiveValue = null;
        this.dgaObjectiveNombres = null;
        this.dgaObjectiveVolume = null;
        return;
      }

      try {
        const token = localStorage.getItem('token');
        const params = {
          territory: this.form.territory,
          type: type, // Utiliser le type calculé (avec valeur par défaut si nécessaire)
          period: period,
          year: year
        };

        if (period === 'month' && this.form.month) {
          params.month = this.form.month;
        } else if (period === 'quarter' && this.form.quarter) {
          params.quarter = this.form.quarter;
        }

        // Log pour déboguer
        console.log('Chargement objectif DGA avec params:', params);

        const response = await axios.get('/api/objectives/dga-objective', {
          params: params,
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        console.log('Réponse objectif DGA:', response.data);

        if (response.data.success && response.data.data) {
          const objective = response.data.data;
          // Pour PRODUCTION, stocker NOMBRES et VOLUME séparément
          if (type === 'PRODUCTION') {
            // Utiliser value_nombres et value_volume si disponibles, sinon utiliser value pour les deux
            this.dgaObjectiveNombres = objective.value_nombres !== null && objective.value_nombres !== undefined 
              ? objective.value_nombres 
              : (objective.value ?? null);
            this.dgaObjectiveVolume = objective.value_volume !== null && objective.value_volume !== undefined 
              ? objective.value_volume 
              : null;
            this.dgaObjectiveValue = objective.value ?? null;
          } else {
            // Pour les autres types, stocker uniquement la valeur
            // Accepter aussi 0 comme valeur valide
            this.dgaObjectiveValue = objective.value !== null && objective.value !== undefined ? objective.value : null;
            this.dgaObjectiveNombres = null;
            this.dgaObjectiveVolume = null;
          }
          
          console.log('Objectif DGA chargé avec succès:', {
            type: type,
            value: this.dgaObjectiveValue,
            value_nombres: this.dgaObjectiveNombres,
            value_volume: this.dgaObjectiveVolume
          });
          
          // Charger la somme des objectifs des agences pour calculer le restant
          this.loadAgencyObjectivesSum();
        } else {
          this.dgaObjectiveValue = null;
          this.dgaObjectiveNombres = null;
          this.dgaObjectiveVolume = null;
        }
      } catch (error) {
        // Afficher l'erreur pour déboguer
        console.error('Erreur lors du chargement de l\'objectif DGA:', error);
        if (error.response) {
          console.error('Réponse erreur:', error.response.status, error.response.data);
        }
        this.dgaObjectiveValue = null;
        this.dgaObjectiveNombres = null;
        this.dgaObjectiveVolume = null;
      }
    },
    async loadAgencyObjectivesSum() {
      // Charger la somme des objectifs déjà fixés pour les agences du territoire
      if (!this.form.territory || !this.form.type || !this.form.period || !this.form.year) {
        return;
      }

      const period = this.form.period || 'month';
      const year = this.form.year || new Date().getFullYear();
      const type = this.determinedType;

      if (!type) return;

      // Pour les périodes mensuelles et trimestrielles, vérifier que month/quarter est rempli
      if (period === 'month' && !this.form.month) {
        return;
      }
      if (period === 'quarter' && !this.form.quarter) {
        return;
      }

      try {
        const token = localStorage.getItem('token');
        const params = {
          territory: this.form.territory,
          type: type,
          period: period,
          year: year
        };

        if (period === 'month' && this.form.month) {
          params.month = this.form.month;
        } else if (period === 'quarter' && this.form.quarter) {
          params.quarter = this.form.quarter;
        }

        const response = await axios.get('/api/objectives/agency-objectives-sum', {
          params: params,
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });

        if (response.data.success && response.data.data) {
          if (type === 'PRODUCTION') {
            this.agencyObjectivesSumNombres = response.data.data.value_nombres || 0;
            this.agencyObjectivesSumVolume = response.data.data.value_volume || 0;
          } else {
            this.agencyObjectivesSumValue = response.data.data.value || 0;
          }
        }
      } catch (error) {
        console.error('Erreur lors du chargement de la somme des objectifs des agences:', error);
        // En cas d'erreur, réinitialiser les sommes
        this.agencyObjectivesSumNombres = 0;
        this.agencyObjectivesSumVolume = 0;
        this.agencyObjectivesSumValue = 0;
      }
    },
    async loadAgencyObjective() {
      // Charger tous les objectifs fixés pour l'agence du CHEF_AGENCE
      if (!this.form.period || !this.form.year) {
        return;
      }

      // Vérifier que l'utilisateur a une agence
      const user = ProfileManager.getCurrentUser();
      if (!user || !user.agency) {
        console.warn('L\'utilisateur n\'a pas d\'agence assignée');
        this.errorMessage = 'Aucune agence assignée à votre compte. Veuillez contacter l\'administrateur.';
        // Réinitialiser tous les objectifs
        this.agencyObjectives.CLIENT.value = null;
        this.agencyObjectives.PRODUCTION.value_nombres = null;
        this.agencyObjectives.PRODUCTION.value_volume = null;
        this.agencyObjectives.ENCOURS_CREDIT.value = null;
        this.agencyObjectives.COLLECT.value = null;
        this.agencyObjectives.DEPOT_GARANTIE.value = null;
        this.agencyObjectives.EPARGNE_SIMPLE.value = null;
        this.agencyObjectives.EPARGNE_PROJET.value = null;
        return;
      }

      const period = this.form.period || 'month';
      const year = this.form.year || new Date().getFullYear();

      // Pour les périodes mensuelles et trimestrielles, vérifier que month/quarter est rempli
      if (period === 'month' && !this.form.month) {
        return;
      }
      if (period === 'quarter' && !this.form.quarter) {
        return;
      }

      const token = localStorage.getItem('token');
      const baseParams = {
        period: period,
        year: year
      };

      if (period === 'month' && this.form.month) {
        baseParams.month = this.form.month;
      } else if (period === 'quarter' && this.form.quarter) {
        baseParams.quarter = this.form.quarter;
      }

      // Charger tous les types d'objectifs en parallèle
      const objectiveTypes = ['CLIENT', 'PRODUCTION', 'ENCOURS_CREDIT', 'COLLECT', 'DEPOT_GARANTIE', 'EPARGNE_SIMPLE', 'EPARGNE_PROJET'];
      console.log('Chargement des objectifs de l\'agence:', {
        agency: user.agency,
        period: baseParams,
        types: objectiveTypes
      });
      
      const loadPromises = objectiveTypes.map(async (type) => {
        try {
          const params = { ...baseParams, type: type };
          console.log(`Chargement objectif ${type}:`, params);
          const response = await axios.get('/api/objectives/agency-objectives', {
            params: params,
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });

          console.log(`Réponse objectif ${type}:`, response.data);
          if (response.data.success && response.data.data) {
            const data = response.data.data;
            console.log(`Données reçues pour ${type}:`, data);
            if (type === 'PRODUCTION') {
              this.agencyObjectives.PRODUCTION.value_nombres = data.value_nombres !== null && data.value_nombres !== undefined ? parseInt(data.value_nombres, 10) : null;
              this.agencyObjectives.PRODUCTION.value_volume = data.value_volume !== null && data.value_volume !== undefined ? parseInt(data.value_volume, 10) : null;
              console.log(`Objectif PRODUCTION chargé:`, {
                nombres: this.agencyObjectives.PRODUCTION.value_nombres,
                volume: this.agencyObjectives.PRODUCTION.value_volume,
                rawData: {
                  value_nombres: data.value_nombres,
                  value_volume: data.value_volume
                }
              });
            } else {
              // Pour CLIENT, ENCOURS_CREDIT et COLLECT, utiliser value
              // COLLECT utilise parseFloat car c'est une valeur monétaire
              if (type === 'COLLECT') {
                this.agencyObjectives[type].value = data.value !== null && data.value !== undefined ? parseFloat(data.value) : null;
              } else {
                this.agencyObjectives[type].value = data.value !== null && data.value !== undefined ? parseInt(data.value, 10) : null;
              }
              console.log(`Objectif ${type} chargé:`, {
                value: this.agencyObjectives[type].value,
                rawData: data.value,
                type: typeof data.value
              });
            }
          } else {
            // Réinitialiser si pas d'objectif trouvé
            if (type === 'PRODUCTION') {
              this.agencyObjectives.PRODUCTION.value_nombres = null;
              this.agencyObjectives.PRODUCTION.value_volume = null;
            } else {
              this.agencyObjectives[type].value = null;
            }
          }
        } catch (error) {
          console.error(`Erreur lors du chargement de l'objectif ${type} de l'agence:`, error);
          if (error.response) {
            console.error(`Réponse erreur pour ${type}:`, error.response.status, error.response.data);
            
            // Si c'est une erreur 404 avec le message "Agence non trouvée", ne pas continuer
            if (error.response.status === 404 && 
                error.response.data?.message?.includes('Agence non trouvée')) {
              console.error('Agence non trouvée pour cet utilisateur');
              this.errorMessage = error.response.data.message || 'Agence non trouvée. Veuillez contacter l\'administrateur.';
              // Arrêter le chargement des autres types
              return;
            }
          }
          // Si c'est une 404 normale (pas d'objectif fixé), c'est OK
          if (error.response && error.response.status === 404) {
            console.log(`Aucun objectif ${type} trouvé pour cette agence et cette période`);
          }
          // Réinitialiser en cas d'erreur
          if (type === 'PRODUCTION') {
            this.agencyObjectives.PRODUCTION.value_nombres = null;
            this.agencyObjectives.PRODUCTION.value_volume = null;
          } else {
            this.agencyObjectives[type].value = null;
          }
        }
      });

      await Promise.all(loadPromises);
      
      // Debug: afficher les valeurs chargées
      console.log('Valeurs chargées après loadAgencyObjective:', {
        CLIENT: this.agencyObjectives.CLIENT,
        PRODUCTION: this.agencyObjectives.PRODUCTION,
        ENCOURS_CREDIT: this.agencyObjectives.ENCOURS_CREDIT
      });
      
      // Charger la somme des objectifs des CAF pour calculer le restant
      this.loadCAFObjectivesSum();
    },
    async loadCAFObjectivesSum() {
      // Charger la somme des objectifs déjà fixés pour les CAF de l'agence pour tous les types
      if (!this.form.period || !this.form.year) {
        return;
      }

      // Vérifier que l'utilisateur a une agence
      const user = ProfileManager.getCurrentUser();
      if (!user || !user.agency) {
        // Si pas d'agence, réinitialiser les sommes
        this.cafObjectivesSums.CLIENT.value = 0;
        this.cafObjectivesSums.PRODUCTION.value_nombres = 0;
        this.cafObjectivesSums.PRODUCTION.value_volume = 0;
        this.cafObjectivesSums.ENCOURS_CREDIT.value = 0;
        return;
      }

      const period = this.form.period || 'month';
      const year = this.form.year || new Date().getFullYear();

      // Pour les périodes mensuelles et trimestrielles, vérifier que month/quarter est rempli
      if (period === 'month' && !this.form.month) {
        return;
      }
      if (period === 'quarter' && !this.form.quarter) {
        return;
      }

      const token = localStorage.getItem('token');
      const baseParams = {
        period: period,
        year: year
      };

      if (period === 'month' && this.form.month) {
        baseParams.month = this.form.month;
      } else if (period === 'quarter' && this.form.quarter) {
        baseParams.quarter = this.form.quarter;
      }

      // Charger les sommes pour tous les types d'objectifs en parallèle
      const objectiveTypes = ['CLIENT', 'PRODUCTION', 'ENCOURS_CREDIT', 'COLLECT', 'DEPOT_GARANTIE', 'EPARGNE_SIMPLE', 'EPARGNE_PROJET'];
      const loadPromises = objectiveTypes.map(async (type) => {
        try {
          const params = { ...baseParams, type: type };
          const response = await axios.get('/api/objectives/caf-objectives-sum', {
            params: params,
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });

          if (response.data.success && response.data.data) {
            if (type === 'PRODUCTION') {
              this.cafObjectivesSums.PRODUCTION.value_nombres = response.data.data.value_nombres || 0;
              this.cafObjectivesSums.PRODUCTION.value_volume = response.data.data.value_volume || 0;
            } else {
              // COLLECT utilise parseFloat car c'est une valeur monétaire
              if (type === 'COLLECT') {
                this.cafObjectivesSums[type].value = parseFloat(response.data.data.value) || 0;
              } else {
                this.cafObjectivesSums[type].value = parseInt(response.data.data.value, 10) || 0;
              }
            }
          } else {
            // Réinitialiser si pas de données
            if (type === 'PRODUCTION') {
              this.cafObjectivesSums.PRODUCTION.value_nombres = 0;
              this.cafObjectivesSums.PRODUCTION.value_volume = 0;
            } else {
              this.cafObjectivesSums[type].value = 0;
            }
          }
        } catch (error) {
          console.error(`Erreur lors du chargement de la somme des objectifs ${type} des CAF:`, error);
          // Réinitialiser en cas d'erreur
          if (type === 'PRODUCTION') {
            this.cafObjectivesSums.PRODUCTION.value_nombres = 0;
            this.cafObjectivesSums.PRODUCTION.value_volume = 0;
          } else {
            this.cafObjectivesSums[type].value = 0;
          }
        }
      });

      await Promise.all(loadPromises);
      
      // Debug: afficher les sommes chargées
      console.log('Sommes CAF chargées après loadCAFObjectivesSum:', {
        CLIENT: this.cafObjectivesSums.CLIENT,
        PRODUCTION: this.cafObjectivesSums.PRODUCTION,
        ENCOURS_CREDIT: this.cafObjectivesSums.ENCOURS_CREDIT
      });
    },
    getAgencyName() {
      const user = ProfileManager.getCurrentUser();
      if (user && user.agency) {
        return user.agency.name || 'Agence';
      }
      return 'Agence';
    },
    async loadUserTerritory() {
      // Charger le territoire de l'utilisateur connecté
      try {
        const user = ProfileManager.getCurrentUser();
        if (user && user.territory) {
          // Si le territoire a un code, l'utiliser
          const territoryCode = user.territory.code || user.territory_id;
          if (territoryCode) {
            // Mapper le code du territoire à la valeur du formulaire
            // Les codes peuvent être différents, donc on essaie de trouver la correspondance
            const territoryName = user.territory.name || '';
            
            // Mapper les noms de territoires aux codes du formulaire
            const territoryMapping = {
              'Dakar Ville': 'DAKAR_VILLE',
              'Dakar Banlieue': 'DAKAR_BANLIEUE',
              'Province Centre-Sud': 'PROVINCE_CENTRE_SUD',
              'Province Nord': 'PROVINCE_NORD',
              'DAKAR_VILLE': 'DAKAR_VILLE',
              'DAKAR_BANLIEUE': 'DAKAR_BANLIEUE',
              'PROVINCE_CENTRE_SUD': 'PROVINCE_CENTRE_SUD',
              'PROVINCE_NORD': 'PROVINCE_NORD'
            };
            
            // Essayer de trouver la correspondance par nom ou code
            const mappedCode = territoryMapping[territoryName] || 
                              territoryMapping[territoryCode] || 
                              territoryCode;
            
            this.form.territory = mappedCode;
            console.log('Territoire chargé:', mappedCode);
            
            // Initialiser les valeurs par défaut pour charger l'objectif DGA
            if (!this.form.year) {
              this.form.year = new Date().getFullYear();
            }
            if (!this.form.period) {
              // Pour le responsable de zone, utiliser "month" par défaut avec le mois actuel
              // car les objectifs DGA sont souvent créés pour des périodes mensuelles
              this.form.period = 'month';
              this.form.month = new Date().getMonth() + 1; // Mois actuel (1-12)
            }
            
            // Charger les agences et l'objectif DGA après avoir défini le territoire
            this.$nextTick(() => {
              this.loadAgencies();
              // Ne charger l'objectif DGA que si un type est sélectionné
              // Sinon, il se chargera automatiquement quand l'utilisateur sélectionnera un type
              if (this.form.type) {
                console.log('Type déjà sélectionné, chargement de l\'objectif DGA...');
                this.loadDGAObjective();
                this.loadAgencyObjectivesSum();
              } else {
                console.log('Aucun type sélectionné, l\'objectif DGA se chargera quand un type sera sélectionné');
              }
            });
          }
        } else {
          // Si pas de territoire associé, essayer de récupérer depuis l'API
          const token = localStorage.getItem('token');
          const response = await axios.get('/api/user', {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          if (response.data && response.data.territory) {
            const territory = response.data.territory;
            const territoryName = territory.name || '';
            const territoryMapping = {
              'Dakar Ville': 'DAKAR_VILLE',
              'Dakar Banlieue': 'DAKAR_BANLIEUE',
              'Province Centre-Sud': 'PROVINCE_CENTRE_SUD',
              'Province Nord': 'PROVINCE_NORD',
              'DAKAR_VILLE': 'DAKAR_VILLE',
              'DAKAR_BANLIEUE': 'DAKAR_BANLIEUE',
              'PROVINCE_CENTRE_SUD': 'PROVINCE_CENTRE_SUD',
              'PROVINCE_NORD': 'PROVINCE_NORD'
            };
            
            const mappedCode = territoryMapping[territoryName] || territory.code;
            this.form.territory = mappedCode;
            console.log('Territoire chargé depuis API:', mappedCode);
            
            // Initialiser les valeurs par défaut pour charger l'objectif DGA
            if (!this.form.year) {
              this.form.year = new Date().getFullYear();
            }
            if (!this.form.period) {
              // Pour le responsable de zone, utiliser "month" par défaut avec le mois actuel
              // car les objectifs DGA sont souvent créés pour des périodes mensuelles
              this.form.period = 'month';
              this.form.month = new Date().getMonth() + 1; // Mois actuel (1-12)
            }
            
            this.$nextTick(() => {
              this.loadAgencies();
              // Ne charger l'objectif DGA que si un type est sélectionné
              if (this.form.type) {
                console.log('Type déjà sélectionné, chargement de l\'objectif DGA...');
                this.loadDGAObjective();
              } else {
                console.log('Aucun type sélectionné, l\'objectif DGA se chargera quand un type sera sélectionné');
              }
            });
          }
        }
      } catch (error) {
        console.error('Erreur lors du chargement du territoire de l\'utilisateur:', error);
      }
    },
    async findTerritoryForAgency(agencyCodeOrName) {
      // Chercher dans les données Oracle pour trouver le territoire de l'agence
      try {
        const response = await axios.get(`/api/oracle/data/clients`, {
          params: {
            period: 'month',
            month: new Date().getMonth() + 1,
            year: new Date().getFullYear()
          }
        });

        let data = response.data?.data || response.data;
        const hierarchicalData = data?.hierarchicalData || data;

        // Chercher dans les territoires
        const territoryMap = {
          'territoire_dakar_ville': 'DAKAR_VILLE',
          'territoire_dakar_banlieue': 'DAKAR_BANLIEUE',
          'territoire_province_centre_sud': 'PROVINCE_CENTRE_SUD',
          'territoire_province_nord': 'PROVINCE_NORD'
        };

        if (hierarchicalData?.TERRITOIRE) {
          for (const [territoryKey, territory] of Object.entries(hierarchicalData.TERRITOIRE)) {
            if (territory.agencies) {
              for (const agency of territory.agencies) {
                const name = agency.name || agency.AGENCE || agency.NOM_AGENCE;
                const code = agency.code || agency.CODE_AGENCE || agency.AGENCE || name;
                
                if (code === agencyCodeOrName || name === agencyCodeOrName ||
                    code?.toUpperCase() === agencyCodeOrName?.toUpperCase() ||
                    name?.toUpperCase() === agencyCodeOrName?.toUpperCase()) {
                  // Trouvé ! Retourner le territoire correspondant
                  return territoryMap[territoryKey] || null;
                }
              }
            }
          }
        }

        return null;
      } catch (error) {
        console.error('Erreur lors de la recherche du territoire:', error);
        return null;
      }
    },
    formatNumber(value) {
      if (value === null || value === undefined) return '0';
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(value);
    },
    formatCurrency(value) {
      if (value === null || value === undefined) return '0 FCFA';
      return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(value) + ' FCFA';
    },
    resetForm() {
      // Réinitialiser les objectifs CAF
      this.cafObjectives = {
        CLIENT: { value: null },
        PRODUCTION: { value_nombres: null, value_volume: null },
        ENCOURS_CREDIT: { value: null },
        COLLECT: { value: null },
        DEPOT_GARANTIE: { value: null },
        EPARGNE_SIMPLE: { value: null },
        EPARGNE_PROJET: { value: null }
      };
      this.form = {
        type: '',
        territory: '',
        agency: '',
        caf: '',
        value: null,
        value_nombres: null,
        value_volume: null,
        period: '',
        month: null,
        quarter: null,
        year: null,
        description: '',
        zone: ''
      };
      this.agencies = [];
        this.dgaObjectiveValue = null;
        this.dgaObjectiveNombres = null;
        this.dgaObjectiveVolume = null;
        this.errorMessage = '';
        this.successMessage = '';
    }
  }
}
</script>

<style scoped>
.add-objective-section {
  width: 100%;
  height: 100%;
}

.section-header {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 2px solid #e0e0e0;
}

.section-title {
  margin: 0;
  font-size: 24px;
  color: #333;
  font-weight: 600;
}

.period-header {
  display: flex;
  align-items: center;
  margin-top: 5px;
}

.period-title {
  font-size: 18px;
  font-weight: 500;
  color: #555;
}

.form-card-container {
  display: flex;
  justify-content: center;
  width: 100%;
}

.form-card {
  background: white;
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 10px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(0, 0, 0, 0.05);
  width: 100%;
  max-width: 1200px;
  transition: box-shadow 0.3s ease;
}

.form-card:hover {
  box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1), 0 20px 40px rgba(0, 0, 0, 0.08);
}

.objective-form {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.period-selection-section {
  display: flex;
  gap: 20px;
  align-items: flex-end;
  padding: 20px;
  background: #f9fafb;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.period-field {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 150px;
}

.period-field label {
  margin-bottom: 8px;
  font-weight: 600;
  color: #1f2937;
  font-size: 14px;
}

.period-select {
  padding: 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 15px;
  transition: all 0.2s ease;
  font-family: inherit;
  background: #ffffff;
  color: #1f2937;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23374151' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 40px;
}

.period-select:hover {
  border-color: #9ca3af;
}

.period-select:focus {
  outline: none;
  border-color: #1A4D3A;
  box-shadow: 0 0 0 3px rgba(26, 77, 58, 0.1);
}

@media (max-width: 768px) {
  .period-selection-section {
    flex-direction: column;
    align-items: stretch;
  }
  
  .period-field {
    min-width: 100%;
  }
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
    gap: 20px;
  }
}

.form-group {
  display: flex;
  flex-direction: column;
  position: relative;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  margin-bottom: 10px;
  font-weight: 600;
  color: #1f2937;
  font-size: 14px;
  letter-spacing: 0.01em;
}

.form-select,
.form-input,
.form-textarea {
  padding: 14px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 15px;
  transition: all 0.2s ease;
  font-family: inherit;
  background: #ffffff;
  color: #1f2937;
}

.form-select:hover,
.form-input:hover,
.form-textarea:hover {
  border-color: #9ca3af;
}

.form-select:focus,
.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #1A4D3A;
  box-shadow: 0 0 0 4px rgba(26, 77, 58, 0.1);
  background: #ffffff;
}

.form-select:disabled,
.form-input:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
  opacity: 0.6;
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

.error-message {
  padding: 16px 20px;
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #991b1b;
  border-radius: 12px;
  border: 2px solid #fecaca;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 10px;
}

.error-message::before {
  content: "⚠️";
  font-size: 18px;
}

.success-message {
  padding: 16px 20px;
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  color: #065f46;
  border-radius: 12px;
  border: 2px solid #a7f3d0;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 10px;
}

.success-message::before {
  content: "✅";
  font-size: 18px;
}

.info-message {
  margin-top: 10px;
  padding: 12px 16px;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  color: #0369a1;
  border-radius: 10px;
  font-size: 13px;
  border: 2px solid #bae6fd;
  font-weight: 500;
  line-height: 1.5;
}

.form-actions {
  display: flex;
  gap: 16px;
  justify-content: flex-end;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 2px solid #f3f4f6;
}

@media (max-width: 768px) {
  .form-actions {
    flex-direction: column-reverse;
  }
  
  .form-actions button {
    width: 100%;
  }
}

.btn-reset,
.btn-submit {
  padding: 14px 32px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
  letter-spacing: 0.02em;
  position: relative;
  overflow: hidden;
}

.btn-reset {
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  color: #374151;
  border: 2px solid #e5e7eb;
}

.btn-reset:hover {
  background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-submit {
  background: linear-gradient(135deg, #1A4D3A 0%, #0f3d2a 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(26, 77, 58, 0.3);
}

.btn-submit:hover:not(:disabled) {
  background: linear-gradient(135deg, #0f3d2a 0%, #0a2d1f 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(26, 77, 58, 0.4);
}

.btn-submit:active:not(:disabled) {
  transform: translateY(0);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Styles pour la carte d'objectif DGA */
.dga-objective-card {
  background: linear-gradient(135deg, #1A4D3A 0%, #0f3d2a 100%);
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
  color: white;
  margin: 20px 0;
  transition: transform 0.2s, box-shadow 0.2s;
}

.dga-objective-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15), 0 4px 6px rgba(0, 0, 0, 0.1);
}

.dga-card-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.dga-card-icon {
  font-size: 32px;
  background: rgba(255, 255, 255, 0.2);
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
}

.dga-card-title h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: white;
}

.dga-card-subtitle {
  margin: 4px 0 0 0;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.9);
  font-weight: 400;
}

.dga-card-body {
  margin-bottom: 16px;
}

.dga-values-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.dga-value-item {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 16px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.dga-value-label {
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 8px;
}

.dga-value-number {
  font-size: 24px;
  font-weight: 700;
  color: white;
  line-height: 1.2;
}

.dga-value-number.empty-value {
  color: rgba(255, 255, 255, 0.6);
  font-style: italic;
  font-weight: 400;
}

.dga-value-number.volume {
  font-size: 20px;
}

.dga-single-value {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 20px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  text-align: center;
}

.dga-single-value .dga-value-label {
  margin-bottom: 12px;
}

.dga-single-value .dga-value-number {
  font-size: 32px;
}

.dga-card-footer {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  font-size: 13px;
  color: rgba(255, 255, 255, 0.9);
}

.dga-note-icon {
  font-size: 16px;
}

.dga-note-text {
  font-weight: 400;
}

.dga-value-distributed {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  font-size: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.distributed-label {
  color: rgba(255, 255, 255, 0.7);
  font-weight: 500;
}

.distributed-value {
  color: rgba(255, 255, 255, 0.9);
  font-weight: 600;
}

.dga-value-remaining {
  margin-top: 8px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
}

.dga-value-remaining.negative {
  background: rgba(239, 68, 68, 0.3);
  border: 1px solid rgba(239, 68, 68, 0.5);
}

.remaining-label {
  color: rgba(255, 255, 255, 0.9);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.remaining-value {
  color: white;
  font-size: 16px;
  font-weight: 700;
}

.dga-value-remaining.negative .remaining-value {
  color: #fef2f2;
}

/* Styles pour le tableau compact des objectifs de l'agence */
.agency-objectives-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 0;
}

.agency-objectives-table thead {
  background: rgba(255, 255, 255, 0.1);
}

.agency-objectives-table th {
  padding: 8px 12px;
  font-size: 11px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.agency-objectives-table th:first-child {
  text-align: left;
}

.agency-objectives-table th:not(:first-child) {
  text-align: right;
}

.agency-objectives-table tbody tr {
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  transition: background-color 0.2s;
}

.agency-objectives-table tbody tr:hover {
  background: rgba(255, 255, 255, 0.05);
}

.agency-objectives-table tbody tr:last-child {
  border-bottom: none;
}

.objective-type-cell {
  padding: 10px 12px;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.95);
  font-weight: 500;
  text-align: left;
  width: 30%;
}

.objective-value-cell {
  padding: 10px 12px;
  font-size: 13px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.95);
  text-align: right;
  width: 23.33%;
  white-space: nowrap;
}

.objective-value-cell.empty-value {
  color: rgba(255, 255, 255, 0.5);
  font-style: italic;
}

.objective-value-cell.negative {
  color: #fca5a5;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .dga-values-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .dga-card-header {
    flex-direction: column;
    text-align: center;
  }
  
  .dga-value-number {
    font-size: 20px;
  }
  
  .dga-value-number.volume {
    font-size: 18px;
  }
  
  .dga-single-value .dga-value-number {
    font-size: 24px;
  }
  
  .objective-values {
    flex-direction: column;
  }
  
  .production-group {
    flex-direction: column;
  }
}

/* Styles pour le tableau des objectifs CAF */
.objectives-table-container {
  margin: 25px 0;
  background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
  border-radius: 16px;
  padding: 0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(26, 77, 58, 0.1);
  overflow: hidden;
}

.objectives-table-header {
  background: linear-gradient(135deg, #1A4D3A 0%, #2d6a4f 100%);
  padding: 20px 24px;
  border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.objectives-table-title {
  margin: 0 0 6px 0;
  font-size: 1.3em;
  font-weight: 700;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 8px;
}

.objectives-table-subtitle {
  margin: 0;
  font-size: 0.9em;
  color: rgba(255, 255, 255, 0.85);
  font-weight: 400;
}

.objectives-table {
  width: 100%;
  border-collapse: collapse;
  margin: 0;
  background: white;
}

.objectives-table thead {
  background: linear-gradient(135deg, #f0f7f4 0%, #e8f3ed 100%);
}

.objectives-table th {
  padding: 16px 20px;
  text-align: left;
  font-weight: 700;
  color: #1A4D3A;
  border-bottom: 2px solid #1A4D3A;
  font-size: 0.85em;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.objectives-table th:first-child {
  padding-left: 24px;
}

.objectives-table th:last-child {
  padding-right: 24px;
}

.objectives-table th:not(:first-child) {
  text-align: center;
}

.objectives-table td {
  padding: 18px 20px;
  border-bottom: 1px solid #e8ecea;
  vertical-align: middle;
}

.objectives-table tbody tr {
  transition: all 0.2s ease;
}

.objectives-table tbody tr:hover {
  background: linear-gradient(135deg, #f8faf9 0%, #f0f7f4 100%);
  transform: translateX(2px);
  box-shadow: inset 4px 0 0 #1A4D3A;
}

.objectives-table tbody tr:last-child td {
  border-bottom: none;
}

.type-cell {
  font-weight: 600;
  color: #1A4D3A;
  min-width: 200px;
  font-size: 0.95em;
  padding-left: 24px;
}

.value-cell {
  text-align: center;
  padding: 12px 20px;
  min-width: 150px;
}

.table-input {
  width: 100%;
  max-width: 200px;
  padding: 12px 16px;
  border: 2px solid #e0e6e3;
  border-radius: 8px;
  font-size: 0.95em;
  font-weight: 500;
  color: #1A4D3A;
  background: #ffffff;
  transition: all 0.3s ease;
  text-align: center;
  margin: 0 auto;
  display: block;
}

.table-input:hover {
  border-color: #1A4D3A;
  background: #f8faf9;
}

.table-input:focus {
  outline: none;
  border-color: #1A4D3A;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(26, 77, 58, 0.1), 0 2px 8px rgba(26, 77, 58, 0.15);
  transform: translateY(-1px);
}

.table-input::placeholder {
  color: #9ca3af;
  font-weight: 400;
}

.empty-cell {
  color: #cbd5e1;
  font-style: italic;
  text-align: center;
  font-size: 0.9em;
  padding: 12px;
  background: #f8faf9;
  border-radius: 6px;
  display: inline-block;
  min-width: 60px;
}
</style>

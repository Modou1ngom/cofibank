<template>
  <div class="add-objective-section">
    <div class="section-header">
      <h2 class="section-title">Ajouter un Objectif</h2>
    </div>

    <div class="form-container">
      <form @submit.prevent="submitObjective" class="objective-form">
        <div class="form-row">
          <div class="form-group">
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
              <option value="PRODUCTION">Objectif Production</option>
            </select>
          </div>

          <div class="form-group">
            <label for="category">Catégorie *</label>
            <select 
              id="category" 
              v-model="form.category" 
              required 
              class="form-select"
              @change="onCategoryChange"
              :disabled="!canCreateObjectives"
            >
              <option value="">Sélectionner une catégorie</option>
              <option v-for="cat in availableCategories" :key="cat.value" :value="cat.value">
                {{ cat.label }}
              </option>
            </select>
            <div v-if="!canCreateObjectives" class="info-message">
              Vous n'avez pas la permission de créer des objectifs.
            </div>
          </div>
        </div>

        <!-- Pour Responsable Zone, afficher le sélecteur de territoire en premier -->
        <div class="form-row" v-if="form.category === 'TERRITOIRE' || (isResponsableZone && (form.category === 'POINT SERVICES' || form.category === 'GRAND COMPTE'))">
          <div class="form-group">
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
        <div v-if="isResponsableZone && form.territory && dgaObjectiveValue !== null" class="form-row">
          <div class="form-group full-width">
            <div class="dga-objective-info">
              <strong>Objectif DGA pour {{ getTerritoryName(form.territory) }}:</strong>
              <span class="dga-value">{{ formatNumber(dgaObjectiveValue) }}</span>
              <span class="dga-note">(Répartissez cet objectif entre vos agences)</span>
            </div>
          </div>
        </div>

        <div class="form-row" v-if="shouldShowAgencyField">
          <div class="form-group">
            <label for="agency">Agence / Point de Service *</label>
            <select 
              id="agency" 
              v-model="form.agency" 
              required 
              class="form-select"
              :disabled="loadingAgencies"
            >
              <option value="">{{ loadingAgencies ? 'Chargement...' : 'Sélectionner une agence' }}</option>
              <option v-for="agency in agencies" :key="agency.code || agency.name" :value="agency.code || agency.name">
                {{ agency.name }}
              </option>
            </select>
            <div v-if="agencies.length === 0 && !loadingAgencies && shouldShowAgencyField" class="info-message">
              Aucune agence disponible. Veuillez vérifier les paramètres.
            </div>
          </div>
        </div>

        <div class="form-row">
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

          <div class="form-group">
            <label for="period">Période *</label>
            <select 
              id="period" 
              v-model="form.period" 
              required 
              class="form-select"
            >
              <option value="">Sélectionner une période</option>
              <option value="month">Mensuel</option>
              <option value="quarter">Trimestriel</option>
              <option value="year">Annuel</option>
            </select>
          </div>
        </div>

        <div class="form-row" v-if="form.period === 'month'">
          <div class="form-group">
            <label for="month">Mois *</label>
            <select 
              id="month" 
              v-model.number="form.month" 
              required 
              class="form-select"
            >
              <option :value="null">Sélectionner un mois</option>
              <option v-for="(month, index) in months" :key="index" :value="index + 1">
                {{ month }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label for="year">Année *</label>
            <select 
              id="year" 
              v-model.number="form.year" 
              required 
              class="form-select"
            >
              <option :value="null">Sélectionner une année</option>
              <option v-for="year in years" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
          </div>
        </div>

        <div class="form-row" v-if="form.period === 'quarter'">
          <div class="form-group">
            <label for="quarter">Trimestre *</label>
            <select 
              id="quarter" 
              v-model.number="form.quarter" 
              required 
              class="form-select"
            >
              <option :value="null">Sélectionner un trimestre</option>
              <option :value="1">T1 (Janvier - Mars)</option>
              <option :value="2">T2 (Avril - Juin)</option>
              <option :value="3">T3 (Juillet - Septembre)</option>
              <option :value="4">T4 (Octobre - Décembre)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="year">Année *</label>
            <select 
              id="year" 
              v-model.number="form.year" 
              required 
              class="form-select"
            >
              <option :value="null">Sélectionner une année</option>
              <option v-for="year in years" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
          </div>
        </div>

        <div class="form-row" v-if="form.period === 'year'">
          <div class="form-group">
            <label for="year">Année *</label>
            <select 
              id="year" 
              v-model.number="form.year" 
              required 
              class="form-select"
            >
              <option :value="null">Sélectionner une année</option>
              <option v-for="year in years" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
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
      dgaObjectiveValue: null, // Valeur de l'objectif DGA pour référence
      mdFilialeObjectiveValue: null, // Valeur de l'objectif MD (filiale) pour référence
      months: [
        'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
      ],
      years: [],
      form: {
        type: '',
        category: '',
        territory: '',
        agency: '',
        value: null,
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
    isResponsableZone() {
      return this.profileCode === 'RESPONSABLE_ZONE';
    },
    availableCategories() {
      const profileCode = this.profileCode;
      
      // DGA répartit l'objectif MD (filiale) entre les territoires
      if (profileCode === 'DGA') {
        return [
          { value: 'TERRITOIRE', label: 'Territoire' }
        ];
      }
      
      // Responsable Zone peut créer pour les agences dans son territoire (POINT SERVICES et GRAND COMPTE)
      if (profileCode === 'RESPONSABLE_ZONE') {
        return [
          { value: 'POINT SERVICES', label: 'Point Services' },
          { value: 'GRAND COMPTE', label: 'Grand Compte' }
        ];
      }
      
      // Chef d'Agence peut créer pour ses CAF (Chargés d'affaires)
      // Pour l'instant, on utilise POINT SERVICES ou GRAND COMPTE selon l'agence
      // TODO: Ajouter une catégorie CAF si nécessaire
      if (profileCode === 'CHEF_AGENCE') {
        return [
          { value: 'POINT SERVICES', label: 'Point Services' },
          { value: 'GRAND COMPTE', label: 'Grand Compte' }
        ];
      }
      
      // MD crée pour la filiale (objectif global annuel)
      if (profileCode === 'MD') {
        return [
          { value: 'FILIALE', label: 'Filiale' }
        ];
      }
      
      // Admin peut créer pour toutes les catégories
      if (ProfileManager.isAdmin()) {
        return [
          { value: 'TERRITOIRE', label: 'Territoire' },
          { value: 'POINT SERVICES', label: 'Point Services' },
          { value: 'GRAND COMPTE', label: 'Grand Compte' }
        ];
      }
      
      // CAF et autres ne peuvent rien créer
      return [];
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
      
      // DGA ne voit jamais le champ agence (fixe uniquement pour les territoires)
      if (profileCode === 'DGA') {
        return false;
      }
      
      // Pour les autres profils, afficher le champ agence selon la catégorie
      if (this.form.category === 'POINT SERVICES' || this.form.category === 'GRAND COMPTE') {
        return true;
      }
      
      // Pour TERRITOIRE, afficher seulement si ce n'est pas le DGA et qu'un territoire est sélectionné
      if (this.form.category === 'TERRITOIRE' && this.form.territory) {
        return profileCode !== 'DGA';
      }
      
      return false;
    }
  },
  mounted() {
    this.generateYears();
    if (!this.canCreateObjectives) {
      this.errorMessage = 'Vous n\'avez pas la permission de créer des objectifs.';
    }
  },
  watch: {
    'form.territory'(newVal) {
      if (newVal) {
        if (this.form.category === 'TERRITOIRE') {
          this.loadAgencies();
        }
        // Pour Responsable Zone, charger l'objectif DGA quand le territoire change
        if (this.isResponsableZone) {
          this.loadDGAObjective();
        }
      } else {
        this.dgaObjectiveValue = null;
      }
    },
    'form.category'(newVal) {
      // Pour DGA, charger l'objectif MD (filiale) quand la catégorie TERRITOIRE est sélectionnée
      if (newVal === 'TERRITOIRE' && this.profileCode === 'DGA') {
        this.loadMDFilialeObjective();
      }
      
      if (newVal === 'POINT SERVICES' || newVal === 'GRAND COMPTE') {
        // Pour Responsable Zone, attendre la sélection du territoire
        if (this.isResponsableZone) {
          // Ne pas charger les agences tant que le territoire n'est pas sélectionné
          if (this.form.territory) {
            this.loadAgencies();
          }
        } else {
          this.loadAgencies();
        }
      }
      
      // Réinitialiser les valeurs d'objectifs supérieurs
      if (this.isResponsableZone) {
        this.dgaObjectiveValue = null;
      }
      if (this.profileCode === 'DGA') {
        this.mdFilialeObjectiveValue = null;
      }
    },
    'form.agency'(newVal) {
      // L'objectif DGA est déjà chargé quand le territoire est sélectionné
      // On peut juste réinitialiser la valeur de l'objectif de l'agence
      if (newVal && this.isResponsableZone) {
        // Réinitialiser la valeur pour que l'utilisateur entre une nouvelle valeur
        // qui fait partie de la répartition de l'objectif DGA
        this.form.value = null;
      }
    },
    'form.type'(newVal) {
      // Recharger l'objectif DGA si le type change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
      }
    },
    'form.period'(newVal) {
      // Recharger l'objectif DGA si la période change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
      }
    },
    'form.month'(newVal) {
      // Recharger l'objectif DGA si le mois change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
      }
    },
    'form.quarter'(newVal) {
      // Recharger l'objectif DGA si le trimestre change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
      }
    },
    'form.year'(newVal) {
      // Pour DGA avec TERRITOIRE, recharger l'objectif MD (filiale) si l'année change
      if (newVal && this.profileCode === 'DGA' && this.form.category === 'TERRITOIRE') {
        this.loadMDFilialeObjective();
      }
      // Pour Responsable Zone, recharger l'objectif DGA si l'année change
      if (newVal && this.form.territory && this.isResponsableZone) {
        this.loadDGAObjective();
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
    async loadAgencies() {
      try {
        this.loadingAgencies = true;
        this.agencies = [];
        
        if (this.form.category === 'TERRITOIRE' && this.form.territory) {
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
        } else if (this.form.category === 'POINT SERVICES') {
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
        } else if (this.form.category === 'GRAND COMPTE') {
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
      this.form.category = '';
      this.form.territory = '';
      this.form.agency = '';
      this.agencies = [];
      this.dgaObjectiveValue = null;
    },
    onCategoryChange() {
      this.form.territory = '';
      this.form.agency = '';
      this.agencies = [];
      this.dgaObjectiveValue = null;
      if (this.form.category !== 'TERRITOIRE') {
        this.loadAgencies();
      }
    },
    onTerritoryChange() {
      // Charger les agences et l'objectif DGA
      if (this.form.category === 'TERRITOIRE') {
        this.loadAgencies();
      } else if (this.isResponsableZone) {
        // Pour Responsable Zone, charger les agences du territoire sélectionné
        this.loadAgencies();
        this.loadDGAObjective();
      }
    },
    async submitObjective() {
      this.loading = true;
      this.errorMessage = '';
      this.successMessage = '';

      try {
        // Validation côté client avant l'envoi
        if (!this.form.type) {
          this.errorMessage = 'Veuillez sélectionner un type d\'objectif.';
          this.loading = false;
          return;
        }
        if (!this.form.category) {
          this.errorMessage = 'Veuillez sélectionner une catégorie.';
          this.loading = false;
          return;
        }
        if (!this.form.value || this.form.value < 0) {
          this.errorMessage = 'Veuillez entrer une valeur d\'objectif valide (nombre entier positif).';
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

        // Pour DGA avec TERRITOIRE, pas besoin d'agence_code (fixe au niveau du territoire)
        // Pour MD avec FILIALE, pas besoin d'agence_code (fixe au niveau de la filiale)
        const profileCode = this.profileCode;
        const isDGAWithTerritory = profileCode === 'DGA' && this.form.category === 'TERRITOIRE';
        const isMDWithFiliale = profileCode === 'MD' && this.form.category === 'FILIALE';
        
        let agencyCode, agencyName;
        if (isDGAWithTerritory) {
          // Pour DGA, utiliser le territoire comme identifiant
          agencyCode = this.form.territory || 'TERRITOIRE';
          agencyName = this.getTerritoryName(this.form.territory) || 'Territoire';
        } else if (isMDWithFiliale) {
          // Pour MD avec FILIALE, utiliser 'FILIALE' comme identifiant
          agencyCode = 'FILIALE';
          agencyName = 'Filiale';
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
        
        // Convertir les valeurs en entiers
        const payload = {
          type: this.form.type,
          category: this.form.category,
          territory: this.form.territory || null,
          agency_code: agencyCode,
          agency_name: agencyName,
          value: parseInt(this.form.value, 10), // Convertir en entier
          period: this.form.period,
          month: this.form.period === 'month' ? parseInt(this.form.month, 10) : null,
          quarter: this.form.period === 'quarter' ? parseInt(this.form.quarter, 10) : null,
          year: parseInt(this.form.year, 10), // Convertir en entier
          description: this.form.description || null
        };

        // Vérifier que les conversions sont valides
        if (isNaN(payload.value) || payload.value < 0) {
          this.errorMessage = 'La valeur de l\'objectif doit être un nombre entier positif.';
          this.loading = false;
          return;
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
          const errorMessages = Object.values(errors).flat();
          this.errorMessage = errorMessages.join(', ') || error.response?.data?.message || 'Une erreur est survenue lors de l\'ajout de l\'objectif.';
        } else {
          this.errorMessage = error.response?.data?.message || 'Une erreur est survenue lors de l\'ajout de l\'objectif.';
        }
      } finally {
        this.loading = false;
      }
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
      if (!this.form.type || !this.form.period || !this.form.year) {
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
          type: this.form.type,
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
      // Vérifier que tous les champs nécessaires sont remplis
      if (!this.form.territory || !this.form.type || !this.form.period || !this.form.year) {
        this.dgaObjectiveValue = null;
        return;
      }

      // Pour les périodes mensuelles et trimestrielles, vérifier que month/quarter est rempli
      if (this.form.period === 'month' && !this.form.month) {
        this.dgaObjectiveValue = null;
        return;
      }
      if (this.form.period === 'quarter' && !this.form.quarter) {
        this.dgaObjectiveValue = null;
        return;
      }

      try {
        const token = localStorage.getItem('token');
        const params = {
          territory: this.form.territory,
          type: this.form.type,
          period: this.form.period,
          year: this.form.year
        };

        if (this.form.period === 'month') {
          params.month = this.form.month;
        } else if (this.form.period === 'quarter') {
          params.quarter = this.form.quarter;
        }

        const response = await axios.get('/api/objectives/dga-objective', {
          params: params,
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });

        if (response.data.success && response.data.data) {
          // Stocker la valeur de l'objectif DGA pour l'afficher comme référence
          this.dgaObjectiveValue = response.data.data.value;
        } else {
          this.dgaObjectiveValue = null;
        }
      } catch (error) {
        // Ne pas afficher d'erreur si c'est juste qu'il n'y a pas d'objectif DGA
        if (error.response?.status !== 404) {
          console.error('Erreur lors du chargement de l\'objectif DGA:', error);
        }
        this.dgaObjectiveValue = null;
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
    resetForm() {
      this.form = {
        type: '',
        category: '',
        territory: '',
        agency: '',
        value: null,
        period: '',
        month: null,
        quarter: null,
        year: null,
        description: ''
      };
      this.agencies = [];
      this.dgaObjectiveValue = null;
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
  justify-content: space-between;
  align-items: center;
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

.form-container {
  background: white;
  border-radius: 8px;
  padding: 30px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.objective-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  margin-bottom: 8px;
  font-weight: 500;
  color: #333;
  font-size: 14px;
}

.form-select,
.form-input,
.form-textarea {
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s;
  font-family: inherit;
}

.form-select:focus,
.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #1A4D3A;
  box-shadow: 0 0 0 3px rgba(26, 77, 58, 0.1);
}

.form-textarea {
  resize: vertical;
}

.error-message {
  padding: 12px;
  background: #fee2e2;
  color: #991b1b;
  border-radius: 6px;
  border: 1px solid #fecaca;
}

.success-message {
  padding: 12px;
  background: #d1fae5;
  color: #065f46;
  border-radius: 6px;
  border: 1px solid #a7f3d0;
}

.info-message {
  margin-top: 8px;
  padding: 8px;
  background: #f0f9ff;
  color: #0369a1;
  border-radius: 4px;
  font-size: 12px;
  border: 1px solid #bae6fd;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e0e0e0;
}

.btn-reset,
.btn-submit {
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 14px;
}

.btn-reset {
  background: #f3f4f6;
  color: #333;
}

.btn-reset:hover {
  background: #e5e7eb;
}

.btn-submit {
  background: #1A4D3A;
  color: white;
}

.btn-submit:hover:not(:disabled) {
  background: #153d2e;
}

.btn-submit:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>

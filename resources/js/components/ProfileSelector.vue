<template>
  <div class="profile-selector">
    <div class="current-profile" @click="toggleDropdown">
      <span class="profile-label">{{ getCurrentProfileLabel() }}</span>
      <span class="dropdown-arrow">{{ showDropdown ? '▲' : '▼' }}</span>
    </div>
    <div v-if="showDropdown" class="profile-dropdown">
      <div 
        v-for="profile in availableProfiles" 
        :key="profile.value"
        class="profile-option"
        :class="{ active: currentProfile === profile.value }"
        @click="selectProfile(profile.value)"
      >
        <div class="profile-option-header">
          <span class="profile-name">{{ profile.label }}</span>
        </div>
        <div class="profile-description">{{ profile.description }}</div>
      </div>
    </div>
  </div>
</template>

<script>
import { PROFILES, PROFILE_LABELS, PROFILE_DESCRIPTIONS, ProfileManager } from '../utils/profiles.js';

export default {
  name: 'ProfileSelector',
  data() {
    return {
      showDropdown: false,
      currentProfile: ProfileManager.getCurrentProfile(),
      availableProfiles: [
        {
          value: PROFILES.ADMIN,
          label: PROFILE_LABELS[PROFILES.ADMIN],
          description: PROFILE_DESCRIPTIONS[PROFILES.ADMIN]
        },
        {
          value: PROFILES.DGA,
          label: PROFILE_LABELS[PROFILES.DGA],
          description: PROFILE_DESCRIPTIONS[PROFILES.DGA]
        },
        {
          value: PROFILES.FINANCES,
          label: PROFILE_LABELS[PROFILES.FINANCES],
          description: PROFILE_DESCRIPTIONS[PROFILES.FINANCES]
        },
        {
          value: PROFILES.EXPLOITATIONS,
          label: PROFILE_LABELS[PROFILES.EXPLOITATIONS],
          description: PROFILE_DESCRIPTIONS[PROFILES.EXPLOITATIONS]
        }
      ]
    }
  },
  methods: {
    toggleDropdown() {
      this.showDropdown = !this.showDropdown;
    },
    selectProfile(profile) {
      this.currentProfile = profile;
      ProfileManager.setCurrentProfile(profile);
      this.showDropdown = false;
      this.$emit('profile-changed', profile);
      // Recharger la page pour appliquer les nouvelles permissions
      window.location.reload();
    },
    getCurrentProfileLabel() {
      return PROFILE_LABELS[this.currentProfile] || 'Sélectionner un profil';
    }
  },
  mounted() {
    // Fermer le dropdown si on clique en dehors
    document.addEventListener('click', (e) => {
      if (!this.$el.contains(e.target)) {
        this.showDropdown = false;
      }
    });
  }
}
</script>

<style scoped>
.profile-selector {
  position: relative;
  display: inline-block;
}

.current-profile {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s;
  color: white;
  font-size: 13px;
  font-weight: 500;
}

.current-profile:hover {
  background: rgba(255, 255, 255, 0.2);
}

.profile-label {
  white-space: nowrap;
}

.dropdown-arrow {
  font-size: 10px;
  transition: transform 0.2s;
}

.profile-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: white;
  border: 1px solid #DDD;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 300px;
  z-index: 1000;
  overflow: hidden;
}

.profile-option {
  padding: 12px 16px;
  cursor: pointer;
  transition: background 0.2s;
  border-bottom: 1px solid #F0F0F0;
}

.profile-option:last-child {
  border-bottom: none;
}

.profile-option:hover {
  background: #F5F5F5;
}

.profile-option.active {
  background: #E8F5E9;
  border-left: 3px solid #1A4D3A;
}

.profile-option-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.profile-name {
  font-weight: 600;
  font-size: 14px;
  color: #333;
}

.profile-description {
  font-size: 12px;
  color: #666;
  line-height: 1.4;
}
</style>


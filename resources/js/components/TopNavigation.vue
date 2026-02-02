<template>
  <div class="top-navigation">
    <nav class="horizontal-nav">
      <a href="#" v-for="(item, index) in navItems" :key="item.label" class="nav-item">
        <div class="nav-icon">{{ item.icon }}</div>
        <span class="nav-text">{{ item.label }}</span>
        <span class="nav-arrow">▼</span>
      </a>
      <span class="nav-scroll-arrow">←</span>
    </nav>
  </div>
</template>

<script>
export default {
  name: 'TopNavigation',
  data() {
    return {
      navItems: [
        { label: 'Accueil', icon: '🏠' },
        { label: 'Utilisateur', icon: '👤' },
        { label: 'Client 360', icon: '🤝' },
        { label: 'Recouvrement', icon: '🏦' },
        { label: 'Marketing', icon: '💰' },
        { label: 'Editions', icon: '🖨️' },
        { label: 'Consultations', icon: '📅' },
        { label: 'Actions', icon: '👆' },
        { label: 'Analytiques', icon: '📈' },
        { label: 'Démo', icon: '⚙️' }
      ]
    }
  }
}
</script>

<style scoped>
.top-navigation {
  background: #1A4D3A;
  width: 100%;
  padding: 10px 20px;
}

.horizontal-nav {
  display: flex;
  gap: 25px;
  align-items: center;
  overflow-x: auto;
}

.nav-item {
  color: white;
  text-decoration: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 8px 12px;
  border-radius: 4px;
  transition: background 0.2s;
  min-width: 80px;
  position: relative;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
}

.nav-icon {
  font-size: 20px;
  margin-bottom: 2px;
}

.nav-text {
  font-size: 12px;
  white-space: nowrap;
}

.nav-arrow {
  font-size: 10px;
  position: absolute;
  right: 8px;
  top: 8px;
  color: white;
}

.nav-scroll-arrow {
  color: white;
  font-size: 18px;
  margin-left: auto;
  padding: 8px;
  cursor: pointer;
}
</style>


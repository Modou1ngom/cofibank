<template>
  <div class="login-page">
    <!-- Animated background gradient -->
    <div class="animated-background"></div>
    
    <!-- Background decorative elements -->
    <div class="background-shapes">
      <div class="shape shape-1"></div>
      <div class="shape shape-2"></div>
      <div class="shape shape-3"></div>
      <div class="shape shape-4"></div>
      <div class="shape shape-5"></div>
    </div>

    <!-- Floating particles -->
    <div class="particles">
      <div class="particle" v-for="n in 20" :key="n" :style="{ left: Math.random() * 100 + '%', animationDelay: Math.random() * 5 + 's', animationDuration: (Math.random() * 3 + 2) + 's' }"></div>
    </div>

    <div class="login-container">
      <!-- Decorative top accent -->
      <div class="top-accent"></div>
      
      <div class="login-header">
        <div class="logo-wrapper">
          <div class="logo-glow"></div>
        <img src="/logo.png" alt="COFINA Logo" class="login-logo" />
        </div>
        <h1 class="login-title">
          <span class="title-word">COFINA</span>
          <span class="title-word">Banking</span>
        </h1>
        <p class="login-subtitle">Sénégal</p>
        <div class="welcome-text">
          <svg class="welcome-icon" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <p>Bienvenue dans votre espace bancaire</p>
        </div>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div v-if="error" class="error-message">
          <svg class="error-icon" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <span>{{ error }}</span>
        </div>

        <div class="form-group">
          <label for="email" class="form-label">
            <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor">
              <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
              <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
            </svg>
            <span>Email</span>
          </label>
          <div class="input-wrapper">
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            placeholder="votre.email@cofina.sn"
            class="form-input"
          />
            <div class="input-underline"></div>
          </div>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">
            <svg class="input-icon" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
            </svg>
            <span>Mot de passe</span>
          </label>
          <div class="input-wrapper">
          <input
            id="password"
            v-model="form.password"
            type="password"
            required
            placeholder="••••••••"
            class="form-input"
          />
            <div class="input-underline"></div>
          </div>
        </div>

        <button type="submit" :disabled="loading" class="login-button">
          <span class="button-background"></span>
          <span v-if="loading" class="button-content">
            <svg class="spinner" viewBox="0 0 24 24">
              <circle class="spinner-circle" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" stroke-linecap="round" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dasharray" dur="2s" values="0 32;16 16;0 32;0 32" repeatCount="indefinite"/>
                <animate attributeName="stroke-dashoffset" dur="2s" values="0;-16;-32;-32" repeatCount="indefinite"/>
              </circle>
            </svg>
            Connexion en cours...
          </span>
          <span v-else class="button-content">
            <svg class="button-icon" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Se connecter
          </span>
        </button>
      </form>

      <div class="login-footer">
        <div class="footer-divider"></div>
        <p class="footer-text">
          <svg class="footer-icon" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
          </svg>
          © 2026 COFINA Banking. Tous droits réservés.
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'LoginPage',
  data() {
    return {
      form: {
        email: '',
        password: ''
      },
      loading: false,
      error: null
    }
  },
  methods: {
    async handleLogin() {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.post('/api/login', this.form);
        
        // Stocker les informations utilisateur
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('userProfile', response.data.user.profile.code);

        // Rediriger vers le dashboard
        this.$router.push('/dashboard');
      } catch (error) {
        this.error = error.response?.data?.message || 
                    error.response?.data?.error || 
                    'Erreur de connexion. Vérifiez vos identifiants.';
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0a1f15;
  padding: 20px;
  position: relative;
  overflow: hidden;
}

/* Animated background gradient */
.animated-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #1A4D3A 0%, #2D6A4F 25%, #40916C 50%, #52B788 75%, #40916C 100%);
  background-size: 400% 400%;
  animation: gradientShift 15s ease infinite;
  opacity: 0.9;
}

@keyframes gradientShift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Background decorative shapes */
.background-shapes {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  z-index: 0;
  overflow: hidden;
}

.shape {
  position: absolute;
  border-radius: 50%;
  opacity: 0.1;
  animation: float 20s infinite ease-in-out;
}

.shape-1 {
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.3), transparent);
  top: -100px;
  left: -100px;
  animation-delay: 0s;
}

.shape-2 {
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.2), transparent);
  bottom: -50px;
  right: -50px;
  animation-delay: 5s;
}

.shape-3 {
  width: 150px;
  height: 150px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.15), transparent);
  top: 50%;
  right: 10%;
  animation-delay: 10s;
}

.shape-4 {
  width: 100px;
  height: 100px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.1), transparent);
  bottom: 20%;
  left: 15%;
  animation-delay: 15s;
}

.shape-5 {
  width: 250px;
  height: 250px;
  background: radial-gradient(circle, rgba(255, 215, 0, 0.15), transparent);
  top: 60%;
  left: 50%;
  animation-delay: 7s;
}

/* Floating particles */
.particles {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  z-index: 0;
  overflow: hidden;
}

.particle {
  position: absolute;
  width: 4px;
  height: 4px;
  background: rgba(255, 255, 255, 0.6);
  border-radius: 50%;
  animation: floatParticle linear infinite;
  box-shadow: 0 0 6px rgba(255, 255, 255, 0.8);
}

@keyframes floatParticle {
  0% {
    transform: translateY(100vh) translateX(0) scale(0);
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    transform: translateY(-100px) translateX(100px) scale(1);
    opacity: 0;
  }
}

@keyframes float {
  0%, 100% {
    transform: translate(0, 0) scale(1);
  }
  33% {
    transform: translate(30px, -30px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
}

.login-container {
  background: rgba(255, 255, 255, 0.99);
  backdrop-filter: blur(30px);
  border-radius: 28px;
  box-shadow: 
    0 30px 100px rgba(0, 0, 0, 0.5),
    0 0 0 1px rgba(255, 255, 255, 0.3),
    inset 0 2px 4px rgba(255, 255, 255, 0.8),
    inset 0 -2px 4px rgba(0, 0, 0, 0.05);
  padding: 60px 50px;
  width: 100%;
  max-width: 600px;
  position: relative;
  z-index: 1;
  animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.5);
}

/* Top accent bar */
.top-accent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #FF6B00, #DC143C, #FF6B00, #1A4D3A);
  background-size: 300% 100%;
  animation: accentFlow 3s linear infinite;
}

@keyframes accentFlow {
  0% { background-position: 0% 0%; }
  100% { background-position: 300% 0%; }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.login-header {
  text-align: center;
  margin-bottom: 45px;
  position: relative;
}

.logo-wrapper {
  display: inline-block;
  padding: 28px;
  background: linear-gradient(135deg, rgba(26, 77, 58, 0.1), rgba(64, 145, 108, 0.15));
  border-radius: 28px;
  margin-bottom: 35px;
  position: relative;
  animation: pulse 3s ease-in-out infinite;
  box-shadow: 
    0 10px 30px rgba(26, 77, 58, 0.2),
    inset 0 2px 4px rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(26, 77, 58, 0.1);
}

.logo-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 120%;
  height: 120%;
  background: radial-gradient(circle, rgba(26, 77, 58, 0.2), transparent);
  border-radius: 50%;
  animation: glowPulse 2s ease-in-out infinite;
  pointer-events: none;
}

@keyframes glowPulse {
  0%, 100% {
    opacity: 0.5;
    transform: translate(-50%, -50%) scale(1);
  }
  50% {
    opacity: 0.8;
    transform: translate(-50%, -50%) scale(1.1);
  }
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1) rotate(0deg);
  }
  50% {
    transform: scale(1.03) rotate(1deg);
  }
}

.login-logo {
  width: 140px;
  height: auto;
  display: block;
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.2));
  transition: transform 0.3s ease;
}

.logo-wrapper:hover .login-logo {
  transform: scale(1.05);
}

.login-title {
  font-size: 42px;
  font-weight: 900;
  margin: 0 0 12px 0;
  letter-spacing: -1.5px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  line-height: 1.1;
}

.title-word {
  background: linear-gradient(135deg, #1A4D3A 0%, #2D6A4F 30%, #40916C 60%, #52B788 100%);
  background-size: 200% 200%;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: inline-block;
  animation: titleSlide 0.8s ease-out, titleGradient 3s ease infinite;
  animation-fill-mode: both;
  text-shadow: 0 4px 12px rgba(26, 77, 58, 0.2);
  font-weight: 900;
  letter-spacing: -2px;
}

@keyframes titleGradient {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.title-word:nth-child(1) {
  animation-delay: 0.2s;
}

.title-word:nth-child(2) {
  animation-delay: 0.4s;
}

@keyframes titleSlide {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.login-subtitle {
  font-size: 18px;
  color: #555;
  margin: 0 0 20px 0;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 2px;
  opacity: 0.8;
}

.welcome-text {
  margin-top: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  animation: fadeInUp 1s ease-out 0.6s both;
  padding: 12px 20px;
  background: linear-gradient(135deg, rgba(26, 77, 58, 0.05), rgba(64, 145, 108, 0.08));
  border-radius: 12px;
  border: 1px solid rgba(26, 77, 58, 0.1);
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.welcome-icon {
  width: 20px;
  height: 20px;
  color: #1A4D3A;
  animation: checkBounce 1s ease-out 1s both, checkmarkPulse 2s ease-in-out infinite;
  filter: drop-shadow(0 2px 4px rgba(26, 77, 58, 0.2));
}

@keyframes checkmarkPulse {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.1);
    opacity: 0.8;
  }
}

@keyframes checkBounce {
  0% {
    opacity: 0;
    transform: scale(0) rotate(-180deg);
  }
  50% {
    transform: scale(1.2) rotate(10deg);
  }
  100% {
    opacity: 1;
    transform: scale(1) rotate(0deg);
  }
}

.welcome-text p {
  font-size: 15px;
  color: #4B5563;
  margin: 0;
  font-style: italic;
  font-weight: 500;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
  position: relative;
}

.form-label {
  font-size: 15px;
  font-weight: 700;
  color: #1F2937;
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
  transition: color 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-group:focus-within .form-label {
  color: #1A4D3A;
}

.input-icon {
  width: 22px;
  height: 22px;
  color: #6B7280;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.form-group:focus-within .input-icon {
  color: #1A4D3A;
  transform: scale(1.15) rotate(5deg);
  filter: drop-shadow(0 4px 8px rgba(26, 77, 58, 0.3));
}

.input-wrapper {
  position: relative;
}

.form-input {
  width: 100%;
  padding: 18px 20px;
  border: 2px solid #E5E7EB;
  border-radius: 14px;
  font-size: 16px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  background: linear-gradient(to bottom, #FFFFFF, #FAFAFA);
  color: #333;
  box-shadow: 
    0 2px 8px rgba(0, 0, 0, 0.04),
    inset 0 1px 2px rgba(255, 255, 255, 0.8);
  font-weight: 500;
}

.form-input:focus {
  outline: none;
  border-color: #1A4D3A;
  background: white;
  box-shadow: 
    0 6px 20px rgba(26, 77, 58, 0.2),
    0 0 0 5px rgba(26, 77, 58, 0.1),
    inset 0 1px 3px rgba(0, 0, 0, 0.05);
  transform: translateY(-3px);
  border-width: 3px;
}

.form-input::placeholder {
  color: #9CA3AF;
  transition: opacity 0.3s ease;
}

.form-input:focus::placeholder {
  opacity: 0.5;
}

.input-underline {
  position: absolute;
  bottom: 0;
  left: 50%;
  width: 0;
  height: 3px;
  background: linear-gradient(90deg, #1A4D3A, #2D6A4F, #40916C, #52B788);
  background-size: 200% 100%;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  transform: translateX(-50%);
  border-radius: 2px;
  box-shadow: 0 2px 8px rgba(26, 77, 58, 0.4);
}

.form-input:focus ~ .input-underline {
  width: 100%;
  left: 0;
  transform: translateX(0);
  animation: underlineFlow 2s ease infinite;
}

@keyframes underlineFlow {
  0%, 100% {
    background-position: 0% 0%;
  }
  50% {
    background-position: 100% 0%;
  }
}

.login-button {
  padding: 20px 28px;
  background: linear-gradient(135deg, #1A4D3A 0%, #2D6A4F 30%, #40916C 60%, #52B788 100%);
  background-size: 200% 200%;
  color: white;
  border: none;
  border-radius: 14px;
  font-size: 17px;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  margin-top: 16px;
  box-shadow: 
    0 8px 25px rgba(26, 77, 58, 0.5),
    0 0 0 0 rgba(26, 77, 58, 0.6),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
  position: relative;
  overflow: hidden;
  text-transform: uppercase;
  letter-spacing: 1px;
  animation: gradientMove 3s ease infinite;
}

@keyframes gradientMove {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

.button-background {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.6s ease;
}

.login-button:hover:not(:disabled) .button-background {
  left: 100%;
}

.login-button:hover:not(:disabled) {
  background: linear-gradient(135deg, #0f3d2a 0%, #1A4D3A 30%, #2D6A4F 60%, #40916C 100%);
  background-size: 200% 200%;
  transform: translateY(-4px) scale(1.03);
  box-shadow: 
    0 12px 35px rgba(26, 77, 58, 0.6),
    0 0 0 5px rgba(26, 77, 58, 0.25),
    inset 0 2px 4px rgba(255, 255, 255, 0.3);
  animation: gradientMove 2s ease infinite;
}

.login-button:active:not(:disabled) {
  transform: translateY(-1px) scale(1);
  box-shadow: 
    0 4px 15px rgba(26, 77, 58, 0.4),
    0 0 0 2px rgba(26, 77, 58, 0.3);
}

.login-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: 0 4px 15px rgba(26, 77, 58, 0.2);
}

.button-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.button-icon {
  width: 20px;
  height: 20px;
}

.spinner {
  width: 20px;
  height: 20px;
  animation: spin 1s linear infinite;
}

.spinner-circle {
  opacity: 0.5;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.error-message {
  padding: 16px 20px;
  background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 50%, #FCA5A5 100%);
  border: 2px solid #EF4444;
  border-radius: 14px;
  color: #991B1B;
  font-size: 15px;
  font-weight: 700;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 
    0 6px 20px rgba(239, 68, 68, 0.3),
    inset 0 1px 2px rgba(255, 255, 255, 0.5);
  gap: 12px;
  animation: shake 0.6s cubic-bezier(0.36, 0.07, 0.19, 0.97);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
  font-weight: 500;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}

.error-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.login-footer {
  margin-top: 35px;
  text-align: center;
  padding-top: 25px;
}

.footer-divider {
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(26, 77, 58, 0.2), transparent);
  margin-bottom: 25px;
  position: relative;
}

.footer-divider::before {
  content: '';
  position: absolute;
  top: -2px;
  left: 50%;
  transform: translateX(-50%);
  width: 60px;
  height: 2px;
  background: linear-gradient(90deg, transparent, #1A4D3A, transparent);
}

.footer-text {
  font-size: 13px;
  color: #6B7280;
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-weight: 600;
}

.footer-icon {
  width: 14px;
  height: 14px;
  color: #9CA3AF;
  animation: rotateIcon 20s linear infinite;
}

@keyframes rotateIcon {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Responsive design */
@media (max-width: 640px) {
  .login-container {
    padding: 40px 30px;
    border-radius: 16px;
  }

  .login-title {
    font-size: 28px;
  }

  .login-logo {
    width: 80px;
  }

  .logo-wrapper {
    padding: 15px;
  }
}
</style>


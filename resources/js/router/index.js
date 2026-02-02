import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../pages/Dashboard.vue';
import LoginPage from '../pages/LoginPage.vue';
import ProfileManagementPage from '../pages/ProfileManagementPage.vue';
import AddObjectivePage from '../pages/AddObjectivePage.vue';

const routes = [
  {
    path: '/',
    name: 'login',
    component: LoginPage
  },
  {
    path: '/login',
    name: 'login-alt',
    component: LoginPage
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/profiles',
    name: 'profile-management',
    component: ProfileManagementPage,
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/users',
    name: 'user-management',
    component: () => import('../pages/UserManagementPage.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/objectives/add',
    name: 'add-objective',
    component: AddObjectivePage,
    meta: { requiresAuth: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Garde de navigation
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  const isAuthenticated = !!token;

  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/');
  } else if ((to.path === '/' || to.path === '/login') && isAuthenticated) {
    next('/dashboard');
  } else {
    next();
  }
});

export default router;

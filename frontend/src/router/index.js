import { createRouter, createWebHistory } from 'vue-router'
import { isAuthenticated } from '../stores/auth'

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { requiresAuth: true, title: 'Portofolio' },
  },
  {
    path: '/holdings/new',
    name: 'holding-create',
    component: () => import('../views/HoldingFormView.vue'),
    meta: { requiresAuth: true, title: 'Tambah holding' },
  },
  {
    path: '/holdings/:id/edit',
    name: 'holding-edit',
    component: () => import('../views/HoldingFormView.vue'),
    props: true,
    meta: { requiresAuth: true, title: 'Edit holding' },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { guestOnly: true, title: 'Masuk' },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/RegisterView.vue'),
    meta: { guestOnly: true, title: 'Daftar' },
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Guard: halaman privat butuh token, halaman login/register hanya untuk tamu.
router.beforeEach((to) => {
  if (to.meta.requiresAuth && !isAuthenticated.value) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.meta.guestOnly && isAuthenticated.value) {
    return { name: 'dashboard' }
  }
  return true
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · ChainFolio` : 'ChainFolio'
})

export default router

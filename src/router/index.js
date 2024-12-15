// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router';
import Home from './Pages/Home.vue';
import Highlight from './Pages/Highlight.vue'; // Nueva vista

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
  },
  {
    path: '/highlight',
    name: 'Highlight',
    component: Highlight,
  },
];

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes,
});

export default router;

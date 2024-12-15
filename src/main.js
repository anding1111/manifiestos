import { createApp } from 'vue';
import App from './App.vue';
import vuetify from './plugins/vuetify';
import { loadFonts } from './plugins/webfontloader';
import { createRouter, createWebHistory } from 'vue-router';
import Highlight from './Pages/Highlight.vue';

loadFonts();

const routes = [
  { path: '/', component: App },
  { path: '/highlight', component: Highlight }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

createApp(App)
  .use(vuetify)
  .use(router)
  .mount('#app');

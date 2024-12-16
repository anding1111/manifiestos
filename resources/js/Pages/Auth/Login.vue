<template>
  <v-app style="height: 100vh;">
    <v-main>
      <v-container fluid fill-height class="container-spacing">
        <v-row align="center" justify="center">
          <v-col cols="12" sm="8" md="10" lg="8">
            <v-card class="d-flex custom-card">
              <!-- Lado izquierdo con ilustración -->
              <v-col md="6" class="d-none d-md-flex align-center justify-center illustration-side">
                <svg viewBox="0 0 200 200" class="illustration">
                  <path d="M140,120 Q120,160 80,140 T40,120" fill="none" stroke="#6C4BEB" stroke-width="2" />
                  <circle cx="100" cy="100" r="30" fill="#F07D30" opacity="0.2" />
                  <rect x="70" y="85" width="60" height="40" rx="5" fill="#6C4BEB" />
                </svg>
              </v-col>
              <!-- Lado derecho con formulario de login/recuperación -->
              <v-col md="6">
                <v-card-text class="pa-8">
                  <template v-if="!showResetForm">
                    <h1 class="text-h4 mb-6 primary--text">Bienvenido</h1>
                    <v-form
                      ref="loginForm"
                      v-model="isLoginFormValid"
                      @submit.prevent="login"
                      lazy-validation
                    >
                      <v-text-field
                        v-model="email"
                        label="Correo electrónico"
                        :error-messages="$page.props.errors.email"
                        :prepend-inner-icon="'mdi-email'"
                        :rules="emailRules"
                        variant="outlined"
                        density="comfortable"
                        class="custom-field"
                        validate-on-blur
                        required
                      ></v-text-field>

                      <v-text-field
                        v-model="password"
                        :type="showPassword ? 'text' : 'password'"
                        label="Contraseña"
                        :error-messages="$page.props.errors.password"
                        :prepend-inner-icon="'mdi-lock'"
                        :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                        @click:append-inner="showPassword = !showPassword"
                        :rules="passwordRules"
                        variant="outlined"
                        density="comfortable"
                        class="custom-field"
                        required
                      ></v-text-field>

                      <div class="d-flex justify-space-between align-center mb-6">
                        <v-checkbox
                          v-model="remember"
                          label="Recordarme"
                          density="comfortable"
                          class="custom-checkbox"
                        ></v-checkbox>
                        <v-btn
                          variant="text"
                          color="secondary"
                          size="small"
                          class="reset-password-button"
                          @click="showResetForm = true"
                        >
                          ¿Olvidaste tu contraseña?
                        </v-btn>
                      </div>

                      <v-btn
                        block
                        color="primary"
                        class="submit-button"
                        :loading="loading"
                        type="submit"
                      >
                        Iniciar sesión
                      </v-btn>
                    </v-form>
                  </template>
                  <template v-else>
                    <div class="d-flex align-center mb-6">
                      <v-btn icon class="mr-4" @click="showResetForm = false">
                        <v-icon>mdi-arrow-left</v-icon>
                      </v-btn>
                      <h1 class="text-h4 primary--text">Recuperar Contraseña</h1>
                    </div>
                    <p class="mb-6">Ingresa tu correo electrónico y te enviaremos las instrucciones para restablecer tu contraseña.</p>
                    <v-form
                      ref="resetForm"
                      v-model="isResetFormValid"
                      @submit.prevent="resetPassword"
                      lazy-validation
                    >
                      <v-text-field
                        v-model="resetEmail"
                        label="Correo electrónico"
                        :prepend-inner-icon="'mdi-email'"
                        :rules="emailRules"
                        variant="outlined"
                        density="comfortable"
                        class="custom-field mb-6"
                        validate-on-blur
                        required
                      ></v-text-field>

                      <v-btn
                        block
                        color="primary"
                        class="submit-button"
                        :loading="resetLoading"
                        type="submit"
                      >
                        Enviar instrucciones
                      </v-btn>
                    </v-form>
                  </template>
                </v-card-text>
              </v-col>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
      <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
        {{ snackbar.text }}
      </v-snackbar>
    </v-main>
  </v-app>
</template>

<script>
import { ref, nextTick, onMounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { usePage } from '@inertiajs/inertia-vue3';
import '@mdi/font/css/materialdesignicons.css'; // Asegurarse de usar css-loader
import { createVuetify } from 'vuetify';
import { h, provide } from 'vue';

const localVuetify = createVuetify({
  theme: {
    themes: {
      light: {
        colors: {
          primary: '#6C4BEB',
          secondary: '#F07D30',
        },
      },
    },
  },
});

export default {
  name: "Login",
  setup() {
    provide('vuetify', localVuetify.framework);
  },
  data() {
    return {
      email: '',
      password: '',
      resetEmail: '',
      showPassword: false,
      remember: false,
      loading: false,
      resetLoading: false,
      showResetForm: false,
      isLoginFormValid: false,
      isResetFormValid: false,
      emailRules: [
        v => !!v || 'El correo electrónico es requerido',
        v => /.+@.+\..+/.test(v) || 'El correo electrónico debe ser válido'
      ],
      passwordRules: [
        v => !!v || 'La contraseña es requerida',
        v => (v || '').length >= 6 || 'La contraseña debe tener al menos 6 caracteres'
      ],
      snackbar: { show: false, text: '', color: 'success' },
    };
  },
  methods: {
    async login() {
      if (this.$refs.loginForm.validate()) {
        this.loading = true;
        try {
          // Realiza la solicitud a Laravel usando Inertia.js
          await this.$inertia.post('/login', {
            email: this.email,
            password: this.password,
            remember: this.remember,
          });

          this.showSnackbar('¡Inicio de sesión exitoso!', 'success');
        } catch (error) {
          console.error('Error de inicio de sesión:', error);
          this.showSnackbar(
            'Error al iniciar sesión. Por favor, verifica tus credenciales.',
            'error'
          );
        } finally {
          this.loading = false;
        }
      }
    },
    async resetPassword() {
      if (this.$refs.resetForm.validate()) {
        this.resetLoading = true;
        try {
          // Realiza la solicitud de recuperación de contraseña a Laravel
          await this.$inertia.post('/forgot-password', {
            email: this.resetEmail,
          });

          this.showSnackbar('¡Instrucciones enviadas a tu correo!', 'success');
          this.resetEmail = '';
          this.showResetForm = false;
        } catch (error) {
          console.error('Error al restablecer contraseña:', error);
          this.showSnackbar(
            'No se pudo enviar el correo. Por favor, inténtalo nuevamente.',
            'error'
          );
        } finally {
          this.resetLoading = false;
        }
      }
    },
    showSnackbar(text, color = 'success') {
      this.snackbar.text = text;
      this.snackbar.color = color;
      this.snackbar.show = true;
    },
  },
};

</script>

<style scoped>
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

#app {
  height: 100%;
}

.v-application {
  min-height: 100vh !important;
}

.v-main {
  background: #f5f5f5;
}

.illustration-side {
  background: #f8f9fa;
}

.illustration {
  width: 80%;
  height: auto;
}

.custom-card {
  border-radius: 24px !important;
  overflow: hidden;
  box-shadow: 0 4px 30px rgba(108, 75, 235, 0.1) !important;
}

.custom-field {
  border-radius: 12px !important;
}

/* New independent submit button styles */
.submit-button {
  border-radius: 12px !important;
  text-transform: none !important;
  letter-spacing: normal !important;
  font-size: 1.1rem !important;
  background-color: #6C4BEB !important;
  color: white !important;
  border: none !important;
  transition: background-color 0.3s ease !important;
  min-height: 48px !important;
  width: 100% !important;
  margin-bottom: 24px !important;
  padding: 0 24px !important;
  cursor: pointer !important;
}

.submit-button:hover {
  background-color: #5636c9 !important;
}
.reset-password-button:hover {
  background-color: #ffffff !important;
}
.reset-password-button {
  color: #f07d30 !important;
}

.submit-button:active {
  background-color: #4528a7 !important;
}

.custom-checkbox .v-input--selection-controls__input {
  color: #6C4BEB !important;
}

@keyframes float {
  0% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
  100% { transform: translateY(0px); }
}

.illustration {
  animation: float 6s ease-in-out infinite;
}

.v-field--variant-outlined .v-field__outline {
  border-color: rgba(108, 75, 235, 0.2) !important;
}

.v-field--variant-outlined:focus-within .v-field__outline {
  border-color: #6C4BEB !important;
}

/* Add this CSS rule to create spacing from the top */
.container-spacing {
  margin-top: 48px !important; /* You can adjust this value as needed */
  margin-bottom: 48px !important;
}
</style>
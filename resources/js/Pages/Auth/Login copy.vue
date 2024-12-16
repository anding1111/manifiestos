<template>
  <v-app :theme="vuetifyTheme">
    <v-main>
      <v-container fluid fill-height>
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
                        prepend-inner-icon="mdi-email"
                        :rules="emailRules"
                        outlined
                        dense
                        class="custom-field"
                        validate-on-blur
                        required
                      ></v-text-field>

                      <v-text-field
                        v-model="password"
                        :type="showPassword ? 'text' : 'password'"
                        label="Contraseña"
                        :error-messages="$page.props.errors.password"
                        prepend-inner-icon="mdi-lock"
                        :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                        @click:append="showPassword = !showPassword"
                        :rules="passwordRules"
                        outlined
                        dense
                        class="custom-field"
                        required
                      ></v-text-field>

                      <div class="d-flex justify-space-between align-center mb-6">
                        <v-checkbox
                          v-model="remember"
                          label="Recordarme"
                          dense
                          class="custom-checkbox"
                        ></v-checkbox>
                        <v-btn
                          text
                          color="secondary"
                          small
                          @click="showResetForm = true"
                        >
                          ¿Olvidaste tu contraseña?
                        </v-btn>
                      </div>

                      <v-btn
                        block
                        color="primary"
                        height="48"
                        class="mb-6 custom-button"
                        :loading="loading"
                        :disabled="!isLoginFormValid"
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
                        prepend-inner-icon="mdi-email"
                        :rules="emailRules"
                        outlined
                        dense
                        class="custom-field mb-6"
                        validate-on-blur
                        required
                      ></v-text-field>

                      <v-btn
                        block
                        color="primary"
                        height="48"
                        class="mb-6 custom-button"
                        :loading="resetLoading"
                        :disabled="!isResetFormValid"
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
import { useVuetify } from 'vuetify';
export default {
  data() {
    return {
      vuetifyTheme: {
        themes: {
          light: {
            primary: '#6C4BEB',
            secondary: '#F07D30',
          },
        },
      },
    };
  },
};
</script>

---

Esto asegura que el tema será exclusivo de `Login.vue`. Avísame si quieres ajustes adicionales!

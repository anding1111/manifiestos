<template>
    <v-app>
      <v-main>
        <v-container class="d-flex justify-center align-center fill-height">
          <v-card class="pa-5" elevation="3" max-width="600">
            <v-card-title class="text-2xl font-bold mb-4 text-center">Login</v-card-title>
            <form @submit.prevent="submit" class="d-flex flex-column align-center">
              <v-text-field
                v-model="form.email"
                label="Email"
                required
                class="mb-4"
              ></v-text-field>
              <v-text-field
                v-model="form.password"
                label="Password"
                type="password"
                required
                class="mb-4"
              ></v-text-field>
              <v-btn type="submit" color="primary" class="mb-4" :disabled="isLoading">
                Login
              </v-btn>
            </form>
            <v-alert v-if="errors.email" type="error" class="mt-4">{{ errors.email[0] }}</v-alert>
          </v-card>
        </v-container>
      </v-main>
    </v-app>
  </template>
  
  <script>
  import { ref } from 'vue';
  import { Inertia } from '@inertiajs/inertia';
  
  export default {
    setup() {
      const form = ref({
        email: '',
        password: ''
      });
      const isLoading = ref(false);
      const errors = ref({});
  
      const submit = () => {
        isLoading.value = true;
  
        Inertia.post('/login', form.value, {
          onSuccess: () => {
            isLoading.value = false;
          },
          onError: (page) => {
            errors.value = page.props.errors;
            isLoading.value = false;
          }
        });
      };
  
      return { form, isLoading, errors, submit };
    }
  };
  </script>
  
  <style scoped>
  .fill-height {
    height: 100vh;
  }
  </style>
  
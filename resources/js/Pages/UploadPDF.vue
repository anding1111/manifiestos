<template>
  <v-app>
    <v-main>
      <v-container class="d-flex justify-center align-center fill-height">
        <v-card class="pa-5" elevation="3" max-width="600">
          <v-card-title color="primary" class="text-2xl font-bold mb-4 text-center">SUBIR PDF</v-card-title>
          <form @submit.prevent="submit" class="d-flex flex-column align-center">
            <v-file-input
              v-model="files"
              :show-size="1000"
              color="deep-purple-accent-4"
              label="Selecciona tu archivo"
              placeholder="Selecciona tu archivo"
              prepend-icon=""
              variant="outlined"
              counter
              class="mb-4 mx-auto"
              width="50vh"
              accept="application/pdf"
              @change="handleFileChange"
            >
              <template v-slot:selection="{ fileNames }">
                <template v-for="(fileName, index) in fileNames" :key="fileName">
                  <v-chip
                    v-if="index < 2"
                    class="me-2"
                    color="deep-purple-accent-4"
                    size="small"
                    label
                  >
                    {{ fileName }}
                  </v-chip>

                  <span
                    v-else-if="index === 2"
                    class="text-overline text-grey-darken-3 mx-2"
                  >
                    +{{ files.length - 2 }} archivo(s)
                  </span>
                </template>
              </template>
            </v-file-input>
            <v-btn type="submit" color="light-blue-darken-4" width="50vh" class="mb-4" :disabled="isLoading">
              Subir
            </v-btn>
          </form>
          <v-progress-linear
            v-if="isLoading"
            indeterminate
            color="primary"
            class="my-4"
          ></v-progress-linear>
          <v-card-text v-if="isLoading" class="text-h6 text-md-h6 text-lg-h5 text-center" color="primary">
            Subiendo Archivos...
          </v-card-text>
          <v-alert v-if="message" :type="isLoading ? 'info' : 'success'" class="mt-4">
            {{ message }}
          </v-alert>
        </v-card>
      </v-container>
    </v-main>
    <v-bottom-navigation
				v-model="value"
				:bg-color="color"
				mode="shift"
        class="nav-bar"
			>
				<v-btn text @click="navigateTo('home')">
					<v-icon>mdi-barcode-scan</v-icon>

					<span>Consulta</span>
				</v-btn>

				<v-btn text @click="navigateTo('upload')">
					<v-icon>mdi-file-upload</v-icon>

					<span>Archivos</span>
				</v-btn>
			</v-bottom-navigation>
  </v-app>
</template>

<script>
import { ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import '@mdi/font/css/materialdesignicons.css'; // Ensure you are using css-loader

export default {
  setup() {
    const files = ref([]);
    const message = ref('');
    const isLoading = ref(false);

    const handleFileChange = (event) => {
      // Aquí puedes manejar cambios en el archivo, si es necesario
    };

    const submit = () => {
      if (files.value.length === 0) {
        message.value = 'Por favor selecciona un archivo.';
        return;
      }

      isLoading.value = true;
      const formData = new FormData();
      formData.append('file', files.value[0]); // Si solo manejas un archivo

      Inertia.post('/upload-pdf', formData, {
        onSuccess: (page) => {
          message.value = page?.props?.flash?.message || 'Archivo subido y procesado exitosamente.';
          isLoading.value = false;
          files.value = []; // Resetear el input de archivo

          setTimeout(() => {
            message.value = ''; // Ocultar el mensaje después de 3 segundos
          }, 3000);
        },
        onError: (errors) => {
          // Mostrar los errores devueltos por el backend
          if (errors.file) {
            message.value = errors.file[0]; // Mostrar el mensaje de error específico
          } else {
            message.value = 'Error procesando el archivo. Asegúrate de que el archivo contiene IMEIs válidos.';
          }
          isLoading.value = false;
        },
      });
    };

    const navigateTo = (route) => {
      Inertia.visit(route === 'home' ? '/' : `/${route}`);
    };

    return { files, handleFileChange, submit, message, isLoading, navigateTo };
  },
  data: () => ({ value: 1 }),
  computed: {
    color () {
      switch (this.value) {
        case 0: return 'deep-purple-accent-4'
        case 1: return 'light-blue-darken-4'
        // case 2: return 'brown'
        // case 3: return 'indigo'
        default: return 'deep-purple-accent-4'
      }
    },
  },
  icons: {
    defaultSet: 'mdi',
  },
};
</script>

<style scoped>
.fill-height {
  height: 100vh;
}
.v-input {
  display: block;
}
.v-file-input {
  flex-direction: row-reverse;
}
.nav-bar {
  bottom: 4px !important;
  left: 20% !important;
  width: calc(60% + 0px) !important;
  border-radius: 20px;
  opacity: 0.9;
}
</style>

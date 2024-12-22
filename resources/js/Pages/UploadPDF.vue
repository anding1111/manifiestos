<template>
  <v-app>
    <v-main>
      <!-- Barra superior con opciones -->
      <v-menu location="bottom" origin="top right" transition="slide-y-transition">
        <template v-slot:activator="{ props }">
          <v-btn
            icon
            variant="elevated"
            size="large"
            v-bind="props"
            class="floating-user-btn"
          >
            <v-icon size="x-large">mdi-account-circle</v-icon>
          </v-btn>
        </template>
        <v-list>
          <v-list-item @click="openProfileModal" hover>
            <v-list-item-title>
              <v-icon start>mdi-account-edit</v-icon>
              Perfil
            </v-list-item-title>
          </v-list-item>
          <v-list-item @click="openPasswordChangeModal" hover>
            <v-list-item-title>
              <v-icon start>mdi-lock-reset</v-icon>
              Cambiar Contraseña
            </v-list-item-title>
          </v-list-item>
          <v-list-item @click="logout" hover>
            <v-list-item-title>
              <v-icon start>mdi-logout</v-icon>
              Cerrar Sesión
            </v-list-item-title>
          </v-list-item>
        </v-list>
      </v-menu>
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

      <!-- Modal Perfil -->
      <v-dialog v-model="profileModal" max-width="500px">
        <v-card>
          <v-card-title>Editar Perfil</v-card-title>
          <v-card-text>
            <v-form ref="profileForm" @submit.prevent="saveProfile" v-model="profileFormValid">
              <v-text-field
                v-model="profileUser.name"
                label="Nombre"
                variant="outlined"
                :rules="[v => !!v || 'El nombre es obligatorio']"
                required
              ></v-text-field>
              <v-text-field
                v-model="profileUser.email"
                label="Correo Electrónico"
                type="email"
                variant="outlined"
                :rules="[v => !!v || 'El correo electrónico es obligatorio']"
                readonly
                disabled
              ></v-text-field>
              <v-btn block color="primary" type="submit" :disabled="!profileFormValid">
                Guardar Cambios
              </v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-dialog>

    <!-- Modal Cambio de Contraseña -->
    <v-dialog v-model="passwordChangeModal" max-width="500px">
      <v-card>
        <v-card-title>Cambiar Contraseña</v-card-title>
        <v-card-text>
          <v-form ref="passwordForm" @submit.prevent="changePassword" v-model="passwordFormValid">
            <v-text-field
              v-model="passwordChange.currentPassword"
              label="Contraseña Actual"
              type="password"
              variant="outlined"
              :rules="[v => !!v || 'La contraseña actual es obligatoria']"
              required
            ></v-text-field>
            <v-text-field
              v-model="passwordChange.newPassword"
              label="Nueva Contraseña"
              type="password"
              variant="outlined"
              :rules="[
                v => !!v || 'La nueva contraseña es obligatoria',
                v => v.length >= 6 || 'La contraseña debe tener al menos 6 caracteres'
              ]"
              required
            ></v-text-field>
            <v-text-field
              v-model="passwordChange.confirmPassword"
              label="Confirmar Nueva Contraseña"
              type="password"
              variant="outlined"
              :rules="[
                v => !!v || 'Debe confirmar la nueva contraseña',
                v => v === passwordChange.newPassword || 'Las contraseñas no coinciden'
              ]"
              required
            ></v-text-field>
            <v-btn block color="primary" type="submit" :disabled="!passwordFormValid">
              Cambiar Contraseña
            </v-btn>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>
    </v-main>
    <!-- Navegación inferior con botones para ir a la página de consulta y la de subida de archivos -->
    <v-bottom-navigation v-model="value" :bg-color="color" mode="shift" class="nav-bar">
      <!-- Botón Home -->
      <v-btn text @click="navigateTo('home')" v-if="loggedInUser.role === 'Administrador' || loggedInUser.role === 'Trabajador' || loggedInUser.role === 'Cliente'">
        <v-icon>mdi-barcode-scan</v-icon>
        <span>Consulta</span>
      </v-btn>
    
      <!-- Botón Upload (solo Administrador y Trabajador) -->
      <v-btn text @click="navigateTo('upload')" v-if="loggedInUser.role === 'Administrador' || loggedInUser.role === 'Trabajador'">
        <v-icon>mdi-file-upload</v-icon>
        <span>Archivos</span>
      </v-btn>
    
      <!-- Botón Usuarios (solo Administrador) -->
      <v-btn text @click="navigateTo('users')" v-if="loggedInUser.role === 'Administrador'">
        <v-icon>mdi-account-group</v-icon>
        <span>Usuarios</span>
      </v-btn>
    </v-bottom-navigation>
  </v-app>
</template>

<script>
import { ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import '@mdi/font/css/materialdesignicons.css'; // Ensure you are using css-loader

export default {
  props: {
      loggedInUser: Object, // Usuario logeado desde el backend
  },
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
  data: () => ({ 
    value: 1,
    profileModal: false,
    passwordChangeModal: false,
    profileFormValid: false, // Agregado para manejar la validación del formulario de perfil
    passwordFormValid: false, // Agregado para manejar la validación del formulario de cambio de contraseña
    profileUser: {},
    passwordChange: {
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
    },
  }),
  computed: {
    color () {
      switch (this.value) {
        case 0: return 'deep-purple-accent-4'
        case 1: return 'light-blue-darken-4'
        case 2: return 'deep-purple-accent-4'
        // case 3: return 'indigo'
        default: return 'deep-purple-accent-4'
      }
    },
  },
  methods: {
    openProfileModal() {
      // Cargar datos del usuario logeado en el modal
      if (this.loggedInUser) {
        this.profileUser = {
          name: this.loggedInUser.name,
          email: this.loggedInUser.email, // El correo será de solo lectura
        };
      }
      this.profileModal = true; // Abrir el modal
    },
    saveProfile() {
      // Validar y enviar los datos actualizados
      Inertia.post('/profile', {
        _method:'PUT',
        name: this.profileUser.name,
      }, {
        onSuccess: () => {
          this.profileModal = false;
          this.$emit('success', 'Perfil actualizado correctamente.');
        },
        onError: (error) => {
          console.error('Error al actualizar perfil:', error);
          alert('No se pudo actualizar el perfil. Intenta nuevamente.');
        },
      });
    },
    openPasswordChangeModal() {
      this.passwordChangeModal = true;
      this.passwordChange = {
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
      };
    },
    changePassword() {
      // Validar y enviar el cambio de contraseña
      Inertia.post('/change-password', {
        _method:"PUT",
        current_password: this.passwordChange.currentPassword,
        new_password: this.passwordChange.newPassword,
      }, {
        onSuccess: () => {
          this.passwordChangeModal = false;
          this.$emit('success', 'Contraseña actualizada correctamente.');
        },
        onError: (error) => {
          console.error('Error al cambiar contraseña:', error);
          alert('No se pudo cambiar la contraseña. Verifique su contraseña actual.');
        },
      });
    },
    logout() {
      Inertia.post('/logout');
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
.floating-user-btn {
  position: fixed;
  top: 16px;
  right: 16px;
  z-index: 1000;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
  background-color: #0F579B !important;
  color: #ffffff !important;
}
.floating-user-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 8px rgba(0,0,0,0.15);
}
.v-menu__content {
  border-radius: 12px !important;
  box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
}
/* .nav-bar {
  bottom: 4px !important;
  left: 20% !important;
  width: calc(60% + 0px) !important;
  border-radius: 20px;
  opacity: 0.9;
} */
</style>

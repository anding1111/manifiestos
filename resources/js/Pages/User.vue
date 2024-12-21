<template>
  <v-app>
    <v-container class="pa-6">
      <!-- Barra superior con opciones -->
        <v-menu location="bottom" origin="top right" transition="slide-y-transition">
          <template v-slot:activator="{ props }">
            <v-btn
              icon
              variant="elevated"
              size="large"
              color="primary"
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

      <!-- Tarjeta con tabla de usuarios -->
      <v-card elevation="3" rounded="lg">
        <v-card-title class="bg-primary text-white d-flex justify-space-between align-center pa-4">
          <span class="text-h5">Gestión de Usuarios</span>
          <v-btn color="white" variant="tonal" @click="openUserModal('create')">
            Agregar Nuevo Usuario
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-data-table
            :headers="headers"
            :items="users"
            density="comfortable"
          >
            <template v-slot:item.status="{ item }">
              <v-chip :color="getStatusColor(item.status)" size="small" label>
                {{ getStatusText(item.status) }}
              </v-chip>
            </template>
            <template v-slot:item.actions="{ item }">
              <v-btn icon variant="text" size="small" @click="openUserModal('edit', item)">
                <v-icon>mdi-pencil</v-icon>
              </v-btn>
              <v-btn
                icon
                variant="text"
                size="small"
                color="error"
                @click="confirmDeleteUser(item)"
              >
                <v-icon>mdi-delete</v-icon>
              </v-btn>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <!-- Modal Confirmación de Eliminación -->
      <v-dialog v-model="deleteDialog" max-width="400px">
        <v-card>
          <v-card-title class="text-h6">
            Confirmar Eliminación
          </v-card-title>
          <v-card-text>
            ¿Estás seguro de que deseas eliminar al usuario <strong>{{ userToDelete?.name }}</strong>? Esta acción no se puede deshacer.
          </v-card-text>
          <v-card-actions>
            <v-btn text color="grey" @click="deleteDialog = false">Cancelar</v-btn>
            <v-btn color="error" @click="deleteUser(userToDelete)">Eliminar</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Modal Usuario -->
      <v-dialog v-model="userModal" max-width="500px">
        <v-card>
          <v-card-title class="bg-primary text-white">
            {{ modalTitle }}
          </v-card-title>
          <v-card-text>
            <v-form ref="userForm" @submit.prevent="saveUser" v-model="formValid">
              <v-text-field
                v-model="currentUser.name"
                label="Nombre Completo"
                variant="outlined"
                required
              ></v-text-field>

              <v-text-field
                v-model="currentUser.email"
                label="Correo Electrónico"
                type="email"
                variant="outlined"
                :rules="validationRules.emailRules"
                required
              ></v-text-field>

              <v-text-field
                v-model="currentUser.password"
                label="Contraseña"
                type="password"
                variant="outlined"
                :rules="validationRules.passwordRules"
                required
              ></v-text-field>

              <v-select
                v-model="currentUser.role"
                :items="roles"
                label="Rol"
                variant="outlined"
                required
              ></v-select>

              <v-select
                v-model="currentUser.status"
                :items="statusOptions"
                item-title="label"
                item-value="value"
                label="Estado"
                variant="outlined"
                required
              ></v-select>

              <v-card-actions>
                <v-btn block color="primary" type="submit" :disabled="!formValid">
                  Guardar Usuario
                </v-btn>
              </v-card-actions>
            </v-form>
          </v-card-text>
        </v-card>
      </v-dialog>

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

    </v-container>
  </v-app>
</template>

<script>
import { Inertia } from '@inertiajs/inertia';
import '@mdi/font/css/materialdesignicons.css'; // Asegurarse de usar css-loader

export default {
  props: {
    users: Array, // Pasar usuarios desde el backend
    loggedInUser: Object, // Usuario logeado desde el backend
  },
  data() {
    return {
      userModal: false,
      modalMode: 'create',
      modalTitle: 'Agregar Nuevo Usuario',
      profileModal: false,
      passwordChangeModal: false,
      deleteDialog: false,
      userToDelete: null,
      currentUser: {
        name: '',
        email: '',
        role: 'Trabajador',
        password: '',
        status: null, // Guardará 1 o 2 según la selección
      },
      roles: ['Administrador', 'Trabajador', 'Cliente'],
      statusOptions: [
        { label: 'Activo', value: 1 },
        { label: 'Inactivo', value: 2 },
      ],
      profileUser: {},
      passwordChange: {
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
      },
      headers: [
        { text: 'Nombre', value: 'name' },
        { text: 'Correo', value: 'email' },
        { text: 'Rol', value: 'role' },
        { text: 'Estado', value: 'status' },
        { text: 'Acciones', value: 'actions', sortable: false },
      ],
      profileFormValid: false, // Agregado para manejar la validación del formulario de perfil
      passwordFormValid: false, // Agregado para manejar la validación del formulario de cambio de contraseña
      validationRules: {
        emailRules: [
          (v) => !!v || 'El correo electrónico es requerido',
          (v) => /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) || 'Ingrese un correo electrónico válido',
        ],
        passwordRules: [
          (v) => !!v || 'La contraseña es requerida',
          (v) => v.length >= 6 || 'La contraseña debe tener al menos 6 caracteres',
        ],
      },
      formValid: false,
    };
  },
  methods: {
    getStatusText(status) {
      switch (status) {
        case 1:
          return 'Activo';
        case 2:
          return 'Inactivo';
        case 3:
          return 'Eliminado';
        default:
          return 'Desconocido';
      }
    },
    getStatusColor(status) {
      switch (status) {
        case 1:
          return 'green';
        case 2:
          return 'grey';
        case 3:
          return 'red';
        default:
          return 'blue-grey';
      }
    },
    openUserModal(mode, user = {}) {
      this.modalMode = mode;
      this.modalTitle = mode === 'create' ? 'Agregar Nuevo Usuario' : 'Editar Usuario';
      this.currentUser = { ...user };
      this.userModal = true;
    },

    saveUser() {
      const url = this.modalMode === 'create' ? '/users' : `/users/${this.currentUser.id}`;
      const payload = { ...this.currentUser };

      // Si es una actualización, agrega _method para forzar el método PUT
      if (this.modalMode === 'edit') {
        payload._method = 'PUT';
      }

      Inertia.post(url, payload);
      this.userModal = false;
    },
    confirmDeleteUser(user) {
      this.userToDelete = user;
      this.deleteDialog = true;
    },
    deleteUser(user) {
      Inertia.post(`/users/${user.id}`, {
        id: user.id, // Incluye el ID en el payload
        _method: 'PUT', // Emular el método PUT
        status: 3, // Cambiar el estado a borrado
      });
      this.deleteDialog = false;
    },
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
};
</script>

<style scoped>

body { 
  background-color: #f0f2f5; 
  font-family: 'Roboto', sans-serif;
}
.v-container {
  max-width: 900px;
  margin: auto;
}
.floating-user-btn {
  position: fixed;
  top: 16px;
  right: 16px;
  z-index: 1000;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
}
.floating-user-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 8px rgba(0,0,0,0.15);
}
.v-menu__content {
  border-radius: 12px !important;
  box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
}
</style>

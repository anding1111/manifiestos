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
        <v-card-title class="text-white d-flex justify-space-between align-center pa-4 custom-title">
          <!-- <span class="text-h5">Usuarios</span>
          <v-spacer></v-spacer> -->
          <v-text-field
          v-model="search"
          density="compact"
          label="Buscar"
          prepend-inner-icon="mdi-magnify"
          variant="solo-filled"
          flat
          hide-details
          single-line
          ></v-text-field>
          <v-spacer></v-spacer>
          <v-btn color="white" variant="tonal" @click="openUserModal('create')">
            Agregar Nuevo Usuario
          </v-btn>
        </v-card-title>
        <v-card-text>
          <v-data-table
            v-model:search="search"
            :items-per-page="itemsPerPage"
            :items-per-page-options="itemsPerPageOptions"
            :filter-keys="['name', 'email']"
            :headers="headers"
            :items="users"
            density="comfortable"
            itemsPerPageText="Usuarios por página"
            height="300"
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
          <v-card-title class="custom-title text-white text-center">
            {{ modalTitle }}
          </v-card-title>
          <v-card-text>
            <v-form ref="userForm" @submit.prevent="saveUser" v-model="formValid">
              <v-text-field
                v-model="currentUser.name"
                label="Nombre Completo"
                variant="outlined"
                :rules="validationRules.nameRules"
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

    </v-container>
    
    <!-- Navegación inferior con botones para ir a la página de consulta y la de subida de archivos -->
    <v-bottom-navigation v-if="loggedInUser" v-model="value" :bg-color="color" mode="shift" class="nav-bar">
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
import { Inertia } from '@inertiajs/inertia';
import '@mdi/font/css/materialdesignicons.css'; // Asegurarse de usar css-loader

export default {
  setup() {
    // Función para navegar entre páginas
    const navigateTo = (route) => {
      Inertia.visit(route === 'home' ? '/' : `/${route}`);
    };
    return { navigateTo };
    console.log('Props recibidos desde el backend (Composition API):', props);

  },
  props: {
    users: Array, // Pasar usuarios desde el backend
    loggedInUser: Object, // Usuario logeado desde el backend
  },
  data() {
    return {
      search: '',
      itemsPerPage: 6,
      itemsPerPageOptions: [6, 12, 18, -1],
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
        role: 'Cliente', // Valor por defecto
        status: 1, // Valor por defecto (Activo)
        password: '',
        // status: null, // Guardará 1 o 2 según la selección
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
        {
          title: 'Nombre',
          sortable: true,
          key: 'name',
        },
        {
          title: 'Correo',
          sortable: true,
          key: 'email',
        },
        {
          title: 'Rol',
          sortable: true,
          key: 'role',
        },
        {
          title: 'Estado',
          align: 'center',
          sortable: true,
          key: 'status',
        },
        {
          title: 'Acciones',
          align: 'center',
          sortable: false,
          key: 'actions',
        },
      ],
      profileFormValid: false, // Agregado para manejar la validación del formulario de perfil
      passwordFormValid: false, // Agregado para manejar la validación del formulario de cambio de contraseña
      validationRules: {
        nameRules: [
          (v) => !!v || 'El nombre es requerido',
        ],
        emailRules: [
          (v) => !!v || 'El correo electrónico es requerido',
          (v) => /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) || 'Ingrese un correo electrónico válido',
        ],
        passwordRules: [
          (v) => !!v || 'La contraseña es requerida',
          (v) => v.length >= 6 || 'La contraseña debe tener al menos 6 caracteres',
        ],
        passwordRules: [
          v => this.modalMode === 'create' ? !!v || 'La contraseña es obligatoria' : true,
          v => this.modalMode === 'create' ? v.length >= 6 || 'La contraseña debe tener al menos 6 caracteres' : true,
        ],
      },
      formValid: false,
      value: 2,
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

      if (mode === 'create') {
        // Establecer valores por defecto para un nuevo usuario
        this.currentUser = {
          name: '',
          email: '',
          role: 'Cliente', // Valor por defecto
          status: 1, // Valor por defecto (Activo)
          password: '',
        };
      } else if (mode === 'edit') {
        // Cargar los datos del usuario a editar
        this.currentUser = { ...user, password: '' }; // Mantener contraseña vacía en edición
      }

      this.userModal = true;
    },

    saveUser() {
      const url = this.modalMode === 'create' ? '/users' : `/users/${this.currentUser.id}`;
      const payload = {
        name: this.currentUser.name,
        email: this.currentUser.email,
        role: this.currentUser.role,
        status: this.currentUser.status,
      };

      // Solo agregar contraseña si está presente (en edición)
      if (this.modalMode === 'create' || this.currentUser.password) {
        payload.password = this.currentUser.password;
      }

      Inertia.post(url, {
        ...payload,
        _method: this.modalMode === 'create' ? 'POST' : 'PUT',
      }, {
        onSuccess: () => {
          this.userModal = false;
          this.$emit('success', 'Usuario guardado correctamente.');
        },
        onError: (error) => {
          console.error('Error al guardar el usuario:', error);
          alert('No se pudo guardar el usuario. Intenta nuevamente.');
        },
      });
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
  computed: {
    color () {
      switch (this.value) {
        case 0: return 'deep-purple-accent-4'
        case 1: return 'light-blue-darken-4'
        case 2: return 'deep-purple-accent-4'
        default: return 'deep-purple-accent-4'
      }
    },
    isAuthenticated() {
      return this.$page.props.auth.user !== null; // Verifica si el usuario está autenticado
    },
  },
};
</script>

<style scoped>

body { 
  background-color: #f0f2f5; 
  font-family: 'Roboto', sans-serif;
}
.custom-title {
  background-color: #674AEE !important;
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
  background-color: #674AEE !important;
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
</style>

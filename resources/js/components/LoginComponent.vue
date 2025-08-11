<template>
  <div class="login-container">
    <form @submit.prevent="fazerLogin" class="login-form">
      <img
        src="/public/images/logo_arandu.png"
        alt="Logo Arandu"
        style="height: 120px; width: 120px; padding: 1em; margin: 0 auto; border-radius: 50%; border: 1px solid #c0c0c0; background-color: #FFF;"
      />
      <h2 style="text-align: center;">Login</h2>
      <div style="text-align: center; font-style: italic;">Use suas credenciais do Sophia</div>
      <div>
        <label for="usuario">Usuário:</label>
        <input id="usuario" v-model="usuario" type="text" required autocomplete="username" />
      </div>
      <div>
        <label for="senha">Senha:</label>
        <input id="senha" v-model="senha" type="password" required autocomplete="current-password" />
      </div>
      <button type="submit">Entrar</button>
      <div v-if="erro" class="erro-login">{{ erro }}</div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { defineStore } from 'pinia'

const usuario = ref('')
const senha = ref('')
const erro = ref('')

const emit = defineEmits(['login-sucesso'])
const auth = useAuthStore()

async function fazerLogin() {
  erro.value = ''
  if (usuario.value === 'paco' && senha.value === '123') {
    // Obtenha o token do backend
    const resp = await axios.get('/api/token-sophia')
    console.log(resp.data)
    const token = resp.data.token
    auth.setToken(token) // Isso já salva no localStorage
    emit('login-sucesso')
  } else {
    erro.value = 'Usuário ou senha inválidos.'
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
}
.login-form {
  background: #fff;
  padding: 2em 2.5em;
  border-radius: 10px;
  box-shadow: 0 4px 24px #0002;
  display: flex;
  flex-direction: column;
  gap: 1em;
  min-width: 300px;
}
.login-form label {
  font-weight: bold;
  color: #4f6c33;
}
.login-form input {
  padding: 0.5em;
  border-radius: 4px;
  border: 1px solid #d0d7e2;
  font-size: 1em;
  width: 100%;
}
.login-form button {
  background: #4f6c33;
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 0.7em 2em;
  font-size: 1em;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.2s;
}
.erro-login {
  color: #73331d;
  font-weight: bold;
  margin-top: 0.5em;
}
</style>
<template>
  <div class="login-bg-overlay">
    <form @submit.prevent="tokenEnviado ? autenticarComOTP() : enviarMagicLink()" class="login-form">
      <img class="login-logo" src="/public/images/logo_arandu.png" alt="Logo Arandu" />
      <h2 class="login-title">Login</h2>
      <div class="campo-email">
        <label for="email">E-mail</label>
        <div class="input-wrapper">
          <input
            type="email"
            id="email"
            v-model="email"
            placeholder="exemplo@email.com.br"
            :disabled="tokenEnviado"
            required
          />
          <span class="icone">&#9993;</span>
        </div>
      </div>
      <!-- Campo OTP aparece após envio do token -->
      <div v-if="tokenEnviado" class="campo-otp">
        <label for="otp">Token recebido</label>
        <div class="input-wrapper">
          <input
            type="text"
            id="otp"
            v-model="otp"
            maxlength="6"
            placeholder="Digite o token"
            required
          />
        </div>
      </div>
      <div class="login-btn-container">
        <button type="submit">{{ tokenEnviado ? 'Validar Token' : 'Entrar' }}</button>
      </div>
      <div v-if="erro" class="erro-login">{{ erro }}</div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'

const email = ref('')
const erro = ref('')
const tokenEnviado = ref(false)
const otp = ref('')
const emit = defineEmits(['login-sucesso'])
const auth = useAuthStore()

async function enviarMagicLink() {
  erro.value = ''
  if (!email.value) {
    erro.value = 'Informe um e-mail válido.'
    return
  }
  try {
    await axios.post('/api/auth/send-magic-link', { email: email.value })
    tokenEnviado.value = true
  } catch (e) {
    erro.value = 'Erro ao enviar o link/token. Tente novamente.'
  }
}

async function autenticarComOTP() {
  erro.value = ''
  try {
    const resp = await axios.post('/api/auth/verify-otp', { email: email.value, otp: otp.value })
    // Exemplo: resp.data.perfil, resp.data.token
    sessionStorage.setItem('perfil', resp.data.perfil)
    auth.setToken(resp.data.token)
    emit('login-sucesso')
  } catch (e) {
    erro.value = 'Token inválido ou expirado.'
  }
}
</script>

<style scoped>
.login-bg-overlay {
  max-height: 100vh;
  max-width: 100vw;
  display: flex;
  flex-direction: row;
}
.central-container {
  width: 50vw;
  min-height: 100vh;
  margin: 0 auto;
  background: #fff;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 0;
  box-shadow: none;
}
.login-right {
  width: 50vw;
  min-height: 100vh;
}
.login-form {
  background: #fff;
  padding: 2em 2.5em;
  border-radius: 0;
  box-shadow: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1em;
  min-width: 300px;
  width: 100%;
}
.login-title {
  text-align: center;
  color: #b91509;
  margin: 8vh 0 0.5em 0;
}
.login-field {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 70%;
  margin: 0 auto;
}
.login-form label {
  font-weight: bold;
  color: #4f6c33;
  margin-bottom: 0.2em;
}
.login-form input {
  padding: 0.4em;
  border-radius: 4px;
  border: 1px solid #d0d7e2;
  font-size: 0.95em;
  width: 140px;
  text-align: center;
}
.login-btn-container {
  display: flex;
  justify-content: center;
  width: 300px;
  margin-top: 1rem;
}
.login-form button {
  background: #b91509;
  color: #fff;
  border: none;
  border-radius: 4px;
  padding: 1em 2em;
  font-size: 1em;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.2s;
  margin: 0 auto;
  display: block;
  width: 100%;
  min-width: 140px;
  max-width: 300px;
}
.login-form button:hover {
  background: #ff2415;
}
.erro-login {
  color: #73331d;
  font-weight: bold;
  margin-top: 0.5em;
  text-align: center;
}

.login-logo {
  height: 120px;
  width: 120px;
  padding: 1em;
  margin: 3rem auto 0 auto;
  border-radius: 50%;
  border: 1px solid #c0c0c0;
  background-color: #FFF;
}

.campo-email,
.campo-senha {
  display: flex;
  flex-direction: column;
  align-items: center; /* centraliza os campos */
  width: 300px;
}

.input-wrapper {
  width: 100%;
  display: flex;
  align-items: center;
  position: relative;
}

.campo-email input[type="email"],
.campo-senha input[type="password"] {
  width: 100%;
  box-sizing: border-box;
  text-align: center;
  height: 3rem;
  padding: 10px;
  padding-right: 1rem; /* espaço pro ícone */
  border: 1px solid #b91509;
  border-radius: 4px;
  font-size: 16px;
  outline: none;
  color: #b91509;
}

.login-btn-container {
  display: flex;
  justify-content: center;
  width: 300px;
}

.login-form button {
  width: 100%;
  min-width: 140px;
  max-width: 300px;
  margin: 0 auto;
  display: block;
}

/* Estilo do rótulo */
.campo-email label,
.campo-senha label {
  font-family: Tahoma, sans-serif;
  width: 4rem;
  font-size: 14px;
  color: #b91509;
  margin: 0 0 -0.6rem 1rem;
  z-index: 1;
  background-color: #fff;
  text-align: center;
}

/* Wrapper para o input e o ícone */
.input-wrapper {
  position: relative;
}

.campo-email input[type="email"]:focus,
.campo-senha input[type="password"]:focus {
  border-color: #ff8279;
}

.icone {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 20px;
  color: #b91509;
  background-color: #fff;
  padding-left: 0.4em;
}

#rodape{
  position: absolute;
  bottom: 0;
}

.campo-otp {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 300px;
  margin-top: 1em;
}
.campo-otp label {
  font-family: Tahoma, sans-serif;
  width: 8rem;
  font-size: 14px;
  color: #b91509;
  margin: 0 0 -0.6rem 1rem;
  z-index: 1;
  background-color: #fff;
  text-align: center;
}
.campo-otp .input-wrapper {
  width: 100%;
  position: relative;
}
.campo-otp input[type="text"] {
  width: 100%;
  box-sizing: border-box;
  text-align: center;
  height: 3rem;
  padding: 10px;
  border: 1px solid #b91509;
  border-radius: 4px;
  font-size: 16px;
  outline: none;
  color: #b91509;
}
</style>
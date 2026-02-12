<template>
  <div class="login-bg-overlay">
    <!-- Formulário de login -->
    <form
      v-if="!exibirCadastroSenha"
      @submit.prevent="tokenEnviado ? autenticarComSenha() : verificarEmail()"
      class="login-form"
    >
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
      <!-- Campo senha aparece após verificação do email -->
      <div v-if="tokenEnviado" class="campo-senha">
        <label for="senha">Senha</label>
        <div class="input-wrapper">
          <input
            :type="senhaVisivel ? 'text' : 'password'"
            id="senha"
            v-model="senha"
            placeholder="Digite sua senha"
            required
          />
          <span
            class="icone senha-toggle"
            @click="senhaVisivel = !senhaVisivel"
            :title="senhaVisivel ? 'Ocultar senha' : 'Mostrar senha'"
            style="cursor:pointer;"
          >
            <i :class="senhaVisivel ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
          </span>
        </div>
      </div>
      <div v-if="tokenEnviado" class="link-primeiro-acesso">
        <a href="#" @click.prevent="mostrarCadastroSenha">
          É seu primeiro acesso ou deseja trocar a sua senha?
        </a>
      </div>
      <div class="login-btn-container">
        <button type="submit" :disabled="realizandoLogin || enviandoToken">
          {{ tokenEnviado ? 'Entrar' : 'Avançar' }}
        </button>
      </div>
      <div v-if="erro" class="erro-login">
        {{ erro === 'E-mail não verificado.' ? 'Seu cadastro não foi finalizado. Por favor verifique o seu email para finalizar o processo.' : erro }}
      </div>
      <div v-if="erro === 'E-mail não verificado.'" style="text-align:center; margin-top:0.5em;">
        <a href="#" @click.prevent="reenviarEmailConfirmacao">Reenviar e-mail de confirmação</a>
      </div>
      <div v-if="enviandoToken" style="text-align:center; color:#b91509; margin-top:0.5em;">
        Verificando e-mail...
      </div>
      <div v-if="realizandoLogin" style="text-align:center; color:#b91509; margin-top:0.5em;">
        <span class="spinner" style="margin-right:0.5em;"></span>Realizando login...
      </div>
      <div v-if="mensagemLogin" class="sucesso-login">{{ mensagemLogin }}</div>
    </form>

    <!-- Formulário de cadastro de senha -->
    <div v-else class="cadastro-senha-form">
      <h3>Definir nova senha</h3>
      <form class="form-senha" @submit.prevent="cadastrarSenha">
        <div>
          <label>E-mail</label>
          <input type="email" :value="email" disabled />
        </div>
        <div>
          <label>Nova senha</label>
          <div class="input-wrapper">
            <input
              :type="novaSenhaVisivel ? 'text' : 'password'"
              v-model="novaSenha"
              required
            />
            <span
              class="icone senha-toggle"
              @click="novaSenhaVisivel = !novaSenhaVisivel"
              :title="novaSenhaVisivel ? 'Ocultar senha' : 'Mostrar senha'"
              style="cursor:pointer;"
            >
              <i :class="novaSenhaVisivel ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
            </span>
          </div>
        </div>
        <div>
          <label>Confirme a senha</label>
          <div class="input-wrapper">
            <input
              :type="confirmaSenhaVisivel ? 'text' : 'password'"
              v-model="confirmaSenha"
              required
            />
            <span
              class="icone senha-toggle"
              @click="confirmaSenhaVisivel = !confirmaSenhaVisivel"
              :title="confirmaSenhaVisivel ? 'Ocultar senha' : 'Mostrar senha'"
              style="cursor:pointer;"
            >
              <i :class="confirmaSenhaVisivel ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
            </span>
          </div>
        </div>
        <button type="submit" :disabled="salvandoSenha">Salvar senha</button>
        <div v-if="erroSenha" class="erro-login">{{ erroSenha }}</div>
        <div v-if="sucessoSenha" class="sucesso-login">{{ sucessoSenha }}</div>
        <div v-if="mensagemLogin" class="sucesso-login">{{ mensagemLogin }}</div>
      </form>
      <div class="voltar-login">
        <a href="#" @click.prevent="exibirCadastroSenha = false">Voltar para o login</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'

const email = ref('')
const senha = ref('')
const erro = ref('')
const tokenEnviado = ref(false) // agora indica se pode mostrar o campo senha
const enviandoToken = ref(false)
const realizandoLogin = ref(false)
const emit = defineEmits(['login-sucesso', 'primeiro-acesso'])
const auth = useAuthStore()

const exibirCadastroSenha = ref(false)
const novaSenha = ref('')
const confirmaSenha = ref('')
const erroSenha = ref('')
const sucessoSenha = ref('')
const salvandoSenha = ref(false)
const senhaVisivel = ref(false)
const novaSenhaVisivel = ref(false)
const confirmaSenhaVisivel = ref(false)
const mensagemLogin = ref('')

async function verificarEmail() {
  erro.value = ''
  if (!email.value) {
    erro.value = 'Informe um e-mail válido.'
    return
  }
  enviandoToken.value = true
  try {
    // Verifica se o email pertence a colaborador ou aluno
    await axios.post('/api/checar-email', { email: email.value })
    tokenEnviado.value = true // Mostra o campo de senha
  } catch (e) {
    if (e.response && e.response.status === 404 && e.response.data && e.response.data.message) {
      erro.value = e.response.data.message
    } else {
      erro.value = 'Erro ao verificar o e-mail. Tente novamente.'
    }
  }
  enviandoToken.value = false
}

async function autenticarComSenha() {
  try {
    const resp = await axios.post('/api/auth/login', { email: email.value, senha: senha.value })
    
    // ORDEM IMPORTANTE!
    // 1. Salve primeiro no sessionStorage
    sessionStorage.setItem('token', resp.data.token)
    sessionStorage.setItem('perfil', resp.data.perfil)
    sessionStorage.setItem('email', email.value)
    
    // Se o backend retornar tokenSophia (perfil Desenvolvedor), salve também
    if (resp.data.tokenSophia) {
      sessionStorage.setItem('tokenSophia', resp.data.tokenSophia)
    }
    
    // 2. Depois atualize o store
    auth.setToken(resp.data.token)
    
    // 3. Finalmente, emita o evento
    emit('login-sucesso')
  } catch (e) {
    if (e.response && e.response.data && e.response.data.message) {
      erro.value = e.response.data.message
    } else {
      erro.value = 'Problema ao realizar login. Verifique suas credenciais.'
    }
  }
}

// Chame isso ao clicar no link de primeiro acesso
function mostrarCadastroSenha() {
  exibirCadastroSenha.value = true
  erroSenha.value = ''
  sucessoSenha.value = ''
  novaSenha.value = ''
  confirmaSenha.value = ''
}

async function cadastrarSenha() {
  erroSenha.value = ''
  sucessoSenha.value = ''
  if (!novaSenha.value || novaSenha.value.length < 8) {
    erroSenha.value = 'A senha deve ter no mínimo 8 caracteres.'
    return
  }
  if (novaSenha.value !== confirmaSenha.value) {
    erroSenha.value = 'As senhas não coincidem.'
    return
  }
  salvandoSenha.value = true
  try {
    await axios.post('/api/definir-senha', {
      email: email.value,
      senha: novaSenha.value
    })
    // Mostra mensagem no formulário de login
    mensagemLogin.value = 'Senha alterada com sucesso. Verifique seu email para continuar.'
    exibirCadastroSenha.value = false
    // Limpa campos de senha
    novaSenha.value = ''
    confirmaSenha.value = ''
  } catch (e) {
    erroSenha.value = e.response?.data?.message || 'Erro ao cadastrar senha.'
  }
  salvandoSenha.value = false
}

async function reenviarEmailConfirmacao() {
  erro.value = ''
  try {
    await axios.post('/api/definir-senha', { email: email.value, senha: 'tempSenha1234' })
    mensagemLogin.value = 'E-mail de confirmação reenviado. Verifique sua caixa de entrada.'
  } catch (e) {
    erro.value = e.response?.data?.message || 'Erro ao reenviar e-mail de confirmação.'
  }
}
</script>
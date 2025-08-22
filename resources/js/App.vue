<template>
  <div id="container-geral" class="central-container">
    <template v-if="autenticado">
      <nav style="margin-bottom: 2em;">
        <button
          :class="{ ativo: paginaAtual === 'inicio' }"
          @click="paginaAtual = 'inicio'"
        >
          Início
        </button>
        <button
          v-if="perfilLogado !== 'Aluno'"
          :class="{ ativo: paginaAtual === 'objetivos' }"
          @click="paginaAtual = 'objetivos'"
        >
          Objetivos
        </button>
        <button
          :class="{ ativo: paginaAtual === 'avaliacao' }"
          @click="paginaAtual = 'avaliacao'"
        >
          Avaliação
        </button>
        <button @click="logout" class="logout-button">Sair</button>
      </nav>
      <div style="margin-bottom: 1em; font-weight: bold; color: #4f6c33;">
        Perfil logado: {{ perfilLogado }}
      </div>
      <div v-if="paginaAtual === 'inicio'">
        <div
          style="
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 2em;
          "
        >
          <img
            src="/public/images/logo_arandu.png"
            alt="Logo Arandu"
            style="height: 160px; padding: 1em; margin-bottom: 1em; border-radius: 50%; border: 1px solid #c0c0c0; background-color: #FFF;"
          />
          <h1>Bem-vindo ao sistema Arandu!</h1>
          <p>Escolha uma opção no menu acima.</p>
        </div>
      </div>
      <div v-else-if="paginaAtual === 'objetivos'">
        <ObjetivoComponent />
      </div>
      <div v-else-if="paginaAtual === 'avaliacao'">
        <AvaliacaoComponent />
      </div>
    </template>
    <template v-else>
      <LoginComponent @login-sucesso="onLoginSucesso" />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import AlunoComponent from './components/AlunoComponent.vue'
import TurmaComponent from './components/TurmaComponent.vue'
import MateriaComponent from './components/MateriaComponent.vue'
import AvaliacaoComponent from './components/AvaliacaoComponent.vue'
import ObjetivoComponent from './components/ObjetivoComponent.vue'
import LoginComponent from './components/LoginComponent.vue'
import axios from 'axios'

const auth = useAuthStore()

const paginaAtual = ref(localStorage.getItem('paginaAtual') || 'inicio')
const autenticado = computed(() => !!auth.token)

const perfilLogado = ref(sessionStorage.getItem('perfil') || 'Não definido')

// Salva a página sempre que mudar
watch(paginaAtual, (novaPagina) => {
  localStorage.setItem('paginaAtual', novaPagina)
})

// Verificação do token ao montar o App
onMounted(async () => {
  if (auth.token) {
    try {
      // Tenta acessar um endpoint protegido para validar o token
      await axios.get('https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Unidades=2', {
        headers: { Token: auth.token }
      })
      // Se não der erro, segue normalmente
    } catch (e) {
      // Se der erro (ex: 401), faz logout e volta para login
      logout()
    }
  }
  // Atualiza o perfil ao recarregar
  perfilLogado.value = sessionStorage.getItem('perfil') || 'Não definido'
})

function logout() {
  auth.logout()
  localStorage.removeItem('paginaAtual')
  sessionStorage.removeItem('perfil')
  window.location.reload()
}

// Atualize ao receber o evento de login-sucesso
function onLoginSucesso() {
  autenticado.value = true
  perfilLogado.value = sessionStorage.getItem('perfil') || 'Não definido'
}
</script>
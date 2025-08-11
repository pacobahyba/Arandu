<template>
  <div class="central-container">
    <template v-if="autenticado">
      <nav style="margin-bottom: 2em;">
        <button
          :class="{ ativo: paginaAtual === 'inicio' }"
          @click="paginaAtual = 'inicio'"
        >
          Início
        </button>
        <button
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
      <LoginComponent @login-sucesso="autenticado = true" />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from './stores/auth'
import AlunoComponent from './components/AlunoComponent.vue'
import TurmaComponent from './components/TurmaComponent.vue'
import MateriaComponent from './components/MateriaComponent.vue'
import AvaliacaoComponent from './components/AvaliacaoComponent.vue'
import ObjetivoComponent from './components/ObjetivoComponent.vue'
import LoginComponent from './components/LoginComponent.vue'

const auth = useAuthStore()

// Inicializa a página atual com o valor salvo ou 'inicio'
const paginaAtual = ref(localStorage.getItem('paginaAtual') || 'inicio')
const autenticado = computed(() => !!auth.token)

// Salva a página sempre que mudar
watch(paginaAtual, (novaPagina) => {
  localStorage.setItem('paginaAtual', novaPagina)
})

function logout() {
  auth.logout()
  localStorage.removeItem('paginaAtual')
  window.location.reload() // ou, se preferir, defina autenticado = false
}
</script>
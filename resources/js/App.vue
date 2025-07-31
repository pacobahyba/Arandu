<template>
  <nav style="margin-bottom: 2em;">
    <button
      :class="{ ativo: paginaAtual === 'inicio' }"
      @click="paginaAtual = 'inicio'"
    >
      Início
    </button>
    <button
      :class="{ ativo: paginaAtual === 'alunos' }"
      @click="paginaAtual = 'alunos'"
    >
      Alunos
    </button>
    <button
      :class="{ ativo: paginaAtual === 'turmas' }"
      @click="paginaAtual = 'turmas'"
    >
      Turmas
    </button>
    <button
      :class="{ ativo: paginaAtual === 'materias' }"
      @click="paginaAtual = 'materias'"
    >
      Matérias
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
  </nav>
  <div v-if="paginaAtual === 'inicio'">
    <h1>Bem-vindo ao sistema Arandu!</h1>
    <p>Escolha uma opção no menu acima.</p>
  </div>
  <div v-else-if="paginaAtual === 'alunos'">
    <AlunoComponent />
  </div>
  <div v-else-if="paginaAtual === 'turmas'">
    <TurmaComponent />
  </div>
  <div v-else-if="paginaAtual === 'materias'">
    <MateriaComponent />
  </div>
  <div v-else-if="paginaAtual === 'objetivos'">
    <ObjetivoComponent />
  </div>
  <div v-else-if="paginaAtual === 'avaliacao'">
    <AvaliacaoComponent />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AlunoComponent from './components/AlunoComponent.vue'
import TurmaComponent from './components/TurmaComponent.vue'
import MateriaComponent from './components/MateriaComponent.vue'
import AvaliacaoComponent from './components/AvaliacaoComponent.vue'
import ObjetivoComponent from './components/ObjetivoComponent.vue'

const paginaAtual = ref('inicio')
</script>

<style scoped>
nav {
  display: flex;
  gap: 1em;
}
button {
  padding: 0.5em 1.5em;
  border: 1px solid #ccc;
  background: #f7f7f7;
  cursor: pointer;
  font-weight: bold;
}
button.ativo {
  background: #42b883;
  color: #fff;
  border-color: #42b883;
}
</style>
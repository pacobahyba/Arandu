<template>
  <div>
    <h2>Selecione uma turma</h2>
    <select v-model="turmaSelecionada" @change="carregarAlunos">
      <option value="">Selecione...</option>
      <option v-for="turma in turmas" :key="turma.id" :value="turma.id">
        {{ turma.descricao }} - {{ turma.nome }}
      </option>
    </select>

    <div v-if="turmaSelecionada" id="alunos-da-turma">
      <h2>Alunos da turma</h2>
      <table border="1" cellpadding="5">
        <thead>
          <tr>
            <th>Matrícula</th>
            <th>Nome</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="aluno in alunos" :key="aluno.id">
            <td>{{ aluno.matricula }}</td>
            <td>{{ aluno.nome_aluno }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const turmas = ref([])
const turmaSelecionada = ref('')
const alunos = ref([{ nome: '' }])

async function carregarTurmas() {
  try {
    const response = await axios.get('/api/turmas')
    turmas.value = response.data
  } catch (e) {
    turmas.value = []
  }
}

async function carregarAlunos() {
  if (!turmaSelecionada.value) {
    alunos.value = [{ nome: '' }]
    return
  }
  try {
    const response = await axios.get(`/api/alunos?turma_id=${turmaSelecionada.value}`)
    alunos.value = response.data.length ? response.data : [{ nome: '' }]
  } catch (e) {
    alunos.value = [{ nome: '' }]
  }
}

onMounted(carregarTurmas)
</script>

<style scoped>
table {
  margin-top: 1em;
  border-collapse: collapse;
}
th, td {
  padding: 0.5em 1em;
  text-align: center;
}
input[type="text"] {
  width: 120px;
  padding: 0.2em;
}
button {
  margin: 0.2em;
}
</style>

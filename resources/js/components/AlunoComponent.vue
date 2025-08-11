<template>
  <div>
    <h2>Selecione uma turma</h2>
    <select v-model="turmaSelecionada" @change="carregarAlunos">
      <option value="">Selecione...</option>
      <option v-for="turma in turmas" :key="turma.id" :value="turma.id">
        {{ turma.curso.descricao }} - {{ turma.nome }}
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
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

const turmas = ref([])
const turmaSelecionada = ref('')
const alunos = ref([{ nome: '' }])

async function carregarTurmas() {
  try {
    const response = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Unidades=2',
      {
        headers: {
          Token: auth.token // Use o token obtido no login
        }
      }
    )
    // Ordena: primeiro "Ensino Fundamental", depois "Ensino Médio"
    turmas.value = response.data.sort((a, b) => {
      const aFund = a.curso?.descricao?.includes('Ensino Fundamental') ? 0 : 1
      const bFund = b.curso?.descricao?.includes('Ensino Fundamental') ? 0 : 1
      if (aFund !== bFund) return aFund - bFund
      // Se ambos são do mesmo tipo, mantém a ordem original ou ordena por nome
      return a.nome.localeCompare(b.nome)
    })
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


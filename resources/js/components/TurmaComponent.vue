<template>
  <div>
    <h2>Turmas</h2>
    <ul>
      <li v-for="turma in turmasSalvas" :key="turma.id">
        ({{ turma.id }}) ({{ turma.idanos }}) {{ turma.descricao }} - {{ turma.nome }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const turmasSalvas = ref([])

async function carregarTurmasSalvas() {
  try {
    const response = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2',
      {
        headers: {
          Authorization: `Bearer ${auth.token}`
        }
      }
    )
    turmasSalvas.value = response.data
  } catch (e) {
    turmasSalvas.value = []
  }
}

onMounted(() => {
  carregarTurmasSalvas()
})
</script>
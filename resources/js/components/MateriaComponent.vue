<template>
  <div>
    <h2>Lista de Matérias</h2>
    <table border="1" cellpadding="5">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>Abreviação</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="materia in materias" :key="materia.id">
          <td>{{ materia.id_materia }}</td>
          <td>{{ materia.nome_materia }}</td>
          <td>{{ materia.abreviacao_materia }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const materias = ref([])

async function carregarMaterias() {
  try {
    const response = await axios.get('/api/materias')
    materias.value = response.data
  } catch (e) {
    materias.value = []
  }
}

onMounted(carregarMaterias)
</script>
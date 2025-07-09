<template>
  <div>
    <h2>Cadastro de Turmas</h2>
    <table border="1" cellpadding="5">
      <thead>
        <tr>
          <th>#</th>
          <th>Ano</th>
          <th>Nome da turma</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(turma, idx) in turmas" :key="idx">
          <td>{{ idx + 1 }}</td>
          <td>
            <select v-model="turma.ano">
              <option value="">Selecione...</option>
              <option value="6F">6º Fundamental</option>
              <option value="7F">7º Fundamental</option>
              <option value="8F">8º Fundamental</option>
              <option value="9F">9º Fundamental</option>
              <option value="1M">1º - Médio</option>
              <option value="2M">2º - Médio</option>
              <option value="3M">3º - Médio</option>
            </select>
          </td>
          <td><input v-model="turma.nome" type="text" /></td>
          <td>
            <button @click="removerTurma(idx)" :disabled="turmas.length === 1">Remover</button>
          </td>
        </tr>
      </tbody>
    </table>
    <button @click="adicionarTurma">Adicionar Turma</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const turmas = ref([
  { ano: '', nome: '' }
])

function adicionarTurma() {
  turmas.value.push({ ano: '', nome: '' })
}

function removerTurma(idx) {
  if (turmas.value.length > 1) turmas.value.splice(idx, 1)
}
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
input[type="text"], input[type="number"], select {
  width: 120px;
  padding: 0.2em;
}
button {
  margin: 0.2em;
}
</style>

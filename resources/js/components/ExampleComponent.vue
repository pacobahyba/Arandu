<template>
  <div>
    <h2>Tabela de Alunos</h2>
    <table border="1" cellpadding="5">
      <thead>
        <tr>
          <th>Número</th>
          <th>Nome</th>
          <th>Nota do Estudante</th>
          <th>Nota do Professor</th>
          <th>CP</th>
          <th>CF</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(aluno, idx) in alunos" :key="idx">
          <td>{{ idx + 1 }}</td>
          <td><input v-model="aluno.nome" type="text" /></td>
          <td><input v-model.number="aluno.notaEstudante" type="number" min="0" max="100" /></td>
          <td><input v-model.number="aluno.notaProfessor" type="number" min="0" max="100" /></td>
          <td><input :value="aluno.cp" type="text" readonly /></td>
          <td><input :value="aluno.cf" type="text" readonly /></td>
          <td>
            <button @click="removerAluno(idx)" :disabled="alunos.length === 1">Remover</button>
          </td>
        </tr>
      </tbody>
    </table>
    <button @click="adicionarAluno">Adicionar Aluno</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const alunos = ref([
  { nome: '', notaEstudante: '', notaProfessor: '', cp: '', cf: '' }
])

function adicionarAluno() {
  alunos.value.push({ nome: '', notaEstudante: '', notaProfessor: '', cp: '', cf: '' })
}

function removerAluno(idx) {
  if (alunos.value.length > 1) alunos.value.splice(idx, 1)
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
input[type="text"], input[type="number"] {
  width: 120px;
  padding: 0.2em;
}
button {
  margin: 0.2em;
}
</style>

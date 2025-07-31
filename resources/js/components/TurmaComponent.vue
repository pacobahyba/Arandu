<template>
  <div>
    <h2>Turmas Salvas</h2>
    <ul>
      <li v-for="turma in turmasSalvas" :key="turma.id">
        ({{ turma.id }}) ({{ turma.idanos }}) {{ turma.descricao }} - {{ turma.nome }}
        <button @click="iniciarEdicao(turma)">Editar</button>
      </li>
    </ul>
    <h2>Cadastro de Turmas</h2>
    <table border="1" cellpadding="5">
      <thead>
        <tr>
          <th>Ano</th>
          <th>Nome da turma</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <select v-model="turmas[0].ano">
              <option value="">Selecione...</option>
              <option v-for="ano in anos" :key="ano.idanos" :value="ano.idanos">
                {{ ano.descricao }}
              </option>
            </select>
          </td>
          <td><input v-model="turmas[0].nome" type="text" /></td>
          <td>
            <button v-if="!editando" @click="salvarTurma(turmas[0])">Salvar</button>
            <button v-else @click="atualizarTurma">Atualizar</button>
            <button v-if="editando" @click="cancelarEdicao">Cancelar</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const turmas = ref([
  { ano: '', nome: '' }
])
const turmasSalvas = ref([])
const anos = ref([])
const editando = ref(false)
const turmaEditandoId = ref(null)

async function salvarTurma(turma) {
  try {
    await axios.post('/api/turmas', turma)
    alert('Turma salva com sucesso!')
    await carregarTurmasSalvas()
    turmas.value[0].ano = ''
    turmas.value[0].nome = ''
  } catch (e) {
    alert('Erro ao salvar turma!')
  }
}

function iniciarEdicao(turma) {
  turmas.value[0].id = turma.id
  turmas.value[0].ano = turma.idanos
  turmas.value[0].nome = turma.nome
  turmaEditandoId.value = turma.id
  editando.value = true
}

async function atualizarTurma() {
  try {
    await axios.put(`/api/turmas/${turmaEditandoId.value}`, turmas.value[0])
    alert('Turma atualizada com sucesso!')
    await carregarTurmasSalvas()
    turmas.value[0].ano = ''
    turmas.value[0].nome = ''
    editando.value = false
    turmaEditandoId.value = null
  } catch (e) {
    alert('Erro ao atualizar turma!')
  }
}

function cancelarEdicao() {
  turmas.value[0].ano = ''
  turmas.value[0].nome = ''
  editando.value = false
  turmaEditandoId.value = null
}

async function carregarTurmasSalvas() {
  try {
    const response = await axios.get('/api/turmas')
    turmasSalvas.value = response.data
  } catch (e) {
    turmasSalvas.value = []
  }
}

async function carregarAnos() {
  try {
    const response = await axios.get('/api/anos')
    anos.value = response.data
  } catch (e) {
    anos.value = []
  }
}

onMounted(() => {
  carregarTurmasSalvas()
  carregarAnos()
})
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

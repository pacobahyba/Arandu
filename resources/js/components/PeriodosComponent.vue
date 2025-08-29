<template>
  <div class="periodos-container">
    <h2>Definir períodos letivos</h2>
    <form @submit.prevent="salvarPeriodo" class="periodos-form">
      <label for="trimestre">Trimestre:</label>
      <select id="trimestre" v-model="trimestre" required>
        <option value="">Selecione...</option>
        <option value="1º Tri">1º Tri</option>
        <option value="2º Tri">2º Tri</option>
        <option value="3º Tri">3º Tri</option>
      </select>
      <br /><br />

      <label for="primeiro-dia">Primeiro dia:</label>
      <input
        id="primeiro-dia"
        type="date"
        v-model="primeiroDia"
        :disabled="!trimestre"
        required
      />
      <br /><br />

      <label for="ultimo-dia">Último dia:</label>
      <input
        id="ultimo-dia"
        type="date"
        v-model="ultimoDia"
        :disabled="!trimestre"
      />
      <br /><br />

      <button type="submit" class="btn-salvar" :disabled="!trimestre || !primeiroDia">Salvar</button>
    </form>
    <div v-if="mensagem" class="mensagem-periodo">{{ mensagem }}</div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'

const trimestre = ref('')
const primeiroDia = ref('')
const ultimoDia = ref('')
const mensagem = ref('')

// Busca os dados do período ao selecionar um trimestre
watch(trimestre, async (novoTrimestre) => {
  primeiroDia.value = ''
  ultimoDia.value = ''
  mensagem.value = ''
  if (!novoTrimestre) return
  try {
    const resp = await axios.get('/api/periodos-avaliacao', {
      params: { periodo: novoTrimestre }
    })
    if (resp.data && resp.data.dt_primeiro_dia) {
      primeiroDia.value = resp.data.dt_primeiro_dia
      ultimoDia.value = resp.data.dt_ultimo_dia || ''
      mensagem.value = ''
    }
  } catch (e) {
    // Não faz nada se não encontrar, apenas limpa os campos
  }
})

async function salvarPeriodo() {
  mensagem.value = ''
  if (!trimestre.value || !primeiroDia.value) {
    mensagem.value = 'Preencha o trimestre e o primeiro dia.'
    return
  }
  try {
    await axios.post('/api/periodos-avaliacao', {
      periodo: trimestre.value,
      dt_primeiro_dia: primeiroDia.value,
      dt_ultimo_dia: ultimoDia.value || null
    })
    mensagem.value = `Período salvo: ${trimestre.value} (${primeiroDia.value} a ${ultimoDia.value || '---'})`
  } catch (e) {
    mensagem.value = 'Erro ao salvar período.'
  }
}
</script>

<style scoped>
.periodos-container {
  max-width: 400px;
  margin: 2em auto;
  padding: 2em;
  background: #f9f9f9;
  border-radius: 10px;
  box-shadow: 0 2px 8px #0001;
}
.periodos-form label {
  display: block;
  margin-bottom: 0.3em;
  font-weight: bold;
}
.periodos-form select,
.periodos-form input[type="date"] {
  width: 100%;
  padding: 0.5em;
  margin-bottom: 1em;
  border-radius: 4px;
  border: 1px solid #ccc;
  box-sizing: border-box;
}
.btn-salvar {
  background: #5a773b;
  color: #fff;
  border: none;
  padding: 0.7em 2em;
  border-radius: 4px;
  font-size: 1em;
  font-weight: bold;
  cursor: pointer;
}
.btn-salvar:disabled {
  background: #bdbdbd;
  cursor: not-allowed;
}
.btn-salvar:hover:enabled {
  background: #3e5527;
}
.mensagem-periodo {
  margin-top: 1em;
  color: #2a3a5e;
  font-weight: bold;
  text-align: center;
}
</style>
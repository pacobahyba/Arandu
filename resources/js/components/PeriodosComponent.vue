<template>
  <div class="periodos-container">
    <h2>Definir períodos letivos</h2>
    <form @submit.prevent="salvarPeriodo" class="periodos-form">
      <label for="ano">Ano:</label>
      <select id="ano" v-model="anoSelecionado" required>
        <option value="">Selecione...</option>
        <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
      </select>
      <br /><br />

      <label for="trimestre">Trimestre:</label>
      <select id="trimestre" v-model="trimestre" :disabled="!anoSelecionado" required>
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

      <button
        type="submit"
        class="btn-salvar"
        :disabled="!trimestre || !primeiroDia || bloqueado"
      >
        Salvar
      </button>
    </form>
    <div v-if="mensagem" class="mensagem-periodo">{{ mensagem }}</div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import axios from 'axios'

const anoSelecionado = ref('')
const anosDisponiveis = ref([])
const trimestre = ref('')
const primeiroDia = ref('')
const ultimoDia = ref('')
const mensagem = ref('')
const periodoId = ref(null)
const bloqueado = ref(false)
const periodoJaCadastrado = ref(false)

// Carrega anos disponíveis ao montar o componente
onMounted(() => {
  const anoAtual = new Date().getFullYear()
  // Adiciona o ano atual e os próximos 2 anos
  anosDisponiveis.value = [anoAtual, anoAtual + 1, anoAtual + 2]
})

// Busca os dados do período ao selecionar ano e trimestre
watch([anoSelecionado, trimestre], async ([novoAno, novoTrimestre]) => {
  primeiroDia.value = ''
  ultimoDia.value = ''
  mensagem.value = ''
  periodoId.value = null
  periodoJaCadastrado.value = false
  
  if (!novoAno || !novoTrimestre) return
  
  try {
    const resp = await axios.get('/api/periodos-avaliacao', {
      params: { 
        ano: novoAno,
        periodo: novoTrimestre 
      }
    })
    
    if (resp.data && resp.data.dt_primeiro_dia) {
      periodoJaCadastrado.value = true
      primeiroDia.value = resp.data.dt_primeiro_dia
      ultimoDia.value = resp.data.dt_ultimo_dia || ''
      periodoId.value = resp.data.id_periodo_avaliacao
      mensagem.value = ''
    }
  } catch (e) {
    periodoJaCadastrado.value = false
  }
})

watch([trimestre, primeiroDia, ultimoDia], () => {
  bloqueado.value = false
})

async function salvarPeriodo() {
  mensagem.value = ''
  if (!anoSelecionado.value || !trimestre.value || !primeiroDia.value) {
    mensagem.value = 'Preencha o ano, trimestre e o primeiro dia.'
    return
  }
  bloqueado.value = true
  try {
    await axios.post('/api/periodos-avaliacao', {
      ano: anoSelecionado.value,
      periodo: trimestre.value,
      dt_primeiro_dia: primeiroDia.value,
      dt_ultimo_dia: ultimoDia.value || null
    })
    mensagem.value = `Período salvo: ${trimestre.value}/${anoSelecionado.value} (${primeiroDia.value} a ${ultimoDia.value || '---'})`
    periodoJaCadastrado.value = true
  } catch (e) {
    mensagem.value = 'Erro ao salvar período.'
    bloqueado.value = false
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
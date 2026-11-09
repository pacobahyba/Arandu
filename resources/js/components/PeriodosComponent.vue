<template>
  <div class="periodos-container">
    <h2>Períodos letivos</h2>

    <label for="ano" class="bloco-label">Ano letivo:</label>
    <select id="ano" v-model="anoSelecionado" class="campo">
      <option value="">Selecione...</option>
      <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
    </select>

    <!-- ============ Regime ============ -->
    <section v-if="anoSelecionado" class="bloco">
      <h3>Regime</h3>

      <label for="regime-padrao" class="bloco-label">Regime padrão:</label>
      <select id="regime-padrao" v-model="regimePadrao" class="campo">
        <option v-for="r in regimes" :key="r.regime" :value="r.regime">{{ r.nome }}</option>
      </select>
      <p class="dica">Define os períodos exibidos em "Datas dos períodos" para turmas sem exceção.</p>

      <h4>Exceções por turma</h4>
      <p v-if="!excecoes.length" class="dica">Nenhuma exceção — todas as turmas usam o regime padrão.</p>
      <ul v-else class="lista-excecoes">
        <li v-for="ex in excecoes" :key="ex.id_regime">
          <span class="nome-turma">{{ nomeDaTurma(ex.id_turma, ex.origem_turma) }}</span>
          <span class="regime-turma">{{ ex.nome }}</span>
          <button type="button" class="btn-remover" title="Remover exceção" @click="removerExcecao(ex)">
            &times;
          </button>
        </li>
      </ul>

      <div class="linha nova-excecao">
        <select v-model="novaExcecaoTurma" class="campo">
          <option value="">Adicionar turma...</option>
          <option
            v-for="t in turmasSemExcecao"
            :key="chaveTurma(t)"
            :value="chaveTurma(t)"
          >{{ t.nome_turma }}</option>
        </select>
        <select v-model="novaExcecaoRegime" class="campo campo-curto">
          <option v-for="r in regimes" :key="r.regime" :value="r.regime">{{ r.nome }}</option>
        </select>
        <button
          type="button"
          class="btn-salvar"
          :disabled="!novaExcecaoTurma || salvandoRegime"
          @click="salvarExcecao"
        >Adicionar</button>
      </div>
    </section>

    <!-- ============ Datas dos períodos ============ -->
    <section v-if="anoSelecionado" class="bloco">
      <h3>Datas dos períodos</h3>

      <label class="bloco-label">Aplicar a:</label>
      <div class="linha">
        <label class="radio"><input type="radio" value="todas" v-model="escopoDatas" /> Todas as turmas</label>
        <label class="radio"><input type="radio" value="turma" v-model="escopoDatas" /> Turma específica</label>
      </div>

      <select v-if="escopoDatas === 'turma'" v-model="turmaDatas" class="campo">
        <option value="">Selecione a turma...</option>
        <option v-for="t in turmas" :key="chaveTurma(t)" :value="chaveTurma(t)">{{ t.nome_turma }}</option>
      </select>

      <form @submit.prevent="salvarPeriodo" class="periodos-form">
        <label for="periodo" class="bloco-label">{{ substantivoDatas }}:</label>
        <select id="periodo" v-model="periodo" class="campo" :disabled="!periodosDisponiveis.length" required>
          <option value="">Selecione...</option>
          <option v-for="p in periodosDisponiveis" :key="p" :value="p">{{ p }}</option>
        </select>

        <label for="primeiro-dia" class="bloco-label">Primeiro dia:</label>
        <input id="primeiro-dia" type="date" v-model="primeiroDia" :disabled="!periodo" class="campo" required />

        <label for="ultimo-dia" class="bloco-label">Último dia:</label>
        <input id="ultimo-dia" type="date" v-model="ultimoDia" :disabled="!periodo" class="campo" />

        <button type="submit" class="btn-salvar" :disabled="!periodo || !primeiroDia || bloqueado">
          Salvar
        </button>
      </form>
    </section>

    <div v-if="mensagem" class="mensagem-periodo" :class="{ erro: mensagemErro }">{{ mensagem }}</div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import axios from 'axios'
import { carregarTodasTurmas, chaveTurma } from '../composables/useTurmas'
import { limparCacheRegimes } from '../composables/usePeriodos'

const anoSelecionado = ref('')
const anosDisponiveis = ref([])
const mensagem = ref('')
const mensagemErro = ref(false)

// --- regimes ---
const regimes = ref([])
const regimePadrao = ref('TRI')
const excecoes = ref([])
const turmas = ref([])
const novaExcecaoTurma = ref('')
const novaExcecaoRegime = ref('TRI')
const salvandoRegime = ref(false)

// --- datas ---
const periodo = ref('')
const primeiroDia = ref('')
const ultimoDia = ref('')
const bloqueado = ref(false)
const escopoDatas = ref('todas')
const turmaDatas = ref('')

onMounted(async () => {
  // De 2025 (ano em que o sistema começou) até o ano seguinte ao atual.
  const ANO_INICIAL = 2025
  const anoFinal = new Date().getFullYear() + 1
  anosDisponiveis.value = Array.from(
    { length: Math.max(anoFinal - ANO_INICIAL + 1, 1) },
    (_, i) => ANO_INICIAL + i
  )

  try {
    const resp = await axios.get('/api/regimes')
    regimes.value = Array.isArray(resp.data) ? resp.data : []
  } catch (e) {
    regimes.value = []
  }

  turmas.value = await carregarTodasTurmas()
})

// Turmas ainda sem exceção cadastrada.
const turmasSemExcecao = computed(() => {
  const jaTem = new Set(excecoes.value.map(e => `${e.origem_turma}-${e.id_turma}`))
  return turmas.value.filter(t => !jaTem.has(chaveTurma(t)))
})

/** Regime que vale para o escopo escolhido na seção de datas. */
const regimeDatas = computed(() => {
  if (escopoDatas.value === 'turma' && turmaDatas.value) {
    const [origem, id] = separarChave(turmaDatas.value)
    const ex = excecoes.value.find(e => e.origem_turma === origem && String(e.id_turma) === String(id))
    if (ex) return ex.regime
  }
  return regimePadrao.value
})

const periodosDisponiveis = computed(
  () => regimes.value.find(r => r.regime === regimeDatas.value)?.periodos || []
)
const substantivoDatas = computed(
  () => regimes.value.find(r => r.regime === regimeDatas.value)?.substantivo || 'Período'
)

function separarChave(chave) {
  const [origem, ...resto] = String(chave).split('-')
  return [origem, resto.join('-')]
}

function nomeDaTurma(idTurma, origem) {
  const t = turmas.value.find(x => x.origem === origem && String(x.id_turma) === String(idTurma))
  return t ? t.nome_turma : `Turma ${idTurma}`
}

function avisar(texto, erro = false) {
  mensagem.value = texto
  mensagemErro.value = erro
}

// Carrega regime padrão + exceções ao trocar de ano.
watch(anoSelecionado, async (ano) => {
  avisar('')
  periodo.value = ''
  primeiroDia.value = ''
  ultimoDia.value = ''
  excecoes.value = []
  if (!ano) return

  try {
    const resp = await axios.get('/api/regimes-avaliacao', { params: { ano } })
    regimePadrao.value = resp.data?.padrao?.regime || 'TRI'
    excecoes.value = resp.data?.excecoes || []
  } catch (e) {
    regimePadrao.value = 'TRI'
  }
})

// Se o regime mudar, um período do regime antigo não pode continuar selecionado.
watch(periodosDisponiveis, (lista) => {
  if (periodo.value && !lista.includes(periodo.value)) periodo.value = ''
})

watch(escopoDatas, (valor) => {
  if (valor === 'todas') turmaDatas.value = ''
})

// Busca as datas já cadastradas para o período/escopo selecionado.
watch([anoSelecionado, periodo, escopoDatas, turmaDatas], async () => {
  primeiroDia.value = ''
  ultimoDia.value = ''
  if (!anoSelecionado.value || !periodo.value) return

  try {
    const resp = await axios.get('/api/periodos-avaliacao', {
      params: { ano: anoSelecionado.value, periodo: periodo.value, ...escopoRequisicao() }
    })
    if (resp.data && resp.data.dt_primeiro_dia) {
      primeiroDia.value = resp.data.dt_primeiro_dia
      ultimoDia.value = resp.data.dt_ultimo_dia || ''
    }
  } catch (e) {
    // sem cadastro ainda
  }
})

watch([periodo, primeiroDia, ultimoDia], () => { bloqueado.value = false })

/** Parâmetros de turma para as chamadas de calendário. */
function escopoRequisicao() {
  if (escopoDatas.value !== 'turma' || !turmaDatas.value) return {}
  const [origem, id] = separarChave(turmaDatas.value)
  return { id_turma: id, origem }
}

async function salvarExcecao() {
  const [origem, id] = separarChave(novaExcecaoTurma.value)
  salvandoRegime.value = true
  try {
    const ok = await enviarRegime({
      ano: anoSelecionado.value,
      regime: novaExcecaoRegime.value,
      id_turma: id,
      origem
    })
    if (ok) {
      novaExcecaoTurma.value = ''
      await recarregarRegimes()
      avisar('Exceção adicionada.')
    }
  } finally {
    salvandoRegime.value = false
  }
}

/**
 * O backend responde 409 quando já existem lançamentos em períodos que não
 * pertencem ao novo regime. Confirmamos com o usuário antes de reenviar,
 * porque esses dados deixam de aparecer nas telas.
 */
async function enviarRegime(payload) {
  try {
    await axios.post('/api/regimes-avaliacao', payload)
    limparCacheRegimes()
    return true
  } catch (e) {
    if (e.response?.status === 409) {
      const d = e.response.data
      const texto = `Já existem ${d.objetivos} objetivo(s) e ${d.notas} nota(s) lançados em `
        + `períodos de outro regime neste ano. Se continuar, esses lançamentos deixarão de `
        + `aparecer nas telas. Deseja continuar mesmo assim?`
      if (window.confirm(texto)) {
        await axios.post('/api/regimes-avaliacao', { ...payload, confirmar: true })
        limparCacheRegimes()
        return true
      }
      avisar('Alteração cancelada.', true)
      return false
    }
    avisar('Erro ao salvar o regime.', true)
    return false
  }
}

async function removerExcecao(ex) {
  if (!window.confirm(`Remover a exceção de ${nomeDaTurma(ex.id_turma, ex.origem_turma)}? A turma volta a usar o regime padrão.`)) return
  try {
    await axios.delete(`/api/regimes-avaliacao/${ex.id_regime}`)
    limparCacheRegimes()
    await recarregarRegimes()
    avisar('Exceção removida.')
  } catch (e) {
    avisar('Erro ao remover a exceção.', true)
  }
}

async function recarregarRegimes() {
  const resp = await axios.get('/api/regimes-avaliacao', { params: { ano: anoSelecionado.value } })
  regimePadrao.value = resp.data?.padrao?.regime || 'TRI'
  excecoes.value = resp.data?.excecoes || []
}

async function salvarPeriodo() {
  avisar('')
  if (!anoSelecionado.value || !periodo.value || !primeiroDia.value) {
    avisar('Preencha o ano, o período e o primeiro dia.', true)
    return
  }
  if (escopoDatas.value === 'turma' && !turmaDatas.value) {
    avisar('Selecione a turma.', true)
    return
  }
  bloqueado.value = true
  try {
    await axios.post('/api/periodos-avaliacao', {
      ano: anoSelecionado.value,
      periodo: periodo.value,
      dt_primeiro_dia: primeiroDia.value,
      dt_ultimo_dia: ultimoDia.value || null,
      ...escopoRequisicao()
    })
    let alvo = 'todas as turmas'
    if (escopoDatas.value === 'turma') {
      const [origem, id] = separarChave(turmaDatas.value)
      alvo = nomeDaTurma(id, origem)
    }
    avisar(`Período salvo: ${periodo.value}/${anoSelecionado.value} (${primeiroDia.value} a ${ultimoDia.value || '---'}) — ${alvo}.`)
  } catch (e) {
    avisar('Erro ao salvar período.', true)
    bloqueado.value = false
  }
}
</script>

<style scoped>
.periodos-container {
  max-width: 520px;
  margin: 2em auto;
  padding: 2em;
  background: #f9f9f9;
  border-radius: 10px;
  box-shadow: 0 2px 8px #0001;
}
.periodos-container h2 {
  margin-top: 0;
  text-align: center;
}
.bloco {
  margin-top: 2em;
  padding-top: 1.2em;
  border-top: 1px solid #ddd;
}
.bloco h3 {
  margin: 0 0 0.8em;
  color: #3e5527;
}
.bloco h4 {
  margin: 1.5em 0 0.5em;
  font-size: 0.95em;
}
.bloco-label {
  display: block;
  margin-bottom: 0.3em;
  font-weight: bold;
}
.campo {
  width: 100%;
  padding: 0.5em;
  margin-bottom: 1em;
  border-radius: 4px;
  border: 1px solid #ccc;
  box-sizing: border-box;
}
.campo-curto {
  max-width: 150px;
}
.linha {
  display: flex;
  gap: 0.6em;
  align-items: flex-start;
}
.linha .campo {
  flex: 1;
}
.nova-excecao {
  margin-top: 0.8em;
}
.radio {
  font-weight: normal;
  display: flex;
  align-items: center;
  gap: 0.3em;
  margin-bottom: 1em;
}
.dica {
  margin: -0.6em 0 0.8em;
  font-size: 0.85em;
  color: #666;
}
.lista-excecoes {
  list-style: none;
  padding: 0;
  margin: 0 0 0.5em;
}
.lista-excecoes li {
  display: flex;
  align-items: center;
  gap: 0.6em;
  padding: 0.45em 0.6em;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  margin-bottom: 0.35em;
}
.nome-turma {
  flex: 1;
}
.regime-turma {
  font-size: 0.85em;
  color: #3e5527;
  font-weight: bold;
}
.btn-salvar {
  background: #5a773b;
  color: #fff;
  border: none;
  padding: 0.55em 1.4em;
  border-radius: 4px;
  font-size: 1em;
  font-weight: bold;
  cursor: pointer;
  white-space: nowrap;
}
.btn-salvar:disabled {
  background: #bdbdbd;
  cursor: not-allowed;
}
.btn-salvar:hover:enabled {
  background: #3e5527;
}
.btn-remover {
  background: none;
  border: none;
  color: #b33;
  font-size: 1.3em;
  line-height: 1;
  cursor: pointer;
  padding: 0 0.2em;
}
.mensagem-periodo {
  margin-top: 1.5em;
  color: #2a3a5e;
  font-weight: bold;
  text-align: center;
}
.mensagem-periodo.erro {
  color: #b33;
}
</style>

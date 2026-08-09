import { ref, watch, computed, unref } from 'vue'
import axios from 'axios'

// Cache por "ano|turma|origem" — evita refazer a requisição a cada troca de
// matéria, já que o regime só depende de ano + turma.
const cache = new Map()

function chave(ano, idTurma, origem) {
  return `${ano}|${idTurma || 0}|${origem || 'S'}`
}

async function buscarRegime(ano, idTurma, origem) {
  const k = chave(ano, idTurma, origem)
  if (cache.has(k)) return cache.get(k)

  const promessa = axios
    .get('/api/regime-turma', { params: { ano, id_turma: idTurma || null, origem: origem || 'S' } })
    .then(resp => resp.data)
    .catch(() => {
      // Sem resposta do servidor, cai no comportamento histórico do sistema.
      cache.delete(k)
      return { regime: 'TRI', substantivo: 'Trimestre', periodos: ['1º Tri', '2º Tri', '3º Tri'] }
    })

  cache.set(k, promessa)
  return promessa
}

/** Invalida o cache — chamar depois que o administrativo altera um regime. */
export function limparCacheRegimes() {
  cache.clear()
}

/**
 * Lista de períodos válidos para a turma selecionada.
 *
 * @param {Ref} anoRef     ano letivo selecionado
 * @param {Ref} turmaRef   id da turma selecionada ('' enquanto não há turma)
 * @param {Ref} origemRef  'S' (Sophia) ou 'C' (customizada)
 * @param {Ref} periodoSelecionadoRef  opcional: é limpo automaticamente quando
 *   o período escolhido não pertence mais ao regime da turma.
 */
export function usePeriodos(anoRef, turmaRef, origemRef = ref('S'), periodoSelecionadoRef = null) {
  const periodos = ref([])
  const regime = ref('')
  const substantivo = ref('Período')
  const carregando = ref(false)

  async function carregar() {
    const ano = unref(anoRef)
    const idTurma = unref(turmaRef)

    if (!ano) {
      periodos.value = []
      regime.value = ''
      substantivo.value = 'Período'
      return
    }

    // Sem turma ainda (ex.: aluno com mais de uma turma), o backend devolve o
    // regime padrão do ano — melhor que um select vazio. Ao escolher a turma,
    // a lista é recarregada e o watch abaixo limpa uma seleção incompatível.

    carregando.value = true
    try {
      const dados = await buscarRegime(ano, idTurma, unref(origemRef))
      periodos.value = dados.periodos || []
      regime.value = dados.regime || ''
      substantivo.value = dados.substantivo || 'Período'

      // Trocar de turma bimestral para semestral deixaria "3º Bim" selecionado
      // e gravaria notas num período inexistente para a turma nova.
      if (periodoSelecionadoRef && periodoSelecionadoRef.value &&
          !periodos.value.includes(periodoSelecionadoRef.value)) {
        periodoSelecionadoRef.value = ''
      }
    } finally {
      carregando.value = false
    }
  }

  watch([anoRef, turmaRef, origemRef], carregar, { immediate: true })

  return { periodos, regime, substantivo, carregando, recarregar: carregar }
}

/**
 * Verifica se o período de avaliação já encerrou, respeitando o calendário
 * específico da turma quando existir.
 *
 * Substitui as cópias de verificarPeriodo() que existiam em
 * AvaliacaoComponent e ObjetivoComponent.
 */
export function usePeriodoExpirado(anoRef, periodoRef, turmaRef = ref(null), origemRef = ref('S')) {
  const periodoExpirado = ref(false)
  const dataExpiracao = ref('')

  async function verificarPeriodo() {
    periodoExpirado.value = false
    dataExpiracao.value = ''

    const ano = unref(anoRef)
    const periodo = unref(periodoRef)
    if (!ano || !periodo) return

    try {
      const resp = await axios.get('/api/periodos-avaliacao', {
        params: {
          ano,
          periodo,
          id_turma: unref(turmaRef) || null,
          origem: unref(origemRef) || 'S'
        }
      })

      if (resp.data && resp.data.dt_ultimo_dia) {
        const dataFim = new Date(resp.data.dt_ultimo_dia)
        const hoje = new Date()
        hoje.setHours(0, 0, 0, 0)
        if (hoje > dataFim) {
          periodoExpirado.value = true
          dataExpiracao.value = dataFim.toLocaleDateString('pt-BR')
        }
      }
    } catch (e) {
      // silencio: se não encontrar, mantém não expirado
    }
  }

  watch([anoRef, periodoRef, turmaRef], verificarPeriodo)

  return { periodoExpirado, dataExpiracao, verificarPeriodo }
}

/** Origem da turma no formato esperado pela API, a partir de `isCustomizada`. */
export function origemDaTurma(turmas, idTurma) {
  const turma = (unref(turmas) || []).find(t => String(t.id_turma) === String(idTurma))
  return turma && turma.isCustomizada ? 'C' : 'S'
}

/** Períodos que existem no histórico de objetivos de um ano (import de anos passados). */
export function usePeriodosHistoricos(anoRef) {
  const periodos = ref([])

  watch(anoRef, async (ano) => {
    if (!ano) {
      periodos.value = []
      return
    }
    try {
      const resp = await axios.get('/api/periodos-objetivos', { params: { ano } })
      periodos.value = Array.isArray(resp.data) ? resp.data : []
    } catch (e) {
      periodos.value = []
    }
  }, { immediate: true })

  return { periodos }
}

/** Catálogo de regimes para os selects do administrativo. */
export function useCatalogoRegimes() {
  const regimes = ref([])

  axios.get('/api/regimes')
    .then(resp => { regimes.value = Array.isArray(resp.data) ? resp.data : [] })
    .catch(() => { regimes.value = [] })

  const porCodigo = computed(() =>
    Object.fromEntries(regimes.value.map(r => [r.regime, r]))
  )

  return { regimes, porCodigo }
}

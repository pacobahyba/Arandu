<template>
  <div class="container">
    <h2 class="titulo">Liberar notas aos alunos</h2>

    <div v-if="perfilLogado !== 'Professor'" class="aviso-perfil">
      Esta tela está disponível apenas para perfil Professor.
    </div>

    <template v-else>
      <div class="selecao-container">
        <label for="lib-ano-select" class="avaliacao-label">Ano:</label>
        <select id="lib-ano-select" v-model="anoSelecionado" class="avaliacao-select combo-pequena">
          <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
        </select>

        <label for="lib-materia-select" class="avaliacao-label">Matéria:</label>
        <select id="lib-materia-select" v-model="materiaSelecionada" class="avaliacao-select" @change="aoMudarMateria">
          <option value="">Selecione...</option>
          <option v-for="materia in materias" :key="materia.id_materia" :value="materia.id_materia">
            {{ materia.nome_materia }}
          </option>
        </select>

        <label for="lib-turma-select" class="avaliacao-label">Turma:</label>
        <!-- O valor combina origem e id: turmas do Sophia e customizadas podem ter o mesmo id -->
        <select
          id="lib-turma-select"
          v-model="turmaChave"
          class="avaliacao-select"
          :disabled="!materiaSelecionada || carregandoTurmas"
        >
          <option value="">{{ carregandoTurmas ? 'Carregando...' : 'Selecione...' }}</option>
          <option v-for="turma in turmas" :key="chave(turma)" :value="chave(turma)">
            {{ turma.nome_turma }}
          </option>
        </select>

        <!-- O período vem depois da turma: a lista depende do regime da turma -->
        <label for="lib-periodo-select" class="avaliacao-label">{{ substantivoPeriodo }}:</label>
        <select
          id="lib-periodo-select"
          v-model="periodoSelecionado"
          class="avaliacao-select combo-pequena"
          :disabled="!turmaChave || !periodos.length"
        >
          <option value="">Selecione...</option>
          <option v-for="p in periodos" :key="p" :value="p">{{ p }}</option>
        </select>
      </div>

      <div v-if="erro" class="erro">{{ erro }}</div>

      <div v-if="filtrosSelecionados" class="status-wrap">
        <div v-if="carregandoStatus" class="status-carregando">Verificando status...</div>

        <div v-else-if="status" :class="['status-card', status.liberado ? 'status-liberado' : 'status-trancado']">
          <template v-if="status.liberado">
            <div class="status-titulo">🔓 Liberado: os alunos podem ver as notas que você deu.</div>
            <p class="status-detalhe">
              Na tela de Avaliação, os alunos desta turma veem as colunas Prof, Rec e CF e a nota final
              de {{ periodoSelecionado }}.
            </p>
            <button class="btn-acao btn-trancar" :disabled="salvando" @click="alterar(false)">
              {{ salvando ? 'Salvando...' : 'Trancar novamente' }}
            </button>
          </template>

          <template v-else>
            <div class="status-titulo">🔒 Trancado: suas notas não estão visíveis aos alunos.</div>
            <p class="status-detalhe">
              Os alunos desta turma veem apenas a própria autoavaliação (Est) em {{ periodoSelecionado }}.
            </p>
            <button class="btn-acao btn-liberar" :disabled="salvando" @click="alterar(true)">
              {{ salvando ? 'Salvando...' : 'Liberar acesso' }}
            </button>
            <p class="status-detalhe status-dica">
              Ao liberar, os alunos poderão ver as notas que você deu (Prof, Rec, CF e nota final).
            </p>
          </template>

          <div v-if="status.atualizado_em" class="status-rodape">
            Última alteração: {{ formatarData(status.atualizado_em) }}
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import { usePeriodos } from '../composables/usePeriodos'
import { carregarMateriasProfessor, carregarTurmasProfessor } from '../composables/useTurmas'

const perfilLogado = ref(sessionStorage.getItem('perfil') || '')

const anosDisponiveis = ref([])
const anoSelecionado = ref(new Date().getFullYear())

const materias = ref([])
const materiaSelecionada = ref('')

const turmas = ref([])
const turmaChave = ref('') // "S-123" (Sophia) ou "C-5" (customizada)
const carregandoTurmas = ref(false)

const periodoSelecionado = ref('')

const status = ref(null)
const carregandoStatus = ref(false)
const salvando = ref(false)
const erro = ref('')

function chave(turma) {
  return `${turma.isCustomizada ? 'C' : 'S'}-${turma.id_turma}`
}

const turmaSelecionada = computed(() => turmaChave.value ? turmaChave.value.slice(2) : '')
const origemTurma = computed(() => turmaChave.value ? turmaChave.value.charAt(0) : 'S')

// Períodos válidos conforme o regime da turma selecionada.
const { periodos, substantivo: substantivoPeriodo } = usePeriodos(
  anoSelecionado, turmaSelecionada, origemTurma, periodoSelecionado
)

const filtrosSelecionados = computed(() => Boolean(
  anoSelecionado.value && materiaSelecionada.value && turmaSelecionada.value && periodoSelecionado.value
))

function parametros() {
  return {
    ano: anoSelecionado.value,
    id_materia: materiaSelecionada.value,
    id_turma: turmaSelecionada.value,
    origem: origemTurma.value,
    periodo: periodoSelecionado.value
  }
}

async function carregarAnos() {
  try {
    const resp = await axios.get('/api/anos-objetivos')
    anosDisponiveis.value = Array.isArray(resp.data) && resp.data.length ? resp.data : [new Date().getFullYear()]
  } catch {
    anosDisponiveis.value = [new Date().getFullYear()]
  }
  anoSelecionado.value = anosDisponiveis.value[0]
}

async function aoMudarMateria() {
  turmas.value = []
  turmaChave.value = ''
  periodoSelecionado.value = ''
  if (!materiaSelecionada.value) return

  carregandoTurmas.value = true
  try {
    turmas.value = await carregarTurmasProfessor(materiaSelecionada.value)
  } finally {
    carregandoTurmas.value = false
  }
}

async function carregarStatus() {
  status.value = null
  erro.value = ''
  if (!filtrosSelecionados.value) return

  const consulta = parametros()
  carregandoStatus.value = true
  try {
    const resp = await axios.get('/api/liberacao-notas', { params: consulta })
    // Descarta a resposta se a seleção mudou enquanto a requisição estava em andamento.
    if (JSON.stringify(consulta) === JSON.stringify(parametros())) {
      status.value = resp.data
    }
  } catch (e) {
    erro.value = e.response?.data?.message || 'Não foi possível consultar o status das notas.'
  } finally {
    carregandoStatus.value = false
  }
}

async function alterar(liberado) {
  salvando.value = true
  erro.value = ''
  try {
    const resp = await axios.put('/api/liberacao-notas', {
      ...parametros(),
      liberado,
      perfil: sessionStorage.getItem('perfil'),
      codigo_professor: sessionStorage.getItem('codigo') || null
    })
    status.value = resp.data
  } catch (e) {
    erro.value = e.response?.data?.message || 'Não foi possível salvar a alteração.'
  } finally {
    salvando.value = false
  }
}

function formatarData(iso) {
  const data = new Date(iso)
  return Number.isNaN(data.getTime()) ? '' : data.toLocaleString('pt-BR')
}

watch([anoSelecionado, materiaSelecionada, turmaChave, periodoSelecionado], carregarStatus)

onMounted(async () => {
  if (perfilLogado.value !== 'Professor') return
  await carregarAnos()
  materias.value = await carregarMateriasProfessor()
})
</script>

<style scoped>
.container {
  padding: 2em;
}

.titulo {
  color: #73321c;
  margin-bottom: 1em;
  text-align: center;
}

.selecao-container {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.7em;
  margin-bottom: 1.2em;
}

.avaliacao-label {
  font-weight: bold;
}

.avaliacao-select {
  padding: 0.35em;
}

.combo-pequena {
  width: 120px;
}

.aviso-perfil {
  margin-top: 1em;
  padding: 0.8em;
  border: 1px solid #f0c36d;
  background: #fff8e5;
  color: #7a5600;
}

.erro {
  margin-bottom: 1em;
  padding: 0.8em;
  border: 1px solid #f5c2c7;
  background: #f8d7da;
  color: #842029;
}

.status-wrap {
  margin-top: 1.5em;
  max-width: 640px;
}

.status-carregando {
  color: #73331d;
  font-weight: bold;
}

.status-card {
  padding: 1.2em;
  border-radius: 8px;
  border: 2px solid;
}

.status-trancado {
  background: #fff3cd;
  border-color: #ffc107;
  color: #664d03;
}

.status-liberado {
  background: #d1e7dd;
  border-color: #198754;
  color: #0f5132;
}

.status-titulo {
  font-size: 1.15em;
  font-weight: bold;
  margin-bottom: 0.5em;
}

.status-detalhe {
  margin: 0.4em 0 0.8em;
}

.status-dica {
  font-size: 0.9em;
  margin-top: 0.8em;
  margin-bottom: 0;
}

.status-rodape {
  margin-top: 0.8em;
  font-size: 0.85em;
  opacity: 0.8;
}

.btn-acao {
  padding: 0.6em 1.4em;
  font-weight: bold;
  border-radius: 4px;
  cursor: pointer;
  color: #fff;
}

.btn-acao:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-liberar {
  background: #198754;
  border: 1px solid #146c43;
}

.btn-trancar {
  background: #7d1f24;
  border: 1px solid #61161b;
}
</style>

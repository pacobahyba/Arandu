<template>
  <div class="container" style="display: flex;">
    <div class="left-panel">
      <h2>Ficha de avaliação</h2>
      <label for="periodo-select" class="avaliacao-label">Período:</label>
      <select id="periodo-select" v-model="periodoSelecionado" class="avaliacao-select">
        <option value="">Selecione...</option>
        <option value="1º Tri">1º Tri</option>
        <option value="2º Tri">2º Tri</option>
        <option value="3º Tri">3º Tri</option>
      </select>
      <br /><br />
      <!-- Combos -->
      <label for="materia-select" class="avaliacao-label">Matéria:</label>
      <select id="materia-select" v-model="materiaSelecionada" @change="carregarTurmas" class="avaliacao-select">
        <option value="">Selecione...</option>
        <option v-for="materia in materias" :key="materia.id_materia" :value="materia.id_materia">
          {{ materia.nome_materia }} ({{ materia.abreviacao_materia }})
        </option>
      </select>
      <br /><br />

      <label for="turma-select" class="avaliacao-label">Turma:</label>
      <select id="turma-select" v-model="turmaSelecionada" :disabled="!materiaSelecionada" @change="carregarAlunos" class="avaliacao-select">
        <option value="">Selecione...</option>
        <option v-for="turma in turmas" :key="turma.id_turma" :value="turma.id_turma">
          {{ turma.nome_turma }}
        </option>
      </select>
      <br /><br />

      <label for="aluno-select" class="avaliacao-label">Aluno:</label>
      <select id="aluno-select" v-model="alunoSelecionado" :disabled="!turmaSelecionada" class="avaliacao-select">
        <option value="">Selecione...</option>
        <option v-for="aluno in alunos" :key="aluno.id_aluno" :value="aluno.id_aluno">
          {{ aluno.nome_aluno }}
        </option>
      </select>
      <br /><br />

      <!-- Botão Avaliar -->
      <button
        :disabled="!todasSelecionadas"
        @click="mostrarTabela = true"
        style="padding: 0.7em 2em; font-size: 1em; font-weight: bold;"
      >
        Avaliar
      </button>

      <!-- Painel de porcentagens -->
      <div v-if="mostrarTabela && objetivos.length && porcentagemConceituais !== null && porcentagemAtitudinais !== null" style="margin-top: 2em; margin-bottom:2em; background:#eef; padding:1em; border-radius:8px; width:400px;">
        <h3 style="margin-top:0;">Porcentagens da nota</h3>
        <table border="1" cellpadding="5" style="width:100%;">
          <thead>
            <tr>
              <th></th>
              <th>Porcentagem da nota</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Objetivos Conceituais</td>
              <td>{{ porcentagemConceituais }}%</td>
            </tr>
            <tr>
              <td>Obj. Atitudinais e Procedimentais</td>
              <td>{{ porcentagemAtitudinais }}%</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Tabela de objetivos separada por tipo OU mensagem de vazio -->
      <div v-if="mostrarTabela" style="margin-top:2em;">
        <template v-if="objetivos.length">
          <!-- Objetivos Conceituais -->
          <table v-if="objetivos.filter(o => o.tipo_objetivo === 'C').length" border="1" cellpadding="5" style="background: #ffffcc; margin-bottom:2em;">
            <thead>
              <tr>
                <th>#</th>
                <th>Objetivos Conceituais</th>
                <th>Est</th>
                <th>Prof</th>
                <th>CP</th>
                <th>Rec</th>
                <th>CF</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(objetivo, idx) in objetivos.filter(o => o.tipo_objetivo === 'C')" :key="objetivo.id_objetivo">
                <td>{{ idx + 1 }}</td>
                <td>{{ objetivo.nome_objetivo }}</td>
                <td>
                  <input
                    type="number"
                    v-model="objetivo.est"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'est')"
                    @blur="salvarNotaEst(objetivo)"
                  />
                </td>
                <!-- Prof -->
                <td>
                  <input
                    type="number"
                    v-model="objetivo.prof"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'prof'); atualizarCP(objetivo)"
                    @blur="salvarNotaProf(objetivo)"
                  />
                </td>
                <td>
                  <input
                    type="number"
                    v-model="objetivo.cp"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'cp'); atualizarCF(objetivo)"
                    @blur="salvarNotaCP(objetivo)"
                  />
                </td>
                <td>
                  <input
                    type="number"
                    v-model="objetivo.rec"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'rec'); atualizarCF(objetivo)"
                    @blur="salvarNotaRec(objetivo)"
                  />
                </td>
                <td>{{ Math.max(Number(objetivo.cp) || 0, Number(objetivo.rec) || 0) }}</td>
              </tr>
            </tbody>
          </table>
          <!-- Objetivos Atitudinais e Procedimentais -->
          <table v-if="objetivos.filter(o => o.tipo_objetivo === 'A').length" border="1" cellpadding="5" style="background: #e6ffe6;">
            <thead>
              <tr>
                <th>#</th>
                <th>Objetivos Atitudinais e Procedimentais</th>
                <th>Est</th>
                <th>Prof</th>
                <th>CP</th>
                <th>Rec</th>
                <th>CF</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(objetivo, idx) in objetivos.filter(o => o.tipo_objetivo === 'A')" :key="objetivo.id_objetivo">
                <td>{{ idx + 1 }}</td>
                <td>{{ objetivo.nome_objetivo }}</td>
                <td>
                  <input
                    type="number"
                    v-model="objetivo.est"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'est')"
                    @blur="salvarNotaEst(objetivo)"
                  />
                </td>
                <!-- Prof -->
                <td>
                  <input
                    type="number"
                    v-model="objetivo.prof"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'prof'); atualizarCP(objetivo)"
                    @blur="salvarNotaProf(objetivo)"
                  />
                </td>
                <td>
                  <input
                    type="number"
                    v-model="objetivo.cp"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'cp'); atualizarCF(objetivo)"
                    @blur="salvarNotaCP(objetivo)"
                  />
                </td>
                <td>
                  <input
                    type="number"
                    v-model="objetivo.rec"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'rec'); atualizarCF(objetivo)"
                    @blur="salvarNotaRec(objetivo)"
                  />
                </td>
                <td>{{ Math.max(Number(objetivo.cp) || 0, Number(objetivo.rec) || 0) }}</td>
              </tr>
            </tbody>
          </table>
        </template>
        <template v-else>
          <div style="font-size:1.2em; color:#73331d; font-weight:bold; margin-top:2em;">
            Não há objetivos cadastrados para esse período.
          </div>
        </template>
      </div>
    </div>
    <div class="right-panel">
      <!-- Painel de notas, porcentagens, nota final -->
      <div
        id="notas-panel"
        v-if="mostrarTabela && objetivos.length"
      >
        <table border="1" cellpadding="8" style="background: #dde3f0; min-width: 260px; text-align: center;">
          <tr style="background: #d3daea;">
            <td colspan="5" style="font-weight: bold;">NOTAS</td>
          </tr>
          <tr style="background: #f5f7fa;">
            <th>Est</th>
            <th>Prof</th>
            <th>CP</th>
            <th>Rec</th>
            <th>CF</th>
          </tr>
          <tr id="linha1">
            <td id="media-conceituais">{{ mediaConceituais }}</td>
            <td id="media-prof-conceituais">{{ mediaProfConceituais }}</td>
            <td id="media-cp-conceituais">{{ mediaCPConceituais }}</td>
            <td>C</td>
            <td id="media-cf-conceituais">
              {{
                (() => {
                  const objetivosC = objetivos.filter(o => o.tipo_objetivo === 'C')
                  if (!objetivosC.length) return 0
                  const soma = objetivosC.reduce((soma, obj) => soma + (Number(obj.cf) || 0), 0)
                  return Math.round((soma * (Number(porcentagemConceituais ?? 0))) / (objetivosC.length * 10))
                })()
              }}
            </td>
          </tr>
          <tr id="linha2">
            <td id="media-atitudinais">{{ mediaAtitudinais }}</td>
            <td id="media-prof-atitudinais">{{ mediaProfAtitudinais }}</td>
            <td id="media-cp-atitudinais">{{ mediaCPAtitudinais }}</td>
            <td>A</td>
            <td id="media-cf-atitudinais">{{ mediaCFAtitudinais }}</td>
          </tr>
          <tr id="linha3">
            <td id="total-medias">{{ totalMedias }}</td>
            <td id="total-prof-medias">{{ totalProfMedias }}</td>
            <td id="total-cp-medias">{{ totalCPMedias }}</td>
            <td>T</td>
            <td id="total-cf-medias">{{ totalCFMedias }}</td>
          </tr>
        </table>
        <div style="margin-top: 1em; font-size: 1.2em; font-weight: bold;">
          Nota final: {{ totalCFMedias }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watchEffect } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

const materias = ref([
  { id_materia: 1, nome_materia: 'História', abreviacao_materia: 'HIST' }
])
const materiaSelecionada = ref('')
const turmas = ref([])
const turmaSelecionada = ref('')
const alunos = ref([])
const alunoSelecionado = ref('')
const periodoSelecionado = ref('')
const objetivos = ref([])
const mostrarTabela = ref(false)
const porcentagemConceituais = ref(null)
const porcentagemAtitudinais = ref(null)

const mediaConceituais = computed(() => {
  const objetivosC = objetivos.value.filter(o => o.tipo_objetivo === 'C')
  if (!objetivosC.length) return 0
  const soma = objetivosC.reduce((soma, obj) => soma + (Number(obj.est) || 0), 0)
  return Math.round((soma * (Number(porcentagemConceituais.value) || 0)) / (objetivosC.length * 10))
})

const mediaAtitudinais = computed(() => {
  const objetivosA = objetivos.value.filter(o => o.tipo_objetivo === 'A')
  if (!objetivosA.length) return 0
  const soma = objetivosA.reduce((soma, obj) => soma + (Number(obj.est) || 0), 0)
  return Math.round((soma * (Number(porcentagemAtitudinais.value) || 0)) / (objetivosA.length * 10))
})

const totalMedias = computed(() => {
  return Math.round(Number(mediaConceituais.value) + Number(mediaAtitudinais.value))
})

const mediaProfConceituais = computed(() => {
  const objetivosC = objetivos.value.filter(o => o.tipo_objetivo === 'C')
  if (!objetivosC.length) return 0
  const soma = objetivosC.reduce((soma, obj) => soma + (Number(obj.prof) || 0), 0)
  return Math.round((soma * (Number(porcentagemConceituais.value) || 0)) / (objetivosC.length * 10))
})

const mediaProfAtitudinais = computed(() => {
  const objetivosA = objetivos.value.filter(o => o.tipo_objetivo === 'A')
  if (!objetivosA.length) return 0
  const soma = objetivosA.reduce((soma, obj) => soma + (Number(obj.prof) || 0), 0)
  return Math.round((soma * (Number(porcentagemAtitudinais.value) || 0)) / (objetivosA.length * 10))
})

const totalProfMedias = computed(() => {
  return Math.round(Number(mediaProfConceituais.value) + Number(mediaProfAtitudinais.value))
})

const mediaCPConceituais = computed(() => {
  const objetivosC = objetivos.value.filter(o => o.tipo_objetivo === 'C')
  if (!objetivosC.length) return 0
  const soma = objetivosC.reduce((soma, obj) => soma + (Number(obj.cp) || 0), 0)
  return Math.round((soma * (Number(porcentagemConceituais.value) || 0)) / (objetivosC.length * 10))
})

const mediaCPAtitudinais = computed(() => {
  const objetivosA = objetivos.value.filter(o => o.tipo_objetivo === 'A')
  if (!objetivosA.length) return 0
  const soma = objetivosA.reduce((soma, obj) => soma + (Number(obj.cp) || 0), 0)
  return Math.round((soma * (Number(porcentagemAtitudinais.value) || 0)) / (objetivosA.length * 10))
})

const totalCPMedias = computed(() => {
  return Math.round(Number(mediaCPConceituais.value) + Number(mediaCPAtitudinais.value))
})

const mediaCFConceituais = computed(() => {
  const objetivosC = objetivos.value.filter(o => o.tipo_objetivo === 'C')
  if (!objetivosC.length) return 0
  const soma = objetivosC.reduce((soma, obj) => soma + (Number(obj.cf) || 0), 0)
  return Math.round((soma * (Number(porcentagemConceituais.value) || 0)) / (objetivosC.length * 10))
})

const mediaCFAtitudinais = computed(() => {
  const objetivosA = objetivos.value.filter(o => o.tipo_objetivo === 'A')
  if (!objetivosA.length) return 0
  const soma = objetivosA.reduce((soma, obj) => soma + (Number(obj.cf) || 0), 0)
  return Math.round((soma * (Number(porcentagemAtitudinais.value) || 0)) / (objetivosA.length * 10))
})

const totalCFMedias = computed(() => {
  return Math.round(Number(mediaCFConceituais.value) + Number(mediaCFAtitudinais.value))
})

const todasSelecionadas = computed(() =>
  materiaSelecionada.value &&
  turmaSelecionada.value &&
  alunoSelecionado.value &&
  periodoSelecionado.value
)

async function carregarTurmas() {
  turmas.value = []
  turmaSelecionada.value = ''
  alunos.value = []
  alunoSelecionado.value = ''
  mostrarTabela.value = false
  porcentagemConceituais.value = null
  porcentagemAtitudinais.value = null

  // Sempre consome a API Sophia e filtra pelas turmas da Cecília (colaborador 1092)
  try {
    const response = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Unidades=2',
      {
        headers: {
          Token: auth.token
        }
      }
    )
    turmas.value = response.data.filter(turma =>
      Array.isArray(turma.professoresDisciplinas) &&
      turma.professoresDisciplinas.some(pd =>
        Array.isArray(pd.colaboradores) &&
        pd.colaboradores.some(colab => colab.codigo === 1092)
      )
    ).map(turma => ({
      id_turma: turma.codigo,
      nome_turma: turma.nome
    }))
  } catch (e) {
    turmas.value = []
  }
}

async function carregarAlunos() {
  alunos.value = []
  alunoSelecionado.value = ''
  if (!turmaSelecionada.value) return
  try {
    const response = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7&Inativos=false&ConsiderarTurmaFinalizada=false',
      {
        headers: {
          Token: auth.token
        }
      }
    )
    // Filtra alunos da turma selecionada
    alunos.value = response.data.filter(aluno =>
      aluno.turmas && aluno.turmas.some(t => t.codigo === turmaSelecionada.value)
    ).map(aluno => ({
      id_aluno: aluno.codigo,
      nome_aluno: aluno.nome
    }))
  } catch (e) {
    alunos.value = []
  }
}

async function carregarPorcentagens() {
  try {
    const response = await axios.get('/api/porcentagem-objetivos', {
      params: {
        id_materia: materiaSelecionada.value,
        id_turma: turmaSelecionada.value,
        periodo: periodoSelecionado.value
      }
    })
    if (response.data) {
      porcentagemConceituais.value = response.data.porcentagem_conceitual ?? 0
      porcentagemAtitudinais.value = response.data.porcentagem_atitudinal ?? 0
    } else {
      porcentagemConceituais.value = 0
      porcentagemAtitudinais.value = 0
    }
  } catch (e) {
    porcentagemConceituais.value = 0
    porcentagemAtitudinais.value = 0
  }
}

// Chame carregarPorcentagens() junto com o carregamento dos objetivos ao clicar em "Avaliar"

function limitarNota(objetivo, campo) {
  if (objetivo[campo] > 10) objetivo[campo] = 10
  if (objetivo[campo] < 0 || objetivo[campo] === null) objetivo[campo] = 0
}

function atualizarCP(objetivo) {
  objetivo.cp = objetivo.prof
  salvarNotaCP(objetivo)
}

function atualizarCF(objetivo) {
  objetivo.cf = Math.max(Number(objetivo.cp) || 0, Number(objetivo.rec) || 0)
}

async function salvarNotaEst(objetivo) {
  try {
    await axios.post('/api/notas', {
      id_objetivo: objetivo.id_objetivo,
      nota: objetivo.est,
      tipo_nota: 'Est',
      id_aluno: alunoSelecionado.value,
      periodo: periodoSelecionado.value
    })
    // Opcional: feedback visual
  } catch (e) {
    alert('Erro ao salvar nota Est!')
  }
}

async function salvarNotaProf(objetivo) {
  try {
    await axios.post('/api/notas', {
      id_objetivo: objetivo.id_objetivo,
      nota: objetivo.prof,
      tipo_nota: 'Prof',
      id_aluno: alunoSelecionado.value,
      periodo: periodoSelecionado.value
    })
  } catch (e) {
    alert('Erro ao salvar nota Prof!')
  }
}

async function salvarNotaRec(objetivo) {
  try {
    await axios.post('/api/notas', {
      id_objetivo: objetivo.id_objetivo,
      nota: objetivo.rec,
      tipo_nota: 'Rec',
      id_aluno: alunoSelecionado.value,
      periodo: periodoSelecionado.value
    })
  } catch (e) {
    alert('Erro ao salvar nota Rec!')
  }
}

async function salvarNotaCP(objetivo) {
  try {
    await axios.post('/api/notas', {
      id_objetivo: objetivo.id_objetivo,
      nota: objetivo.cp,
      tipo_nota: 'CP',
      id_aluno: alunoSelecionado.value,
      periodo: periodoSelecionado.value
    })
  } catch (e) {
    alert('Erro ao salvar nota CP!')
  }
}

watchEffect(async () => {
  // Carrega objetivos e notas quando mostrarTabela for true
  if (
    mostrarTabela.value &&
    materiaSelecionada.value &&
    turmaSelecionada.value &&
    alunoSelecionado.value &&
    periodoSelecionado.value
  ) {
    try {
      carregarPorcentagens()
      const objetivosResp = await axios.get(
        `/api/objetivos?id_materia=${materiaSelecionada.value}&turma_id=${turmaSelecionada.value}&trimestre=${periodoSelecionado.value}`
      )
      const notasResp = await axios.get(
        `/api/notas?aluno_id=${alunoSelecionado.value}&periodo=${periodoSelecionado.value}`
      )
      const notas = notasResp.data

      objetivos.value = objetivosResp.data.map(obj => {
        const notaEst = notas.find(n => n.id_objetivo === obj.id_objetivo && n.tipo_nota === 'Est')
        const notaProf = notas.find(n => n.id_objetivo === obj.id_objetivo && n.tipo_nota === 'Prof')
        const notaRec = notas.find(n => n.id_objetivo === obj.id_objetivo && n.tipo_nota === 'Rec')
        const notaCP = notas.find(n => n.id_objetivo === obj.id_objetivo && n.tipo_nota === 'CP')
        const notaCF = notas.find(n => n.id_objetivo === obj.id_objetivo && n.tipo_nota === 'CF')

        return {
          ...obj,
          est: notaEst ? notaEst.nota : 0,
          prof: notaProf ? notaProf.nota : 0,
          cp: notaCP ? notaCP.nota : 0,
          rec: notaRec ? notaRec.nota : 0,
          cf: notaCF ? notaCF.nota : Math.max(notaCP ? notaCP.nota : 0, notaRec ? notaRec.nota : 0) // fallback
        }
      })
    } catch (e) {
      objetivos.value = []
    }
  }
})
</script>
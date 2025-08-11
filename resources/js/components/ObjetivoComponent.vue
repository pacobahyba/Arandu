<template>
  <div>
    <h2>Turmas de Cecília Schubsky</h2>
    <div style="margin-bottom: 1em;">
      <label for="materia-select" class="avaliacao-label">Matéria:</label>
      <select id="materia-select" class="avaliacao-select" v-model="materiaSelecionada" @change="carregarTurmas">
        <option value="">Selecione...</option>
        <option v-for="materia in materias" :key="materia.id_materia" :value="materia.id_materia">
          {{ materia.nome_materia }}
        </option>
      </select>
    </div>

    <div style="margin-bottom: 1em;" v-if="materiaSelecionada">
      <label for="turma-select" class="avaliacao-label">Turma:</label>
      <select id="turma-select" class="avaliacao-select" v-model="turmaSelecionada">
        <option value="">Selecione...</option>
        <option v-for="turma in turmas" :key="turma.id_turma" :value="turma.id_turma">
          {{ turma.nome_turma }}
        </option>
      </select>
    </div>

    <div style="margin-bottom: 1em;" v-if="turmaSelecionada">
      <label for="trimestre-select" class="avaliacao-label">Trimestre:</label>
      <select
        id="trimestre-select"
        v-model="trimestreSelecionado"
        @change="carregarObjetivos"
        class="avaliacao-select"
      >
        <option value="">Selecione...</option>
        <option value="1º Tri">1º Tri</option>
        <option value="2º Tri">2º Tri</option>
        <option value="3º Tri">3º Tri</option>
      </select>
    </div>

    <div v-if="turmaSelecionada && trimestreSelecionado" style="margin-top:2em;">
      <table border="1" cellpadding="5" style="background: #f5f5f5; width: 400px;">
        <thead>
          <tr>
            <th></th>
            <th>Quantidade</th>
            <th>Porcentagem da nota</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Objetivos Conceituais</td>
            <td>{{ objetivosConceituais.length }}</td>
            <td>
              <input
                type="number"
                v-model="porcentagemConceituais"
                min="0"
                max="100"
                style="width: 60px;"
                @input="atualizarPorcentagem('conceituais')"
                @blur="salvarPorcentagem"
              /> %
            </td>
          </tr>
          <tr>
            <td>Obj. Atitudinais e Procedimentais</td>
            <td>{{ objetivosAtitudinais.length }}</td>
            <td>
              <input
                type="number"
                v-model="porcentagemAtitudinais"
                min="0"
                max="100"
                style="width: 60px;"
                @input="atualizarPorcentagem('atitudinais')"
                @blur="salvarPorcentagem"
              /> %
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="objetivosConceituais.length" style="margin-top:2em;">
        <table border="1" cellpadding="5" style="background: #ffffcc;">
          <thead>
            <tr>
              <th>Objetivos Conceituais</th>
              <th>Editar</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="objetivo in objetivosConceituais" :key="objetivo.id_objetivo">
              <td>
                <span v-if="!objetivo.editando">{{ objetivo.nome_objetivo }}</span>
                <input v-else v-model="objetivo.novoValor" type="text" style="width: 350px;" />
              </td>
              <td>
                <button v-if="!objetivo.editando" @click="iniciarEdicao(objetivo)">Editar</button>
                <span v-else>
                  <button @click="salvarEdicao(objetivo)">Salvar</button>
                  <button @click="cancelarEdicao(objetivo)">Cancelar</button>
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="objetivosAtitudinais.length" style="margin-top:2em;">
        <table border="1" cellpadding="5" style="background: #e6ffe6;">
          <thead>
            <tr>
              <th>Objetivos Atitudinais e Procedimentais</th>
              <th>Editar</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="objetivo in objetivosAtitudinais" :key="objetivo.id_objetivo">
              <td>
                <span v-if="!objetivo.editando">{{ objetivo.nome_objetivo }}</span>
                <input v-else v-model="objetivo.novoValor" type="text" style="width: 350px;" />
              </td>
              <td>
                <button v-if="!objetivo.editando" @click="iniciarEdicao(objetivo)">Editar</button>
                <span v-else>
                  <button @click="salvarEdicao(objetivo)">Salvar</button>
                  <button @click="cancelarEdicao(objetivo)">Cancelar</button>
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div style="margin-top: 2em;">
        <h3>Inserir objetivo</h3>
        <table border="1" cellpadding="8" style="background: #f9f9f9; min-width: 400px;">
          <tr>
            <td>
              <textarea v-model="novoObjetivoTexto" rows="2" style="width: 100%;"></textarea>
            </td>
            <td>
              <select v-model="novoObjetivoTipo" style="width: 120px;">
                <option value="C">Conceitual</option>
                <option value="A">Atitudinal</option>
              </select>
            </td>
            <td>
              <button @click="salvarNovoObjetivo">Salvar</button>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

const materias = ref([])
const materiaSelecionada = ref('')
const turmas = ref([])
const turmaSelecionada = ref('')
const trimestreSelecionado = ref('')
const objetivos = ref([])
const objetivosConceituais = ref([])
const objetivosAtitudinais = ref([])
const porcentagemConceituais = ref(50)
const porcentagemAtitudinais = ref(50)
const novoObjetivoTexto = ref('')
const novoObjetivoTipo = ref('C')

async function carregarMaterias() {
  try {
    const response = await axios.get('/api/materias-professor?id_professor=1')
    materias.value = response.data
  } catch (e) {
    materias.value = []
  }
}

async function carregarTurmas() {
  turmas.value = []
  turmaSelecionada.value = ''
  trimestreSelecionado.value = ''
  objetivos.value = []
  objetivosConceituais.value = []
  objetivosAtitudinais.value = []
  if (!materiaSelecionada.value) return

  // Se a matéria selecionada for História, consome a API Sophia
  const materiaSelecionadaNome = materias.value.find(m => m.id_materia === materiaSelecionada.value)?.nome_materia
  if (materiaSelecionadaNome === 'História') {
    try {
      const response = await axios.get(
        'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2',
        {
          headers: {
            Token: auth.token
          }
        }
      )
      // Filtra turmas que possuem colaboradores com codigo 1092
      //console.log(response.data)

      /*response.data.forEach(turma => {
        turma.professoresDisciplinas.forEach(pd => {
          if(pd.colaboradores[0].codigo == 1092){
            console.log('essa turma é da Cecília!')
          }
        })
      })*/

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
    return
  }

  // Caso contrário, mantém o comportamento antigo (consulta local/api interna)
  try {
    const response = await axios.get(`/api/turmas-professor?professor_id=1&materia_id=${materiaSelecionada.value}`)
    turmas.value = response.data
  } catch (e) {
    turmas.value = []
  }
}

async function carregarObjetivos() {
  objetivos.value = []
  objetivosConceituais.value = []
  objetivosAtitudinais.value = []
  if (!turmaSelecionada.value || !trimestreSelecionado.value) return
  try {
    const response = await axios.get(
      `/api/objetivos?id_materia=${materiaSelecionada.value}&turma_id=${turmaSelecionada.value}&trimestre=${trimestreSelecionado.value}`
    )
    objetivos.value = response.data.map(obj => ({
      ...obj,
      editando: false,
      novoValor: obj.nome_objetivo
    }))
    objetivosConceituais.value = objetivos.value.filter(obj => obj.tipo_objetivo === 'C')
    objetivosAtitudinais.value = objetivos.value.filter(obj => obj.tipo_objetivo === 'A')
    await carregarPorcentagens()
  } catch (e) {
    objetivos.value = []
    objetivosConceituais.value = []
    objetivosAtitudinais.value = []
    porcentagemConceituais.value = 50
    porcentagemAtitudinais.value = 50
  }
}

async function carregarPorcentagens() {
  if (!materiaSelecionada.value || !turmaSelecionada.value || !trimestreSelecionado.value) return
  try {
    const response = await axios.get('/api/porcentagem-objetivos', {
      params: {
        id_materia: materiaSelecionada.value,
        id_turma: turmaSelecionada.value,
        periodo: trimestreSelecionado.value
      }
    })
    if (response.data) {
      porcentagemConceituais.value = response.data.porcentagem_conceitual ?? 50
      porcentagemAtitudinais.value = response.data.porcentagem_atitudinal ?? 50
    } else {
      porcentagemConceituais.value = 50
      porcentagemAtitudinais.value = 50
    }
  } catch (e) {
    porcentagemConceituais.value = 50
    porcentagemAtitudinais.value = 50
  }
}

function iniciarEdicao(objetivo) {
  objetivo.editando = true
  objetivo.novoValor = objetivo.nome_objetivo
}

function cancelarEdicao(objetivo) {
  objetivo.editando = false
  objetivo.novoValor = objetivo.nome_objetivo
}

async function salvarEdicao(objetivo) {
  try {
    await axios.put(`/api/objetivos/${objetivo.id_objetivo}`, {
      nome_objetivo: objetivo.novoValor
    })
    objetivo.nome_objetivo = objetivo.novoValor
    objetivo.editando = false
  } catch (e) {
    alert('Erro ao salvar objetivo!')
  }
}

function atualizarPorcentagem(tipo) {
  if (tipo === 'conceituais') {
    if (porcentagemConceituais.value < 0) porcentagemConceituais.value = 0
    if (porcentagemConceituais.value > 100) porcentagemConceituais.value = 100
    porcentagemAtitudinais.value = 100 - porcentagemConceituais.value
  } else {
    if (porcentagemAtitudinais.value < 0) porcentagemAtitudinais.value = 0
    if (porcentagemAtitudinais.value > 100) porcentagemAtitudinais.value = 100
    porcentagemConceituais.value = 100 - porcentagemAtitudinais.value
  }
}

function salvarPorcentagem() {
  axios.post('/api/porcentagem-objetivos', {
    id_materia: materiaSelecionada.value,
    id_turma: turmaSelecionada.value,
    periodo: trimestreSelecionado.value,
    porcentagem_conceitual: porcentagemConceituais.value,
    porcentagem_atitudinal: porcentagemAtitudinais.value
  }).catch(() => {
    alert('Erro ao salvar porcentagem!')
  })
}

async function salvarNovoObjetivo() {
  if (!novoObjetivoTexto.value.trim()) {
    alert('Digite o objetivo.')
    return
  }
  if (!materiaSelecionada.value || !turmaSelecionada.value || !trimestreSelecionado.value) {
    alert('Selecione matéria, turma e trimestre.')
    return
  }
  try {
    await axios.post('/api/objetivos', {
      nome_objetivo: novoObjetivoTexto.value,
      tipo_objetivo: novoObjetivoTipo.value,
      id_materia: materiaSelecionada.value,
      id_turma: turmaSelecionada.value,
      periodo_objetivo: trimestreSelecionado.value
    })
    novoObjetivoTexto.value = ''
    novoObjetivoTipo.value = 'C'
    await carregarObjetivos()
  } catch (e) {
    alert('Erro ao salvar objetivo!')
  }
}

onMounted(() => {
  carregarMaterias()
  carregarTurmas()
})
</script>
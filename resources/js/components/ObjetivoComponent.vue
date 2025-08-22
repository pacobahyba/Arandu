<template>
  <div>
    <!-- Combo Professor para Administrador -->
    <div v-if="perfilLogado === 'Administrador'" style="margin-bottom: 1em;">
      <label for="professor-select" class="avaliacao-label">Professor:</label>
      <select id="professor-select" class="avaliacao-select" v-model="professorSelecionado" @change="carregarMaterias">
        <option value="">Selecione...</option>
        <option v-for="prof in professores" :key="prof.id_professor" :value="prof.id_professor">
          {{ prof.nome_professor }}
        </option>
      </select>
    </div>

    <!-- Frase só aparece se for Professor -->
    <h2 v-if="perfilLogado == 'Professor'">Turmas de Cecília Schubsky</h2>

    <!-- Combo Matéria só aparece se não for Administrador OU se for Administrador/Professor e já escolheu um professor -->
    <div style="margin-bottom: 1em;" v-if="(perfilLogado !== 'Administrador' && perfilLogado !== 'Professor') || professorSelecionado">
      <label for="materia-select" class="avaliacao-label">Matéria:</label>
      <select id="materia-select" class="avaliacao-select" v-model="materiaSelecionada" @change="carregarTurmas">
        <option value="">Selecione...</option>
        <option v-for="materia in materias" :key="materia.id_materia" :value="materia.id_materia">
          {{ materia.nome_materia }}
        </option>
      </select>
    </div>

    <!-- Combo Turma: agora aparece para qualquer perfil assim que uma matéria for escolhida -->
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
                :disabled="perfilLogado === 'Administrador'"
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
                :disabled="perfilLogado === 'Administrador'"
                @input="atualizarPorcentagem('atitudinais')"
                @blur="salvarPorcentagem"
              /> %
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="objetivosConceituais.length" style="margin-top:2em;">
        <table class="objetivos" border="1" cellpadding="5" style="background: #ffffcc;">
          <thead>
            <tr>
              <th>Objetivos Conceituais</th>
              <th v-if="perfilLogado !== 'Administrador'" width="30%">Editar</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="objetivo in objetivosConceituais" :key="objetivo.id_objetivo">
              <td>
                <span v-if="!objetivo.editando">{{ objetivo.nome_objetivo }}</span>
                <input v-else v-model="objetivo.novoValor" type="text" style="width: 90%;" />
              </td>
              <td v-if="perfilLogado !== 'Administrador'">
                <button v-if="!objetivo.editando" @click="iniciarEdicao(objetivo)">Editar</button>
                <button v-if="!objetivo.editando && perfilLogado === 'Professor'" @click="removerObjetivo(objetivo)" class="bt-remover">Remover</button>
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
        <table class="objetivos" border="1" cellpadding="5" style="background: #e6ffe6;">
          <thead>
            <tr>
              <th>Objetivos Atitudinais e Procedimentais</th>
              <th v-if="perfilLogado !== 'Administrador'" width="30%">Editar</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="objetivo in objetivosAtitudinais" :key="objetivo.id_objetivo">
              <td>
                <span v-if="!objetivo.editando">{{ objetivo.nome_objetivo }}</span>
                <input v-else v-model="objetivo.novoValor" type="text" style="width: 90%;" />
              </td>
              <td v-if="perfilLogado !== 'Administrador'">
                <button v-if="!objetivo.editando" @click="iniciarEdicao(objetivo)">Editar</button>
                <button v-if="!objetivo.editando && perfilLogado === 'Professor'" @click="removerObjetivo(objetivo)" class="bt-remover">Remover</button>
                <span v-else>
                  <button @click="salvarEdicao(objetivo)">Salvar</button>
                  <button @click="cancelarEdicao(objetivo)">Cancelar</button>
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="perfilLogado !== 'Administrador'" style="margin-top: 2em;">
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
const professores = ref([])
const professorSelecionado = ref('')

// Recupera o perfil do usuário logado
const perfilLogado = ref(sessionStorage.getItem('perfil') || 'Não definido')

async function carregarMaterias() {
  materias.value = []
  materiaSelecionada.value = ''
  turmas.value = []
  turmaSelecionada.value = ''
  trimestreSelecionado.value = ''
  objetivos.value = []
  objetivosConceituais.value = []
  objetivosAtitudinais.value = []

  // Para Administrador ou Professor, busca disciplinas do professor selecionado
  if (
    (perfilLogado.value === 'Administrador' && professorSelecionado.value) ||
    (perfilLogado.value === 'Professor' && professorSelecionado.value)
  ) {
    try {
      const response = await axios.get(
        'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2',
        {
          headers: {
            Token: auth.token
          }
        }
      )
      // Extrai disciplinas do professor selecionado
      const disciplinasSet = new Set()
      response.data.forEach(turma => {
        if (Array.isArray(turma.professoresDisciplinas)) {
          turma.professoresDisciplinas.forEach(pd => {
            if (
              Array.isArray(pd.colaboradores) &&
              pd.colaboradores.some(colab => colab.codigo == professorSelecionado.value)
            ) {
              if (pd.disciplina && pd.disciplina.codigo && pd.disciplina.nome) {
                disciplinasSet.add(JSON.stringify({
                  id_materia: pd.disciplina.codigo,
                  nome_materia: pd.disciplina.nome
                }))
              }
            }
          })
        }
      })
      materias.value = Array.from(disciplinasSet).map(str => JSON.parse(str))
    } catch (e) {
      materias.value = []
    }
    return
  }

  // Para outros perfis, mantém o comportamento antigo se necessário
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

  // Para Administrador ou Professor, filtra turmas pelo professor e matéria selecionados
  if (
    (perfilLogado.value === 'Administrador' && professorSelecionado.value && materiaSelecionada.value) ||
    (perfilLogado.value === 'Professor' && professorSelecionado.value && materiaSelecionada.value)
  ) {
    try {
      const response = await axios.get(
        'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2',
        {
          headers: {
            Token: auth.token
          }
        }
      )
      turmas.value = response.data.filter(turma =>
        Array.isArray(turma.professoresDisciplinas) &&
        turma.professoresDisciplinas.some(pd =>
          pd.disciplina &&
          pd.disciplina.codigo == materiaSelecionada.value &&
          Array.isArray(pd.colaboradores) &&
          pd.colaboradores.some(colab => colab.codigo == professorSelecionado.value)
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

  // Para outros perfis, mantém o comportamento antigo se necessário
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

async function removerObjetivo(objetivo) {
  if (!confirm('Deseja realmente remover este objetivo?')) return
  try {
    await axios.delete(`/api/objetivos/${objetivo.id_objetivo}`)
    objetivosConceituais.value = objetivosConceituais.value.filter(o => o.id_objetivo !== objetivo.id_objetivo)
    objetivosAtitudinais.value = objetivosAtitudinais.value.filter(o => o.id_objetivo !== objetivo.id_objetivo)
    // Se quiser remover também de objetivos.value (caso use em outros lugares):
    objetivos.value = objetivos.value.filter(o => o.id_objetivo !== objetivo.id_objetivo)
  } catch (e) {
    alert('Erro ao remover objetivo.')
  }
}

async function carregarProfessores() {
  try {
    const response = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Colaboradores?Unidades=2',
      {
        headers: {
          Token: auth.token
        }
      }
    )
    console.log(professores)
    professores.value = response.data
      .filter(prof => prof.leciona === true || prof.leciona === 'true')
      .map(prof => ({
        id_professor: prof.codigo,
        nome_professor: prof.nome
      }))
      
  } catch (e) {
    professores.value = []
  }
}

onMounted(() => {
  carregarProfessores().then(() => {
    if (perfilLogado.value === 'Professor') {
      // Procura Cecília na lista de professores e seleciona
      const cecilia = professores.value.find(p => p.nome_professor === 'Cecília de Miranda Schubsky')
      if (cecilia) {
        professorSelecionado.value = cecilia.id_professor
        carregarMaterias()
      }
    }
  })
  if (perfilLogado.value !== 'Professor') {
    carregarMaterias()
  }
  carregarTurmas()
})
</script>
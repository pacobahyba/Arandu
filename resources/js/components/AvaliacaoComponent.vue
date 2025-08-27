<template>
  <div class="container" style="display: flex;">
    <div class="left-panel">
      <h2>Ficha de avaliação</h2>
      <label for="periodo-select" class="avaliacao-label">Período:</label>
      <!-- NÃO bloqueie os selects -->
      <select id="periodo-select" v-model="periodoSelecionado" class="avaliacao-select" @change="carregarMaterias">
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
          {{ materia.nome_materia }}
        </option>
      </select>
      <br /><br />

      <!-- Modifique a parte do select de turma -->
      <template v-if="perfilLogado !== 'Aluno'">
        <label for="turma-select" class="avaliacao-label">Turma:</label>
        <select id="turma-select" v-model="turmaSelecionada" :disabled="!materiaSelecionada" class="avaliacao-select" @change="carregarAlunos">
          <option value="">Selecione...</option>
          <option v-for="turma in turmas" :key="turma.id_turma" :value="turma.id_turma">
            {{ turma.nome_turma }}
          </option>
        </select>
        <br /><br />

        <label for="aluno-select" class="avaliacao-label">Aluno:</label>
        <select
          id="aluno-select"
          v-model="alunoSelecionado"
          :disabled="!turmaSelecionada"
          class="avaliacao-select"
        >
          <option value="">Selecione...</option>
          <option v-for="aluno in alunos" :key="aluno.id_aluno" :value="aluno.id_aluno">
            {{ aluno.nome_aluno }}
          </option>
        </select>
        <br /><br />
      </template>

      <!-- Botão Avaliar -->
      <button
        :disabled="!todasSelecionadas"
        @click="aoAvaliar"
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
        <template v-if="carregandoObjetivos">
          <div style="font-size:1.2em; color:#73331d; font-weight:bold; margin-top:2em; display:flex; align-items:center; gap:0.5em;">
            <span class="spinner"></span> Carregando objetivos...
          </div>
        </template>
        <template v-else-if="objetivos.length">
          <!-- Objetivos Conceituais -->
          <table v-if="objetivos.filter(o => o.tipo_objetivo === 'C').length" border="1" cellpadding="5" style="background: #ffffcc; margin-bottom:2em;">
            <thead>
              <tr>
                <th>#</th>
                <th>Objetivos Conceituais</th>
                <th>Est</th>
                <th>Prof</th>
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
                    :disabled="perfilLogado === 'Administrador' ? true : perfilLogado === 'Aluno' ? false : false"
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
                    @input="limitarNota(objetivo, 'prof'); atualizarCF(objetivo)"
                    @blur="salvarNotaProf(objetivo)"
                    :disabled="perfilLogado === 'Administrador' || perfilLogado === 'Aluno'"
                  />
                </td>
                <!-- Remova o <td> referente a CP -->
                <td>
                  <input
                    type="number"
                    v-model="objetivo.rec"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'rec'); atualizarCF(objetivo)"
                    @blur="salvarNotaRec(objetivo)"
                    :disabled="perfilLogado === 'Administrador' || perfilLogado === 'Aluno'"
                  />
                </td>
                <td>
                  <input
                    type="number"
                    v-model="objetivo.cf"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'cf')"
                    @blur="salvarNotaCF(objetivo)"
                    :disabled="perfilLogado === 'Administrador' || perfilLogado === 'Aluno'"
                  />
                </td>
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
                    :disabled="perfilLogado === 'Administrador' ? true : perfilLogado === 'Aluno' ? false : false"
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
                    @input="limitarNota(objetivo, 'prof'); atualizarCF(objetivo)"
                    @blur="salvarNotaProf(objetivo)"
                    :disabled="perfilLogado === 'Administrador' || perfilLogado === 'Aluno'"
                  />
                </td>
                <!-- Remova o <td> referente a CP -->
                <td>
                  <input
                    type="number"
                    v-model="objetivo.rec"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'rec'); atualizarCF(objetivo)"
                    @blur="salvarNotaRec(objetivo)"
                    :disabled="perfilLogado === 'Administrador' || perfilLogado === 'Aluno'"
                  />
                </td>
                <td>
                  <input
                    type="number"
                    v-model="objetivo.cf"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'cf')"
                    @blur="salvarNotaCF(objetivo)"
                    :disabled="perfilLogado === 'Administrador' || perfilLogado === 'Aluno'"
                  />
                </td>
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
    <div class="right-panel" v-if="mostrarTabela">
      <!-- Painel de notas, porcentagens, nota final -->
      <div
        id="notas-panel"
        v-if="mostrarTabela && objetivos.length"
      >
        <table border="1" cellpadding="8" style="background: #dde3f0; min-width: 260px; text-align: center;">
          <tr style="background: #d3daea;">
            <td colspan="4" style="font-weight: bold;">NOTAS</td>
          </tr>
          <tr style="background: #f5f7fa;">
            <th>Est</th>
            <th>Prof</th>
            <th>Rec</th>
            <th>CF</th>
          </tr>
          <tr id="linha1">
            <td id="media-conceituais">{{ mediaConceituais }}</td>
            <td id="media-prof-conceituais">{{ mediaProfConceituais }}</td>
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
            <td>A</td>
            <td id="media-cf-atitudinais">{{ mediaCFAtitudinais }}</td>
          </tr>
          <tr id="linha3">
            <td id="total-medias">{{ totalMedias }}</td>
            <td id="total-prof-medias">{{ totalProfMedias }}</td>
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

const perfilLogado = ref(sessionStorage.getItem('perfil') || 'Não definido')

const materias = ref([])
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
const carregandoObjetivos = ref(false)

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

const todasSelecionadas = computed(() => {
  if (perfilLogado.value === 'Aluno') {
    // Para alunos, só precisa de matéria e período
    return materiaSelecionada.value && periodoSelecionado.value
  } else {
    // Para professores e outros perfis, precisa de todas as seleções
    return materiaSelecionada.value && 
           turmaSelecionada.value && 
           alunoSelecionado.value && 
           periodoSelecionado.value
  }
})

async function carregarTurmas() {
  turmas.value = []
  turmaSelecionada.value = ''
  if (!materiaSelecionada.value) return

  const codigoProfessor = sessionStorage.getItem('codigo')
  try {
    const response = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2',
      {
        headers: {
          Token: sessionStorage.getItem('tokenSophia')
        }
      }
    )
    turmas.value = response.data
      .filter(turma =>
        Array.isArray(turma.professoresDisciplinas) &&
        turma.professoresDisciplinas.some(pd =>
          pd.disciplina &&
          pd.disciplina.codigo == materiaSelecionada.value &&
          Array.isArray(pd.colaboradores) &&
          pd.colaboradores.some(colab => String(colab.codigo) === String(codigoProfessor))
        )
      )
      .map(turma => ({
        id_turma: turma.codigo,
        nome_turma: turma.nome
      }))
  } catch (e) {
    turmas.value = []
  }
}

// Substitua a função carregarMaterias() existente por esta:
async function carregarMaterias() {
  materias.value = [];
  materiaSelecionada.value = '';
  turmas.value = [];
  turmaSelecionada.value = '';
  
  // Verifica o perfil do usuário logado
  const perfil = sessionStorage.getItem('perfil');
  
  if (perfil === 'Professor') {
    // CASO PROFESSOR: Busca disciplinas pelos códigos armazenados na sessão
    const codigos = JSON.parse(sessionStorage.getItem('codigo_disciplina') || '[]');
    if (!codigos.length) return;
    
    try {
      const url = `https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Disciplinas?Codigos=${codigos.join(',')}`;
      const resp = await axios.get(url, {
        headers: {
          Token: sessionStorage.getItem('tokenSophia')
        }
      });
      
      materias.value = Array.isArray(resp.data)
        ? resp.data.map(d => ({
            id_materia: d.codigo,
            nome_materia: d.nome
          }))
        : [];
    } catch (e) {
      materias.value = [];
    }
  } 
  else if (perfil === 'Aluno') {
    // CASO ALUNO: Busca disciplinas das turmas do aluno
    const professoresDisciplinas = JSON.parse(sessionStorage.getItem('professoresDisciplinas') || '[]');
    
    // Extrai disciplinas únicas do array professoresDisciplinas
    const disciplinasUnicas = [];
    const codigosJaAdicionados = new Set();
    
    professoresDisciplinas.forEach(pd => {
      if (pd.disciplina && pd.disciplina.codigo && !codigosJaAdicionados.has(pd.disciplina.codigo)) {
        disciplinasUnicas.push({
          id_materia: pd.disciplina.codigo,
          nome_materia: pd.disciplina.nome
        });
        codigosJaAdicionados.add(pd.disciplina.codigo);
      }
    });
    
    // Ordena por nome para melhor visualização
    materias.value = disciplinasUnicas.sort((a, b) => 
      a.nome_materia.localeCompare(b.nome_materia)
    );
  }
}

// Carregar alunos da turma selecionada
async function carregarAlunos() {
  alunos.value = []
  alunoSelecionado.value = ''
  if (!turmaSelecionada.value) return

  try {
    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7&Inativos=false&ConsiderarTurmaFinalizada=false'
    const resp = await axios.get(url, {
      headers: {
        Token: sessionStorage.getItem('tokenSophia')
      }
    })
    alunos.value = Array.isArray(resp.data)
      ? resp.data.filter(aluno =>
          Array.isArray(aluno.turmas) &&
          aluno.turmas.some(turma => String(turma.codigo) === String(turmaSelecionada.value))
        ).map(aluno => ({
          id_aluno: aluno.codigo,
          nome_aluno: aluno.nome
        }))
      : []
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

async function salvarNotaCF(objetivo) {
  try {
    await axios.post('/api/notas', {
      id_objetivo: objetivo.id_objetivo,
      nota: objetivo.cf,
      tipo_nota: 'CF',
      id_aluno: alunoSelecionado.value,
      periodo: periodoSelecionado.value
    })
  } catch (e) {
    alert('Erro ao salvar nota CF!')
  }
}

async function carregarObjetivos() {
  objetivos.value = []
  carregandoObjetivos.value = true
  if (
    !materiaSelecionada.value ||
    !turmaSelecionada.value ||
    !periodoSelecionado.value
  ) {
    carregandoObjetivos.value = false
    return
  }

  try {
    const resp = await axios.get('/api/objetivos', {
      params: {
        id_materia: materiaSelecionada.value,
        turma_id: turmaSelecionada.value,
        trimestre: periodoSelecionado.value
      }
    })
    objetivos.value = Array.isArray(resp.data) ? resp.data : []
  } catch (e) {
    objetivos.value = []
  }
  carregandoObjetivos.value = false
}

watchEffect(async () => {
  // Para alunos, sempre use o código do aluno armazenado na sessão
  if (perfilLogado.value === 'Aluno') {
    alunoSelecionado.value = sessionStorage.getItem('aluno_codigo')
  }

  // Carrega objetivos e notas quando mostrarTabela for true
  if (
    mostrarTabela.value &&
    materiaSelecionada.value &&
    ((perfilLogado.value === 'Aluno' && alunoSelecionado.value) || 
     (perfilLogado.value !== 'Aluno' && turmaSelecionada.value && alunoSelecionado.value)) &&
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

watchEffect(() => {
  if (turmaSelecionada.value) {
    carregarAlunos()
  } else {
    alunos.value = []
    alunoSelecionado.value = ''
  }
})

// Carregar matérias ao montar o componente
onMounted(() => {
  // Preencher valores automaticamente se for aluno
  if (perfilLogado.value === 'Aluno') {
    // Usa o código do aluno da sessão
    alunoSelecionado.value = sessionStorage.getItem('aluno_codigo')
    
    // Se houver apenas uma turma, seleciona automaticamente
    const codigosTurma = JSON.parse(sessionStorage.getItem('aluno_codigos_turma') || '[]')
    if (codigosTurma.length === 1) {
      turmaSelecionada.value = codigosTurma[0]
    }
  }
  
  carregarMaterias()
})

function aoAvaliar() {
  if (perfilLogado.value === 'Aluno') {
    // Para alunos, use o código do aluno e turma da sessão
    alunoSelecionado.value = sessionStorage.getItem('aluno_codigo')
    
    // Tenta usar a primeira turma se houver várias
    if (!turmaSelecionada.value) {
      const codigosTurma = JSON.parse(sessionStorage.getItem('aluno_codigos_turma') || '[]')
      if (codigosTurma.length > 0) {
        turmaSelecionada.value = codigosTurma[0]
      }
    }
  }
  
  // Continua normalmente
  carregarObjetivos()
  mostrarTabela.value = true
}
</script>

<style>
.spinner {
  display: inline-block;
  width: 1em;
  height: 1em;
  border: 2px solid #b91509;
  border-top: 2px solid transparent;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  vertical-align: middle;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
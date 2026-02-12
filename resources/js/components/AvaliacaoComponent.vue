<template>
  <div class="container">
    <h2 style="text-align: center; width: 100%; display: block; margin: 0 auto 1em auto;">Ficha de avaliação</h2>
    
    <!-- Formulário de seleção -->
    <div class="selecao-container">

      <label for="ano-select" class="avaliacao-label">Ano:</label>
      <select id="ano-select" v-model="anoSelecionado" class="avaliacao-select" style="width: 120px;">
        <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
      </select>

      <label for="periodo-select" class="avaliacao-label">Período:</label>
      <select id="periodo-select" v-model="periodoSelecionado" class="avaliacao-select" style="width: 120px;" @change="carregarMaterias">
        <option value="">Selecione...</option>
        <option value="1º Tri">1º Tri</option>
        <option value="2º Tri">2º Tri</option>
        <option value="3º Tri">3º Tri</option>
      </select>
      <br /><br />

      <!-- Aviso de período expirado (só após seleção de ano e período) -->
      <div v-if="anoSelecionado && periodoSelecionado && periodoExpirado" style="margin: 0.5em 0 1em; padding: 1em; background-color: #fff3cd; border: 2px solid #ffc107; border-radius: 5px; color: #856404; font-weight: bold;">
        ⚠️ O período de avaliação expirou em {{ dataExpiracao }}.
      </div>

      <label for="materia-select" class="avaliacao-label">Matéria:</label>
      <select id="materia-select" v-model="materiaSelecionada" @change="carregarTurmas" class="avaliacao-select">
        <option value="">Selecione...</option>
        <option v-for="materia in materias" :key="materia.id_materia" :value="materia.id_materia">
          {{ materia.nome_materia }}
        </option>
      </select>
      <br /><br />

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

      <button
        :disabled="!todasSelecionadas"
        @click="aoAvaliar"
        style="padding: 0.7em 2em; font-size: 1em; font-weight: bold;"
      >
        {{ periodoExpirado ? 'Ver avaliação' : 'Avaliar' }}
      </button>
    </div>

    <!-- Painel de notas - centralizado -->
    <div 
      id="notas-panel"
      v-if="mostrarTabela && objetivos.length && porcentagemConceituais !== null && porcentagemAtitudinais !== null"
      style="margin: 2em auto; max-width: 600px;"
      class="painel-notas-container"
    >
      <div class="avaliacao-paineis" style="display: flex; gap: 2em; justify-content: space-between;">
        <!-- Porcentagens da nota -->
  <div class="avaliacao-painel avaliacao-painel-porcentagens" style="background:#eef; padding:1em; border-radius:8px;">
          <h3 style="margin-top:0;">Porcentagens da nota</h3>
          <table border="1" cellpadding="5" style="width:100%; margin: 0;">
            <thead>
              <tr>
                <th></th>
                <th>%</th>
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

        <!-- Tabela de notas -->
  <div class="avaliacao-painel avaliacao-painel-notas">
          <table border="1" cellpadding="8" style="background: #dde3f0; width: 100%; text-align: center;">
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
              <td id="media-cf-conceituais">{{ mediaCFConceituais }}</td>
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
          <div style="margin-top: 1em; font-size: 1.2em; font-weight: bold; text-align: center;">
            Nota final: {{ totalCFMedias }}
          </div>
        </div>
      </div>
    </div>

    <!-- Tabela de objetivos centralizada -->
    <div v-if="mostrarTabela" style="margin-top:2em; width: 100%; display: block; clear: both;">
      <template v-if="carregandoObjetivos">
        <div style="font-size:1.2em; color:#73331d; font-weight:bold; margin-top:2em; display:flex; align-items:center; gap:0.5em; justify-content: center;">
          <span class="spinner"></span> Carregando objetivos...
        </div>
      </template>
      <template v-else-if="objetivos.length">
        <!-- Objetivos Conceituais - centralizado -->
        <table v-if="objetivos.filter(o => o.tipo_objetivo === 'C').length" 
               border="1" 
               cellpadding="5" 
               class="tabela-objetivos-conceituais"
               style="background: #ffffcc; margin:0 auto 2em; width:90%; min-width:700px; max-width:1200px;"
        >
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
                <template v-if="periodoExpirado">
                  {{ objetivo.est }}
                </template>
                <template v-else>
                  <input
                    type="number"
                    v-model="objetivo.est"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'est')"
                    @blur="salvarNotaEst(objetivo)"
                    :disabled="perfilLogado === 'Administrativo' || perfilLogado === 'Professor' ? true : perfilLogado === 'Aluno' ? false : false"
                  />
                </template>
              </td>
              <!-- Prof -->
              <td>
                <template v-if="periodoExpirado">
                  {{ objetivo.prof }}
                </template>
                <template v-else>
                  <input
                    type="number"
                    v-model="objetivo.prof"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'prof')"
                    @blur="salvarNotaProf(objetivo)"
                    :disabled="perfilLogado === 'Administrativo' || perfilLogado === 'Aluno'"
                  />
                </template>
              </td>
              <!-- Remova o <td> referente a CP -->
              <td>
                <template v-if="periodoExpirado">
                  {{ objetivo.rec }}
                </template>
                <template v-else>
                  <input
                    type="number"
                    v-model="objetivo.rec"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'rec')"
                    @blur="salvarNotaRec(objetivo)"
                    :disabled="perfilLogado === 'Administrativo' || perfilLogado === 'Aluno'"
                  />
                </template>
              </td>
              <td>
                <template v-if="periodoExpirado">
                  {{ objetivo.cf }}
                </template>
                <template v-else>
                  <input
                    type="number"
                    v-model="objetivo.cf"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="onCFChange(objetivo)"
                    :disabled="perfilLogado === 'Administrativo' || perfilLogado === 'Aluno'"
                  />
                </template>
              </td>
            </tr>
          </tbody>
        </table>
        
        <!-- Objetivos Atitudinais e Procedimentais - centralizado -->
        <table v-if="objetivos.filter(o => o.tipo_objetivo === 'A').length" 
               border="1" 
               cellpadding="5" 
               class="tabela-objetivos-atitudinais"
               style="background: #e6ffe6; width:90%; min-width:700px; max-width:1200px; margin:0 auto;"
        >
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
                <template v-if="periodoExpirado">
                  {{ objetivo.est }}
                </template>
                <template v-else>
                  <input
                    type="number"
                    v-model="objetivo.est"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'est')"
                    @blur="salvarNotaEst(objetivo)"
                    :disabled="perfilLogado === 'Administrativo' || perfilLogado === 'Professor' ? true : perfilLogado === 'Aluno' ? false : false"
                  />
                </template>
              </td>
              <!-- Prof -->
              <td>
                <template v-if="periodoExpirado">
                  {{ objetivo.prof }}
                </template>
                <template v-else>
                  <input
                    type="number"
                    v-model="objetivo.prof"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'prof')"
                    @blur="salvarNotaProf(objetivo)"
                    :disabled="perfilLogado === 'Administrativo' || perfilLogado === 'Aluno'"
                  />
                </template>
              </td>
              <!-- Remova o <td> referente a CP -->
              <td>
                <template v-if="periodoExpirado">
                  {{ objetivo.rec }}
                </template>
                <template v-else>
                  <input
                    type="number"
                    v-model="objetivo.rec"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'rec')"
                    @blur="salvarNotaRec(objetivo)"
                    :disabled="perfilLogado === 'Administrativo' || perfilLogado === 'Aluno'"
                  />
                </template>
              </td>
              <td>
                <template v-if="periodoExpirado">
                  {{ objetivo.cf }}
                </template>
                <template v-else>
                  <input
                    type="number"
                    v-model="objetivo.cf"
                    :max="10"
                    min="0"
                    style="width: 50px;"
                    @input="limitarNota(objetivo, 'cf'); salvarNotaCF(objetivo);"
                    :disabled="perfilLogado === 'Administrativo' || perfilLogado === 'Aluno'"
                  />
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </template>
      <template v-else>
        <div style="font-size:1.2em; color:#73331d; font-weight:bold; margin-top:2em; text-align: center;">
          Não há objetivos cadastrados para esse período.
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, watchEffect } from 'vue'
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
const anosDisponiveis = ref([])
const anoSelecionado = ref(new Date().getFullYear())
const idDisciplinaMae = ref(null) // Armazena id_disciplina_mae para turmas customizadas

// Controle de período expirado
const periodoExpirado = ref(false)
const dataExpiracao = ref('')

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

async function carregarMaterias() {
  materias.value = [];
  materiaSelecionada.value = '';
  turmas.value = [];
  turmaSelecionada.value = '';

  const perfil = sessionStorage.getItem('perfil');

  if (perfil === 'Professor') {
    // Professor: lista todas as disciplinas (inclusive 11)
    const codigos = JSON.parse(sessionStorage.getItem('codigo_disciplina') || '[]');
    if (!codigos.length) return;
    try {
      const url = `https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Disciplinas?Codigos=${codigos.join(',')}`;
      const resp = await axios.get(url, {
        headers: { Token: sessionStorage.getItem('tokenSophia') }
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
  } else if (perfil === 'Aluno') {
    // Matérias padrão (exceto 11)
    const professoresDisciplinas = JSON.parse(sessionStorage.getItem('professoresDisciplinas') || '[]');
    const disciplinasUnicas = [];
    const codigosJaAdicionados = new Set();

    professoresDisciplinas.forEach(pd => {
      if (
        pd.disciplina &&
        pd.disciplina.codigo &&
        pd.disciplina.codigo !== 11 &&
        !codigosJaAdicionados.has(pd.disciplina.codigo)
      ) {
        disciplinasUnicas.push({
          id_materia: pd.disciplina.codigo,
          nome_materia: pd.disciplina.nome
        });
        codigosJaAdicionados.add(pd.disciplina.codigo);
      }
    });

    // Adiciona matérias customizadas do aluno (ex: Inglês A)
    try {
      const resp = await axios.get('/api/turmas-alunos-customizados', {
        params: { id_aluno: sessionStorage.getItem('aluno_codigo') }
      });
      if (Array.isArray(resp.data)) {
        resp.data.forEach(tc => {
          // Adiciona como matéria extra, se não existir
          if (!disciplinasUnicas.some(m => m.nome_materia === tc.nome_turma)) {
            disciplinasUnicas.push({
              id_materia: `custom_${tc.id_turma}`, // identificador único para customizada
              nome_materia: tc.nome_turma
            });
          }
        });
      }
    } catch (e) {
      // ignora erro silenciosamente
    }

    materias.value = disciplinasUnicas.sort((a, b) =>
      a.nome_materia.localeCompare(b.nome_materia)
    );
  }
}

// Carregar turmas da matéria selecionada
async function carregarTurmas() {
  turmas.value = [];
  turmaSelecionada.value = '';
  if (!materiaSelecionada.value) return;

  if (String(materiaSelecionada.value).startsWith('custom_')) {
    // Extrai o id da turma customizada
    const turmaId = materiaSelecionada.value.replace('custom_', '');
    // Busca a turma customizada no backend
    try {
      const resp = await axios.get('/api/turmas-customizadas', {
        params: { id_turma: turmaId }
      });
      turmas.value = Array.isArray(resp.data)
        ? resp.data.map(t => ({
            id_turma: t.id ?? t.id_turma ?? turmaId,
            nome_turma: t.nome_turma
          }))
        : [];
      // Armazena id_disciplina_mae para uso posterior
      if (resp.data && resp.data.length > 0 && resp.data[0].id_disciplina_mae) {
        idDisciplinaMae.value = resp.data[0].id_disciplina_mae;
      }
      // Se só existe uma turma, seleciona automaticamente
      if (turmas.value.length === 1) {
        turmaSelecionada.value = turmas.value[0].id_turma;
      }
    } catch (e) {
      turmas.value = [];
    }
    return;
  }

  // Buscar turmas do Sophia primeiro
  const codigoProfessor = sessionStorage.getItem('codigo');
  let turmasSophia = []
  
  try {
    const response = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2',
      { headers: { Token: sessionStorage.getItem('tokenSophia') } }
    );
    turmasSophia = response.data
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
        nome_turma: turma.nome,
        isCustomizada: false
      }));
  } catch (e) {
    console.error('Erro ao carregar turmas Sophia:', e)
    turmasSophia = []
  }

  // Se a disciplina for uma das listadas, buscar também turmas customizadas
  // Química(6), Inglês(11), Arte(22), Educação Musical(26), Teatro do Oprimido(93)
  let turmasCustomizadas = []
  if (['6','11','22','26','93'].includes(String(materiaSelecionada.value))) {
    try {
      const resp = await axios.get('/api/turmas-customizadas', {
        params: { id_disciplina_mae: materiaSelecionada.value }
      })
      turmasCustomizadas = Array.isArray(resp.data)
        ? resp.data
            .filter(t => (t.id ?? t.id_turma_customizada) != null)
            .map(t => ({
              id_turma: t.id ?? t.id_turma_customizada,
              nome_turma: `${t.nome_turma} (Customizada)`,
              isCustomizada: true
            }))
        : []
    } catch (e) {
      console.error('Erro ao carregar turmas customizadas:', e)
      turmasCustomizadas = []
    }
  }

  // Mesclar turmas Sophia com turmas customizadas
  turmas.value = [...turmasSophia, ...turmasCustomizadas]
}

// Carregar alunos da turma selecionada
async function carregarAlunos() {
  alunos.value = []
  alunoSelecionado.value = ''
  if (!turmaSelecionada.value) return

  // Verificar se a turma selecionada é customizada
  const turmaObj = turmas.value.find(t => String(t.id_turma) === String(turmaSelecionada.value))
  const ehTurmaCustomizada = turmaObj?.isCustomizada === true

  // Se for turma customizada das disciplinas especiais, busca alunos da tabela customizada
  if (ehTurmaCustomizada && ['6', '11', '22', '26', '93'].includes(String(materiaSelecionada.value))) {
    try {
      const resp = await axios.get(`/api/turmas-customizadas/${turmaSelecionada.value}/alunos`)
      alunos.value = Array.isArray(resp.data)
        ? resp.data
            .filter(a => a.dt_fim == null)
            .map(a => ({
              id_aluno: a.id_aluno,
              nome_aluno: a.nome // ajuste se backend retorna nome, senão busque do endpoint Sophia
            }))
        : []
      // Se backend não retorna nome, busque do endpoint Sophia:
      if (alunos.value.some(a => !a.nome_aluno)) {
        const tokenSophia = sessionStorage.getItem('tokenSophia')
        const respSophia = await axios.get(
          'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7&Inativos=false&ConsiderarTurmaFinalizada=false',
          { headers: { Token: tokenSophia } }
        )
        const mapa = new Map(respSophia.data.map(a => [a.codigo, a.nome]))
        alunos.value.forEach(a => {
          if (!a.nome_aluno) a.nome_aluno = mapa.get(a.id_aluno) || '(Nome não encontrado)'
        })
        alunos.value.sort((a, b) => a.nome_aluno.localeCompare(b.nome_aluno))
      }
    } catch (e) {
      console.error('Erro ao carregar alunos turmas customizadas:', e)
      alunos.value = []
    }
    return
  }

  // Fluxo para turmas normais do Sophia (tanto para disciplinas especiais quanto outras)
  try {
    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7&Inativos=false&ConsiderarTurmaFinalizada=false'
    const resp = await axios.get(url, {
      headers: { Token: sessionStorage.getItem('tokenSophia') }
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
  let idMateria = materiaSelecionada.value
  let idTurma = turmaSelecionada.value

  // Se for turma customizada, usa id_disciplina_mae dinâmico
  if (String(idMateria).startsWith('custom_')) {
    idTurma = idMateria.replace('custom_', '')
    try {
      const list = await axios.get('/api/turmas-customizadas')
      const row = (list.data || []).find(t => String(t.id) === String(idTurma))
      idMateria = String(row?.id_disciplina_mae ?? (idDisciplinaMae.value || idMateria))
    } catch {
      idMateria = idDisciplinaMae.value || idMateria
    }
  } else if (String(idTurma).startsWith('custom_')) {
    idTurma = idTurma.replace('custom_', '')
    try {
      const list = await axios.get('/api/turmas-customizadas')
      const row = (list.data || []).find(t => String(t.id) === String(idTurma))
      idMateria = String(row?.id_disciplina_mae ?? (idDisciplinaMae.value || idMateria))
    } catch {
      idMateria = idDisciplinaMae.value || idMateria
    }
  }

  const resp = await axios.get('/api/porcentagem-objetivos', {
    params: {
      id_materia: idMateria,
      id_turma: idTurma,
      periodo: periodoSelecionado.value,
      ano: anoSelecionado.value
    }
  })
  porcentagemConceituais.value = resp.data?.porcentagem_conceitual ?? 0
  porcentagemAtitudinais.value = resp.data?.porcentagem_atitudinal ?? 0
}

function limitarNota(objetivo, campo) {
  if (objetivo[campo] > 10) objetivo[campo] = 10
  if (objetivo[campo] < 0 || objetivo[campo] === null) objetivo[campo] = 0
}

function atualizarCP(objetivo) {
  objetivo.cp = objetivo.prof
  salvarNotaCP(objetivo)
}

// Removido: CF não deve ser preenchido automaticamente em nenhuma circunstância

// Função para salvar nota CF
async function salvarNotaCF(objetivo) {
  if (objetivo.cf === null || objetivo.cf === undefined || objetivo.cf === '') return;
  
  try {
    await axios.post('/api/notas', {
      id_objetivo: objetivo.id_objetivo,
      nota: objetivo.cf,
      tipo_nota: 'CF',
      id_aluno: alunoSelecionado.value,
      periodo: periodoSelecionado.value,
      perfil: sessionStorage.getItem('perfil')
    });
  } catch (e) {
    console.error('Erro ao salvar nota CF:', e);
  }
}

async function salvarNotaEst(objetivo) {
  try {
    console.log('Enviando para /api/notas:', {
      id_objetivo: objetivo.id_objetivo,
      nota: objetivo.est,
      tipo_nota: 'Est',
      id_aluno: alunoSelecionado.value,
      periodo: periodoSelecionado.value,
      perfil: sessionStorage.getItem('perfil')
    })
    await axios.post('/api/notas', {
      id_objetivo: objetivo.id_objetivo,
      nota: objetivo.est,
      tipo_nota: 'Est',
      id_aluno: alunoSelecionado.value,
      periodo: periodoSelecionado.value,
      perfil: sessionStorage.getItem('perfil')
    })
  } catch (e) {
    if (e.response && e.response.data && e.response.data.message) {
      alert(e.response.data.message)
    } else {
      alert('Erro ao salvar nota!')
    }
  }
}

async function salvarNotaProf(objetivo) {
  try {
    await axios.post('/api/notas', {
      id_objetivo: objetivo.id_objetivo,
      nota: objetivo.prof,
      tipo_nota: 'Prof',
      id_aluno: alunoSelecionado.value,
      periodo: periodoSelecionado.value,
      perfil: sessionStorage.getItem('perfil')
    })
  } catch (e) {
    if (e.response && e.response.data && e.response.data.message) {
      alert(e.response.data.message)
    } else {
      alert('Erro ao salvar nota!')
    }
  }
}

async function salvarNotaRec(objetivo) {
  try {
    await axios.post('/api/notas', {
      id_objetivo: objetivo.id_objetivo,
      nota: objetivo.rec,
      tipo_nota: 'Rec',
      id_aluno: alunoSelecionado.value,
      periodo: periodoSelecionado.value,
      perfil: sessionStorage.getItem('perfil')
    })
  } catch (e) {
    if (e.response && e.response.data && e.response.data.message) {
      alert(e.response.data.message)
    } else {
      alert('Erro ao salvar nota!')
    }
  }
}

async function salvarNotaCP(objetivo) {
  try {
    await axios.post('/api/notas', {
      id_objetivo: objetivo.id_objetivo,
      nota: objetivo.cp,
      tipo_nota: 'CP',
      id_aluno: alunoSelecionado.value,
      periodo: periodoSelecionado.value,
      perfil: sessionStorage.getItem('perfil')
    })
  } catch (e) {
    if (e.response && e.response.data && e.response.data.message) {
      alert(e.response.data.message)
    } else {
      alert('Erro ao salvar nota!')
    }
  }
}

async function carregarObjetivos() {
  objetivos.value = []
  carregandoObjetivos.value = true
  if (!materiaSelecionada.value || !turmaSelecionada.value || !periodoSelecionado.value) {
    carregandoObjetivos.value = false
    return
  }

  // Ajuste para turmas customizadas
  let turmaId = turmaSelecionada.value
  let idMateria = materiaSelecionada.value
  if (String(materiaSelecionada.value).startsWith('custom_')) {
    turmaId = materiaSelecionada.value.replace('custom_', '')
    try {
      const list = await axios.get('/api/turmas-customizadas')
      const row = (list.data || []).find(t => String(t.id) === String(turmaId))
      idMateria = String(row?.id_disciplina_mae ?? idDisciplinaMae.value)
    } catch {
      idMateria = idDisciplinaMae.value
    }
  } else if (String(turmaId).startsWith('custom_')) {
    turmaId = turmaId.replace('custom_', '')
    try {
      const list = await axios.get('/api/turmas-customizadas')
      const row = (list.data || []).find(t => String(t.id) === String(turmaId))
      idMateria = String(row?.id_disciplina_mae ?? idDisciplinaMae.value)
    } catch {
      idMateria = idDisciplinaMae.value
    }
  }

  try {
    const resp = await axios.get('/api/objetivos', {
      params: {
        id_materia: idMateria,
        turma_id: turmaId,
        trimestre: periodoSelecionado.value,
        ano: anoSelecionado.value,
        id_aluno: alunoSelecionado.value || null
      }
    })
    const objetivosArr = Array.isArray(resp.data) ? resp.data : []

    // Busca as notas do aluno
    const notasArr = await carregarNotas()

    // Preenche os campos de nota nos objetivos
    objetivosArr.forEach(obj => {
      obj.est = notasArr.find(n => n.id_objetivo === obj.id_objetivo && n.tipo_nota === 'Est')?.nota ?? ''
      obj.prof = notasArr.find(n => n.id_objetivo === obj.id_objetivo && n.tipo_nota === 'Prof')?.nota ?? ''
      obj.rec = notasArr.find(n => n.id_objetivo === obj.id_objetivo && n.tipo_nota === 'Rec')?.nota ?? ''
      obj.cp  = notasArr.find(n => n.id_objetivo === obj.id_objetivo && n.tipo_nota === 'CP')?.nota ?? ''
      obj.cf  = notasArr.find(n => n.id_objetivo === obj.id_objetivo && n.tipo_nota === 'CF')?.nota ?? ''
    })

    objetivos.value = objetivosArr
  } catch (e) {
    objetivos.value = []
  }
  carregandoObjetivos.value = false
}

async function carregarAnosObjetivos() {
  try {
    const resp = await axios.get('/api/anos-objetivos')
    anosDisponiveis.value = resp.data
    // Seleciona o ano atual por padrão
    anoSelecionado.value = anosDisponiveis.value[0] || new Date().getFullYear()
  } catch {
    anosDisponiveis.value = [new Date().getFullYear()]
    anoSelecionado.value = new Date().getFullYear()
  }
}

watchEffect(async () => {
  if (perfilLogado.value === 'Aluno') {
    alunoSelecionado.value = sessionStorage.getItem('aluno_codigo')
  }
  if (
    mostrarTabela.value &&
    materiaSelecionada.value &&
    ((perfilLogado.value === 'Aluno' && alunoSelecionado.value) ||
     (perfilLogado.value !== 'Aluno' && turmaSelecionada.value && alunoSelecionado.value)) &&
    periodoSelecionado.value
  ) {
    await carregarPorcentagens()
    await carregarObjetivos()
    // Se quiser, carregue as notas aqui também
    // ...
  }
})

watch(turmaSelecionada, async (novo, antigo) => {
  if (novo) {
    await carregarAlunos()
    // Mantém alunoSelecionado se ele ainda existir na lista; caso contrário limpa
    if (alunoSelecionado.value && !alunos.value.some(a => String(a.id_aluno) === String(alunoSelecionado.value))) {
      alunoSelecionado.value = ''
    }
  } else {
    alunos.value = []
    alunoSelecionado.value = ''
  }
})

// (Opcional) Adicione logs para depurar quando o botão desabilita
watch([materiaSelecionada, turmaSelecionada, alunoSelecionado, periodoSelecionado], () => {
  // console.log('DEBUG seleções:', {
  //   materia: materiaSelecionada.value,
  //   turma: turmaSelecionada.value,
  //   aluno: alunoSelecionado.value,
  //   periodo: periodoSelecionado.value,
  //   todasSelecionadas: todasSelecionadas.value
  // })
})

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
  carregarAnosObjetivos()
})

async function verificarPeriodo() {
  periodoExpirado.value = false
  dataExpiracao.value = ''

  if (!anoSelecionado.value || !periodoSelecionado.value) return

  try {
    const resp = await axios.get('/api/periodos-avaliacao', {
      params: {
        ano: anoSelecionado.value,
        periodo: periodoSelecionado.value
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

// Dispara verificação ao trocar ano ou período (após seleção)
watch([anoSelecionado, periodoSelecionado], async ([ano, periodo]) => {
  if (ano && periodo) {
    await verificarPeriodo()
  } else {
    periodoExpirado.value = false
    dataExpiracao.value = ''
  }
})

async function aoAvaliar() {
  if (perfilLogado.value === 'Aluno') {
    // Para alunos, use o código do aluno e turma da sessão
    alunoSelecionado.value = sessionStorage.getItem('aluno_codigo')
    
    // Para alunos, precisamos determinar qual turma usar baseado na matéria selecionada
    const professoresDisciplinas = JSON.parse(sessionStorage.getItem('professoresDisciplinas') || '[]')
    
    // Busca a turma que tem a disciplina selecionada
    const disciplinaEncontrada = professoresDisciplinas.find(pd => 
      pd.disciplina && pd.disciplina.codigo === materiaSelecionada.value
    )
    
    if (disciplinaEncontrada && disciplinaEncontrada.turmaCodigo) {
      turmaSelecionada.value = disciplinaEncontrada.turmaCodigo
    } else if (!turmaSelecionada.value) {
      // Fallback: usa a primeira turma se houver várias
      const codigosTurma = JSON.parse(sessionStorage.getItem('aluno_codigos_turma') || '[]')
      if (codigosTurma.length > 0) {
        turmaSelecionada.value = codigosTurma[0]
      }
    }
  }
  
  // Aguarda carregar porcentagens e objetivos
  await carregarPorcentagens()
  await carregarObjetivos()
  mostrarTabela.value = true
}

async function carregarNotas() {
  if (!alunoSelecionado.value || !turmaSelecionada.value || !periodoSelecionado.value) return []
  let idMateria = materiaSelecionada.value
  let idTurma = turmaSelecionada.value

  // Ajuste para turmas customizadas
  if (String(idMateria).startsWith('custom_')) {
    idTurma = idMateria.replace('custom_', '')
    try {
      const list = await axios.get('/api/turmas-customizadas')
      const row = (list.data || []).find(t => String(t.id) === String(idTurma))
      idMateria = String(row?.id_disciplina_mae ?? (idDisciplinaMae.value || idMateria))
    } catch {
      idMateria = idDisciplinaMae.value || idMateria
    }
  } else if (String(idTurma).startsWith('custom_')) {
    idTurma = idTurma.replace('custom_', '')
    try {
      const list = await axios.get('/api/turmas-customizadas')
      const row = (list.data || []).find(t => String(t.id) === String(idTurma))
      idMateria = String(row?.id_disciplina_mae ?? (idDisciplinaMae.value || idMateria))
    } catch {
      idMateria = idDisciplinaMae.value || idMateria
    }
  }

  const resp = await axios.get('/api/notas', {
    params: {
      id_aluno: alunoSelecionado.value,
      id_materia: idMateria,
      id_turma: idTurma,
      periodo: periodoSelecionado.value,
      ano: anoSelecionado.value
    }
  })
  return Array.isArray(resp.data) ? resp.data : []
}

function onCFChange(objetivo) {
  limitarNota(objetivo, 'cf')
  salvarNotaCF(objetivo)
}


// Removido auto-salvamento: CF só é salvo via onCFChange
</script>

<style>
.selecao-container{
  margin: 0 auto;
}

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

/* Desabilitar setas de incremento/decremento nos inputs number */
input[type="number"] {
  -moz-appearance: textfield; /* Firefox */
}
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none; /* Chrome, Safari, Edge (Blink/WebKit) */
  margin: 0;
}
</style>
<style>
/* Ordem dos painéis no mobile: NOTAS acima de Porcentagens */
@media (max-width: 768px) {
  /* Container do painel NOTAS ocupa 100% em mobile */
  #notas-panel.painel-notas-container {
    max-width: 100% !important;
    width: 100%;
    margin: 1em 0;
    padding: 0 0.5em;
  }
  
  #notas-panel .avaliacao-paineis {
    flex-direction: column;
    gap: 1em;
  }
  #notas-panel .avaliacao-painel-notas { order: 1; }
  #notas-panel .avaliacao-painel-porcentagens { order: 2; }
  
  /* Tabelas de objetivos ocupam 100% em mobile, removem min-width */
  .tabela-objetivos-conceituais,
  .tabela-objetivos-atitudinais {
    width: 100% !important;
    min-width: auto !important;
    max-width: 100% !important;
    margin: 0 0 1em 0 !important;
  }
}

/* Desktop e tablets largos: tornar o painel NOTAS mais estreito */
@media (min-width: 769px) {
  #notas-panel .avaliacao-paineis { align-items: flex-start; }
  #notas-panel .avaliacao-painel-notas {
    flex: 0 0 clamp(220px, 30vw, 320px); /* largura fluida responsiva */
    max-width: clamp(220px, 30vw, 320px);
  }
  #notas-panel .avaliacao-painel-porcentagens {
    flex: 1 1 auto; /* ocupa o restante */
    min-width: 0;   /* permite encolher corretamente */
  }
  #notas-panel .avaliacao-painel-notas table {
    width: 100%;
    table-layout: fixed; /* evita estouro horizontal */
  }
}
</style>
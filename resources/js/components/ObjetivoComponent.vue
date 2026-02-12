<template>
  <div class="container">
      <!-- Conteúdo atual do componente -->
      <div>
        <!-- Frase só aparece se for Professor -->
        <h2 v-if="perfilLogado == 'Professor'">Turmas de {{ nomeProfessorSessao }}</h2>

        <!-- Combo Matéria: para qualquer perfil, inclusive Administrativo -->
        <div style="margin-bottom: 1em;" v-if="perfilLogado == 'Professor' || professorSelecionado || perfilLogado === 'Administrativo'">
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

        <div class="trimestre-ano" v-if="turmaSelecionada">
          <label for="trimestre-select" class="avaliacao-label">Trimestre:</label>
          <select
            id="trimestre-select"
            v-model="trimestreSelecionado"
            @change="carregarObjetivos"
            class="avaliacao-select combo-pequena"
          >
            <option value="">Selecione...</option>
            <option value="1º Tri">1º Tri</option>
            <option value="2º Tri">2º Tri</option>
            <option value="3º Tri">3º Tri</option>
          </select>

          <label for="ano-select" class="avaliacao-label">Ano:</label>
          <select
            id="ano-select"
            v-model="anoSelecionado"
            class="avaliacao-select combo-pequena"
          >
            <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
          </select>
        </div>

        <!-- Aviso de período expirado -->
        <div v-if="periodoExpirado" style="margin-top: 1em; padding: 1em; background-color: #ffcccc; border: 2px solid #cc0000; border-radius: 5px; color: #cc0000; font-weight: bold;">
          ⚠️ O período de cadastro de objetivos expirou em {{ dataExpiracao }}.
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
                  <template v-if="periodoExpirado">
                    {{ porcentagemConceituais }} %
                  </template>
                  <template v-else>
                    <input
                      type="number"
                      v-model="porcentagemConceituais"
                      min="0"
                      max="100"
                      style="width: 60px;"
                      :disabled="perfilLogado === 'Administrativo'"
                      @input="atualizarPorcentagem('conceituais')"
                      @blur="salvarPorcentagem"
                    /> %
                  </template>
                </td>
              </tr>
              <tr>
                <td>Obj. Atitudinais e Procedimentais</td>
                <td>{{ objetivosAtitudinais.length }}</td>
                <td>
                  <template v-if="periodoExpirado">
                    {{ porcentagemAtitudinais }} %
                  </template>
                  <template v-else>
                    <input
                      type="number"
                      v-model="porcentagemAtitudinais"
                      min="0"
                      max="100"
                      style="width: 60px;"
                      :disabled="perfilLogado === 'Administrativo'"
                      @input="atualizarPorcentagem('atitudinais')"
                      @blur="salvarPorcentagem"
                    /> %
                  </template>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="objetivosConceituais.length" style="margin-top:2em;">
            <table class="objetivos tabela-objetivos" border="1" cellpadding="5" style="background: #ffffcc;">
              <thead>
                <tr>
                  <th>Objetivos Conceituais</th>
                  <th width="40px">Adaptados</th>
                  <th v-if="!periodoExpirado && perfilLogado !== 'Administrativo'" width="30%">Editar</th>
                </tr>
              </thead>
              <tbody>
                <!-- Objetivos Conceituais -->
                <tr v-for="objetivo in objetivosConceituais" :key="objetivo.id_objetivo">
                  <td>
                    <span v-if="!objetivo.editando">{{ objetivo.nome_objetivo }}</span>
                    <div v-else style="display: flex; gap: 0.5em; align-items: center;">
                      <textarea v-model="objetivo.novoValor" rows="2" style="width: 70%;"></textarea>
                      <select v-model="objetivo.novoTipo" style="width: 120px;">
                        <option value="C">Conceitual</option>
                        <option value="A">Atitudinal</option>
                      </select>
                    </div>
                  </td>
                  <td style="text-align: center;">
                    <button
                      class="atribuir-objetivo-btn"
                      title="Atribuir objetivo a um aluno específico"
                      @click="abrirModalAlunos(objetivo)"
                      type="button"
                    >
                      <svg
                        width="25"
                        height="25"
                        viewBox="0 0 24 24"
                        :fill="objetivosComAlunos.includes(objetivo.id_objetivo) ? '#FFD600' : '#555'"
                      >
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20v-2c0-2.2 3.6-4 8-4s8 1.8 8 4v2"/>
                      </svg>
                    </button>
                  </td>
                  <td v-if="!periodoExpirado && perfilLogado !== 'Administrativo'">
                    <button class="btn-editar-obj" v-if="!objetivo.editando" @click="iniciarEdicao(objetivo)">Editar</button>
                    <button v-if="!objetivo.editando && perfilLogado === 'Professor'" @click="removerObjetivo(objetivo)" class="bt-remover">Remover</button>
                    <span v-else>
                      <button @click="salvarEdicao(objetivo)">Salvar</button>
                      <button @click="cancelarEdicao(objetivo)" class="btn-cancelar">Cancelar</button>
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="objetivosAtitudinais.length" style="margin-top:2em;">
            <table class="objetivos tabela-objetivos" border="1" cellpadding="5" style="background: #e6ffe6;">
              <thead>
                <tr>
                  <th>Objetivos Atitudinais e Procedimentais</th>
                  <th width="40px">Adaptados</th>
                  <th v-if="!periodoExpirado && perfilLogado !== 'Administrativo'" width="30%">Editar</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="objetivo in objetivosAtitudinais" :key="objetivo.id_objetivo">
                  <td>
                    <span v-if="!objetivo.editando">{{ objetivo.nome_objetivo }}</span>
                    <div v-else style="display: flex; gap: 0.5em; align-items: center;">
                      <textarea v-model="objetivo.novoValor" rows="2" style="width: 70%;"></textarea>
                      <select v-model="objetivo.novoTipo" style="width: 120px;">
                        <option value="C">Conceitual</option>
                        <option value="A">Atitudinal</option>
                      </select>
                    </div>
                  </td>
                  <td style="text-align: center;">
                    <button
                      class="atribuir-objetivo-btn"
                      title="Atribuir objetivo a um aluno específico"
                      @click="abrirModalAlunos(objetivo)"
                      type="button"
                    >
                      <svg
                        width="25"
                        height="25"
                        viewBox="0 0 24 24"
                        :fill="objetivosComAlunos.includes(objetivo.id_objetivo) ? '#FFD600' : '#555'"
                      >
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20v-2c0-2.2 3.6-4 8-4s8 1.8 8 4v2"/>
                      </svg>
                    </button>
                  </td>
                  <td v-if="!periodoExpirado && perfilLogado !== 'Administrativo'">
                    <button class="btn-editar-obj" v-if="!objetivo.editando" @click="iniciarEdicao(objetivo)">Editar</button>
                    <button v-if="!objetivo.editando && perfilLogado === 'Professor'" @click="removerObjetivo(objetivo)" class="bt-remover">Remover</button>
                    <span v-else>
                      <button @click="salvarEdicao(objetivo)">Salvar</button>
                      <button @click="cancelarEdicao(objetivo)" class="btn-cancelar">Cancelar</button>
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            class="tabela-inserir-objetivo"
            v-if="!periodoExpirado && perfilLogado !== 'Administrativo' && anoSelecionado === anoAtual"
            style="margin-top: 2em;"
          >
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
                  <span style="margin-left: 0.5em; vertical-align: middle;">
                    <button
                      class="atribuir-objetivo-btn"
                      title="Atribuir objetivo a um aluno específico"
                      @click="abrirModalAlunos(null)"
                    >
                      <svg width="25" height="25" viewBox="0 0 24 24" :fill="getCorBotaoAdaptado(null)">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20v-2c0-2.2 3.6-4 8-4s8 1.8 8 4v2"/>
                      </svg>
                    </button>
                  </span>
                </td>
                <td>
                  <button @click="salvarNovoObjetivo" :disabled="salvandoObjetivo">Salvar</button>
                </td>
              </tr>
            </table>
          </div>
        </div>
        <!-- Botão Imprimir objetivos apenas para perfil Administrativo -->
      <div
        v-if="perfilLogado === 'Administrativo' && materiaSelecionada && turmaSelecionada && trimestreSelecionado"
        style="margin-top: 2em; text-align: center;"
      >
        <button @click="imprimirObjetivos">Imprimir objetivos</button>
      </div>
      </div>
      
    <!--<div v-if="mostrarTabela">
      Aqui poderia ficar um painel de resumo, ajuda ou outras informações
    </div>-->
  </div>

  <!-- Modal para exibir alunos da turma -->
  <div v-if="mostrarModalAlunos" class="modal-overlay">
    <div class="modal-content modal-alunos-amplo">
      <h3>Gerenciar visibilidade do objetivo</h3>
      <div v-if="objetivoModal" style="margin-bottom: 1em; padding: 0.5em; background: #f0f0f0; border-radius: 4px;">
        <strong>Objetivo:</strong> {{ objetivoModal.nome_objetivo }}
      </div>
      <h5 v-if="modalEditando" style="color:red;">Defina quem verá este objetivo:</h5>
      <div v-if="!modalEditando" style="color: #b00; margin-bottom: 1em; font-weight: bold;">
        Para alterar, clique antes em "Editar" e retorne aqui.
      </div>
      
      <div v-if="!carregandoAlunos" style="max-height: 450px; overflow-y: auto;">
        <table style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr style="background: #e0e0e0; position: sticky; top: 0;">
              <th style="text-align: left; padding: 0.5em;">Aluno</th>
              <th style="text-align: center; padding: 0.5em;">Exibir</th>
              <th style="text-align: center; padding: 0.5em;">Ocultar</th>
            </tr>
          </thead>
          <tbody>
            <!-- Linha especial: Todos os alunos -->
            <tr style="background: #f9f9f9; border-bottom: 2px solid #999; font-weight: bold;">
              <td style="padding: 0.5em;">Todos os alunos</td>
              <td style="text-align: center; padding: 0.5em;">
                <input
                  type="radio"
                  name="estado-todos"
                  value="exibir"
                  :disabled="!modalEditando || !objetivoModal"
                  @change="marcarTodosAlunos('exibir')"
                />
              </td>
              <td style="text-align: center; padding: 0.5em;">
                <input
                  type="radio"
                  name="estado-todos"
                  value="ocultar"
                  :disabled="!modalEditando || !objetivoModal"
                  @change="marcarTodosAlunos('ocultar')"
                />
              </td>
            </tr>
            <!-- Linhas individuais por aluno -->
            <tr v-for="aluno in alunosTurma" :key="aluno.id_aluno" style="border-bottom: 1px solid #ddd;">
              <td style="padding: 0.5em;">{{ aluno.nome }}</td>
              <td style="text-align: center; padding: 0.5em;">
                <input
                  type="radio"
                  :name="'estado-' + aluno.id_aluno"
                  value="exibir"
                  :checked="!alunosExcluidosModal.includes(aluno.id_aluno)"
                  :disabled="!modalEditando || !objetivoModal"
                  @change="alterarEstadoAlunoSimplificado(aluno.id_aluno, 'exibir')"
                />
              </td>
              <td style="text-align: center; padding: 0.5em;">
                <input
                  type="radio"
                  :name="'estado-' + aluno.id_aluno"
                  value="ocultar"
                  :checked="alunosExcluidosModal.includes(aluno.id_aluno)"
                  :disabled="!modalEditando || !objetivoModal"
                  @change="alterarEstadoAlunoSimplificado(aluno.id_aluno, 'ocultar')"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else style="text-align: center; font-size: 1.1em; color: #888; margin: 2em 0;">
        Carregando turma...
      </div>
      <div style="margin-top: 1em; padding: 0.8em; background: #e8f5e9; border-radius: 4px; font-size: 0.9em;">
        <strong>Como funciona:</strong><br>
        • <strong>Exibir:</strong> o objetivo aparecerá para este aluno (padrão)<br>
        • <strong>Ocultar:</strong> o objetivo não aparecerá para este aluno<br>
        <em style="color: #666;">Dica: Use "Todos os alunos" para marcar todos de uma vez.</em>
      </div>
      <button @click="fecharModalAlunos" style="margin-top: 1em;">Fechar</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

const materias = ref([])
const materiaSelecionada = ref('')
const turmas = ref([])
const turmaSelecionada = ref('')
const trimestreSelecionado = ref('')
const anosDisponiveis = ref([]);
const anoAtual = new Date().getFullYear();
const anoSelecionado = ref(anoAtual);
const objetivos = ref([])
const objetivosConceituais = ref([])
const objetivosAtitudinais = ref([])
const porcentagemConceituais = ref(50)
const porcentagemAtitudinais = ref(50)
const novoObjetivoTexto = ref('')
const novoObjetivoTipo = ref('C')
const professores = ref([])
const professorSelecionado = ref('')
const nomesDisciplinas = ref([])
const mostrarModalAlunos = ref(false)
const alunosTurma = ref([])
const alunosSelecionados = ref([]) // IDs dos alunos marcados
const carregandoAlunos = ref(false)
const salvandoObjetivo = ref(false)
const objetivosComAlunos = ref([]) // IDs dos objetivos que possuem alunos vinculados
const objetivosExcluidos = ref([]) // IDs dos objetivos excluídos (ocultos) para alunos específicos
const haAlunosMarcados = computed(() => alunosSelecionados.value.length > 0)
const objetivoModal = ref(null) // Guarda o objetivo para o qual o modal está aberto
const alunosSelecionadosNovoObjetivo = ref([]) // Para o painel de inserção
const alunosSelecionadosObjetivo = ref([])     // Para o modal de objetivos existentes
const alunosSelecionadosModal = computed({
  get() {
    return objetivoModal.value ? alunosSelecionadosObjetivo.value : alunosSelecionadosNovoObjetivo.value;
  },
  set(value) {
    if (objetivoModal.value) {
      alunosSelecionadosObjetivo.value = value;
      // Se o objetivo está em modo de edição, salve os alunos adaptados
      if (modalEditando.value && objetivoModal.value.id_objetivo) {
        salvarAlunosAdaptados(objetivoModal.value.id_objetivo, value);
      }
    } else {
      alunosSelecionadosNovoObjetivo.value = value;
    }
  }
});
const modalEditando = ref(false)

// Controle de período expirado
const periodoExpirado = ref(false)
const dataExpiracao = ref('')

// Recupera o perfil do usuário logado
const perfilLogado = ref(sessionStorage.getItem('perfil') || 'Não definido')
const nomeProfessorSessao = sessionStorage.getItem('nome') || ''

async function carregarMaterias() {
  materias.value = []
  materiaSelecionada.value = ''
  turmas.value = []
  turmaSelecionada.value = ''
  trimestreSelecionado.value = ''
  objetivos.value = []
  objetivosConceituais.value = []
  objetivosAtitudinais.value = []

  // Para Administrativo, busca todas as disciplinas a partir das turmas
  if (perfilLogado.value === 'Administrativo') {
    try {
      const url = `https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2`
      const resp = await axios.get(url, {
        headers: {
          Token: sessionStorage.getItem('tokenSophia')
        }
      })
      // Extrai todas as disciplinas únicas das turmas
      const disciplinasMap = {}
      resp.data.forEach(turma => {
        if (Array.isArray(turma.professoresDisciplinas)) {
          turma.professoresDisciplinas.forEach(pd => {
            if (pd.disciplina && pd.disciplina.codigo && pd.disciplina.nome) {
              disciplinasMap[pd.disciplina.codigo] = pd.disciplina.nome
            }
          })
        }
      })
      materias.value = Object.entries(disciplinasMap)
        .map(([codigo, nome]) => ({
          id_materia: codigo,
          nome_materia: nome
        }))
        .sort((a, b) => a.nome_materia.localeCompare(b.nome_materia))
    } catch (e) {
      materias.value = []
    }
    return
  }

  // Para outros perfis, busca apenas as matérias do professor
  const codigos = JSON.parse(sessionStorage.getItem('codigo_disciplina') || '[]')
  if (!codigos.length) return

  try {
    const url = `https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Disciplinas?Codigos=${codigos.join(',')}`
    const resp = await axios.get(url, {
      headers: {
        Token: sessionStorage.getItem('tokenSophia')
      }
    })
    materias.value = Array.isArray(resp.data)
      ? resp.data.map(d => ({
          id_materia: d.codigo,
          nome_materia: d.nome
        }))
      : []
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

  // Buscar turmas do Sophia primeiro
  const codigoProfessor = sessionStorage.getItem('codigo')
  let turmasSophia = []
  
  try {
    const response = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2',
      { headers: { Token: sessionStorage.getItem('tokenSophia') } }
    )

    if (perfilLogado.value === 'Administrativo') {
      turmasSophia = response.data
        .filter(turma =>
          Array.isArray(turma.professoresDisciplinas) &&
          turma.professoresDisciplinas.some(pd =>
            pd.disciplina &&
            pd.disciplina.codigo == materiaSelecionada.value
          )
        )
        .map(turma => ({
          id_turma: turma.codigo,
          nome_turma: turma.nome,
          isCustomizada: false
        }))
    } else {
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
        }))
    }
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

async function carregarObjetivos() {
  objetivos.value = []
  objetivosConceituais.value = []
  objetivosAtitudinais.value = []
  if (!turmaSelecionada.value || !trimestreSelecionado.value || !anoSelecionado.value) return
  try {
    const response = await axios.get(
      `/api/objetivos?id_materia=${materiaSelecionada.value}&turma_id=${turmaSelecionada.value}&trimestre=${trimestreSelecionado.value}&ano=${anoSelecionado.value}`
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
  if (!materiaSelecionada.value || !turmaSelecionada.value || !trimestreSelecionado.value || !anoSelecionado.value) return
  try {
    const response = await axios.get('/api/porcentagem-objetivos', {
      params: {
        id_materia: materiaSelecionada.value,
        id_turma: turmaSelecionada.value,
        periodo: trimestreSelecionado.value,
        ano: anoSelecionado.value // <-- Adicione este parâmetro!
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
  objetivo.novoTipo = objetivo.tipo_objetivo
  objetivoModal.value = objetivo // Define o objetivo do modal
  modalEditando.value = true     // Ativa modo edição do modal
  mostrarModalAlunos.value = false // Garante que o modal só abre ao clicar no botão de adaptação
}

function cancelarEdicao(objetivo) {
  objetivo.editando = false
  objetivo.novoValor = objetivo.nome_objetivo
  modalEditando.value = false
  objetivoModal.value = null
}

// Atualiza o objetivo na lista correta, mantendo a posição
function atualizarListaObjetivos(lista, objetivo) {
  const idx = lista.findIndex(o => o.id_objetivo === objetivo.id_objetivo)
  if (idx !== -1) {
    lista[idx] = { ...objetivo }
  }
}

async function salvarEdicao(objetivo) {
  try {
    await axios.put(`/api/objetivos/${objetivo.id_objetivo}`, {
      nome_objetivo: objetivo.novoValor,
      tipo_objetivo: objetivo.novoTipo
    })
    objetivo.nome_objetivo = objetivo.novoValor
    objetivo.tipo_objetivo = objetivo.novoTipo
    objetivo.editando = false

    // Se mudou de tipo, remova da lista antiga e insira na nova na mesma posição do original
    if (objetivo.tipo_objetivo === 'C') {
      // Se estava em atitudinais, remove de lá e adiciona em conceituais
      const idxAtitudinal = objetivosAtitudinais.value.findIndex(o => o.id_objetivo === objetivo.id_objetivo)
      if (idxAtitudinal !== -1) {
        objetivosAtitudinais.value.splice(idxAtitudinal, 1)
        objetivosConceituais.value.push(objetivo)
      } else {
        // Só atualiza na lista conceituais
        const idxConceitual = objetivosConceituais.value.findIndex(o => o.id_objetivo === objetivo.id_objetivo)
        if (idxConceitual !== -1) {
          objetivosConceituais.value[idxConceitual] = { ...objetivo }
        }
      }
    } else if (objetivo.tipo_objetivo === 'A') {
      // Se estava em conceituais, remove de lá e adiciona em atitudinais
      const idxConceitual = objetivosConceituais.value.findIndex(o => o.id_objetivo === objetivo.id_objetivo)
      if (idxConceitual !== -1) {
        objetivosConceituais.value.splice(idxConceitual, 1)
        objetivosAtitudinais.value.push(objetivo)
      } else {
        // Só atualiza na lista atitudinais
        const idxAtitudinal = objetivosAtitudinais.value.findIndex(o => o.id_objetivo === objetivo.id_objetivo)
        if (idxAtitudinal !== -1) {
          objetivosAtitudinais.value[idxAtitudinal] = { ...objetivo }
        }
      }
    }
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
    porcentagem_atitudinal: porcentagemAtitudinais.value,
    ano: anoSelecionado.value, // <- garantir que atualiza o mesmo registro
  }).catch(() => {
    alert('Erro ao salvar porcentagem!')
  })
}

async function salvarNovoObjetivo() {
  if (salvandoObjetivo.value) return
  salvandoObjetivo.value = true
  if (!novoObjetivoTexto.value.trim()) {
    alert('Digite o objetivo.')
    return
  }
  if (!materiaSelecionada.value || !turmaSelecionada.value || !trimestreSelecionado.value) {
    alert('Selecione matéria, turma e trimestre.')
    return
  }
  try {
    // Salva o objetivo e obtém o ID criado
    const resp = await axios.post('/api/objetivos', {
      nome_objetivo: novoObjetivoTexto.value,
      tipo_objetivo: novoObjetivoTipo.value,
      id_materia: materiaSelecionada.value,
      id_turma: turmaSelecionada.value,
      periodo_objetivo: trimestreSelecionado.value,
      ano_objetivo: anoSelecionado.value
    })
    const idObjetivoCriado = resp.data.id_objetivo // Certifique-se que a API retorna o id do objetivo criado

    // Salva vínculos com alunos
    for (const idAluno of alunosSelecionadosNovoObjetivo.value) {
      await axios.post('/api/objetivos-adaptados', {
        id_objetivo: idObjetivoCriado,
        id_aluno: idAluno
      })
    }
    alunosSelecionadosNovoObjetivo.value = []

    // Recarrega objetivos e vínculos adaptados
    await carregarObjetivos()
    await carregarObjetivosAdaptados() // <-- Isso garante que o botão já fique amarelo
    await carregarObjetivosExcluidos()

    // Limpa campos e seleção
    novoObjetivoTexto.value = ''
    novoObjetivoTipo.value = 'C'
    alunosSelecionados.value = []
  } catch (e) {
    alert('Erro ao salvar objetivo!')
  } finally {
    salvandoObjetivo.value = false
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
          Token: sessionStorage.getItem('tokenSophia')
        }
      }
    )
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

async function carregarNomesDisciplinas() {
  const codigos = JSON.parse(sessionStorage.getItem('codigo_disciplina') || '[]')
  if (!codigos.length) {
    nomesDisciplinas.value = []
    return
  }
  try {
    const url = `https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Disciplinas?Codigos=${codigos.join(',')}`
    const resp = await axios.get(url, {
      headers: {
        Token: sessionStorage.getItem('tokenSophia')
      }
    })
    nomesDisciplinas.value = Array.isArray(resp.data)
      ? resp.data.map(d => d.nome)
      : []
  } catch (e) {
    nomesDisciplinas.value = []
  }
}

async function carregarAnosObjetivos() {
  try {
    const resp = await axios.get('/api/anos-objetivos');
    anosDisponiveis.value = resp.data;
    // Seleciona o ano atual por padrão
    anoSelecionado.value = anoAtual;
  } catch {
    anosDisponiveis.value = [anoAtual];
    anoSelecionado.value = anoAtual;
  }
}

// Chame ao montar ou após selecionar matéria/turma, conforme sua lógica
onMounted(() => {
  carregarProfessores().then(() => {
    carregarMaterias()
  })
  carregarTurmas()
  carregarNomesDisciplinas()
  carregarAnosObjetivos()
})

async function verificarPeriodo() {
  periodoExpirado.value = false
  dataExpiracao.value = ''
  
  if (!trimestreSelecionado.value || !anoSelecionado.value) {
    return
  }
  
  try {
    const resp = await axios.get('/api/periodos-avaliacao', {
      params: {
        ano: anoSelecionado.value,
        periodo: trimestreSelecionado.value
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
    console.error('Erro ao verificar período:', e)
  }
}

watch(turmaSelecionada, (novaTurma) => {
  if (novaTurma && materiaSelecionada.value && trimestreSelecionado.value && anoSelecionado.value) {
    carregarObjetivos()
    carregarPorcentagens()
  }
  // Atualiza os anos disponíveis sempre que mudar de turma
  if (novaTurma) carregarAnosObjetivos();
});

watch(anoSelecionado, (novoAno) => {
  if (materiaSelecionada.value && turmaSelecionada.value && trimestreSelecionado.value && novoAno) {
    carregarObjetivos()
  }
})

watch([materiaSelecionada, turmaSelecionada, trimestreSelecionado], () => {
  alunosSelecionados.value = []
})

watch([turmaSelecionada, trimestreSelecionado, anoSelecionado], async () => {
  if (turmaSelecionada.value && trimestreSelecionado.value && anoSelecionado.value) {
    await carregarObjetivos()
    await carregarObjetivosAdaptados()
    await carregarObjetivosExcluidos()
    await verificarPeriodo()
  }
})

function imprimirObjetivos() {
  // Busca os nomes selecionados
  const materia = materias.value.find(m => m.id_materia == materiaSelecionada.value);
  const turma = turmas.value.find(t => t.id_turma == turmaSelecionada.value);
  const periodo = trimestreSelecionado.value;

  let html = `
    <div style="margin-bottom:2em;">
      <h1>Matéria: ${materia ? materia.nome_materia : ''}</h1>
      <h2>Turma: ${turma ? turma.nome_turma : ''}</h2>
      <h3>Período: ${periodo || ''}</h3>
    </div>
  `;

  if (objetivosConceituais.value.length) {
    html += '<h2>Objetivos Conceituais</h2><ol>';
    objetivosConceituais.value.forEach(obj => {
      html += `<li>${obj.nome_objetivo}</li>`;
    });
    html += '</ol>';
  }

  if (objetivosAtitudinais.value.length) {
    html += '<h2>Objetivos Atitudinais e Procedimentais</h2><ol>';
    objetivosAtitudinais.value.forEach(obj => {
      html += `<li>${obj.nome_objetivo}</li>`;
    });
    html += '</ol>';
  }

  const win = window.open('', '', 'width=800,height=600');
  win.document.write(`
    <html>
      <head>
        <title>Imprimir objetivos</title>
        <style>
          body { font-family: "Times New Roman", Times, serif; margin: 2em; font-size: 12pt; }
          h1, h2, h3 { color: #73321c; }
          ol { margin-bottom: 2em; }
        </style>
      </head>
      <body>
        ${html}
      </body>
    </html>
  `);
  win.document.close();
  win.focus();
  win.print();
}

async function abrirModalAlunos(objetivo) {
  objetivoModal.value = objetivo || null

  // Verifica diretamente a propriedade editando no objetivo
  if (!objetivo) {
    modalEditando.value = true
  } else {
    const objetivoEmEdicao = objetivo.editando === true ||
      objetivosConceituais.value.some(o => o.id_objetivo === objetivo.id_objetivo && o.editando === true) ||
      objetivosAtitudinais.value.some(o => o.id_objetivo === objetivo.id_objetivo && o.editando === true);
    modalEditando.value = objetivoEmEdicao;
  }

  if (!turmaSelecionada.value) {
    alert('Selecione uma turma primeiro.')
    return
  }
  
  carregandoAlunos.value = true
  mostrarModalAlunos.value = true
  
  // Reset arrays
  alunosExcluidosModal.value = []

  try {
    // NOVO: Se matéria for uma das abaixo E turma é customizada, busca alunos da turma customizada
    // Química(6), Inglês(11), Arte(22), Educação Musical(26), Teatro do Oprimido(93)
    const turmaObj = turmas.value.find(t => t.id_turma == turmaSelecionada.value)
    const isCustomizada = turmaObj?.isCustomizada === true
    
    if (['6', '11', '22', '26', '93'].includes(String(materiaSelecionada.value)) && isCustomizada) {
      const resp = await axios.get(`/api/turmas-customizadas/${turmaSelecionada.value}/alunos`)
      alunosTurma.value = Array.isArray(resp.data)
        ? resp.data
            .filter(a => a.dt_fim == null)
            .map(a => ({
              id_aluno: a.id_aluno,
              nome: a.nome // ajuste se backend retorna nome, senão busque do endpoint Sophia
            }))
        : []
      // Se backend não retorna nome, busque do endpoint Sophia:
      if (alunosTurma.value.length > 0 && alunosTurma.value.some(a => !a.nome)) {
        const tokenSophia = sessionStorage.getItem('tokenSophia')
        const respSophia = await axios.get(
          'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7&Inativos=false&ConsiderarTurmaFinalizada=false',
          { headers: { Token: tokenSophia } }
        )
        const mapa = new Map(respSophia.data.map(a => [a.codigo, a.nome]))
        alunosTurma.value.forEach(a => {
          if (!a.nome) a.nome = mapa.get(a.id_aluno) || '(Nome não encontrado)'
        })
      }
      alunosTurma.value.sort((a, b) => a.nome.localeCompare(b.nome))
    } else {
      // Fluxo padrão: alunos da turma Sophia
      const resp = await axios.get(
        'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7&Inativos=false&ConsiderarTurmaFinalizada=false',
        { headers: { Token: sessionStorage.getItem('tokenSophia') } }
      )
      alunosTurma.value = resp.data
        .filter(aluno =>
          Array.isArray(aluno.turmas) &&
          aluno.turmas.some(t => String(t.codigo) === String(turmaSelecionada.value))
        )
        .map(aluno => ({
          id_aluno: aluno.codigo,
          nome: aluno.nome
        }))
        .sort((a, b) => a.nome.localeCompare(b.nome))
    }

    // Carrega vínculos de objetivos excluídos (ocultos) se for objetivo existente
    if (objetivo && objetivo.id_objetivo) {
      try {
        const excluidosResp = await axios.get('/api/objetivos-excluidos', {
          params: { id_objetivo: objetivo.id_objetivo }
        })
        alunosExcluidosModal.value = Array.isArray(excluidosResp.data)
          ? excluidosResp.data.map(a => a.id_aluno)
          : []
      } catch (e) {
        console.error('Erro ao carregar objetivos excluídos:', e)
        alunosExcluidosModal.value = []
      }
    }
  } catch (e) {
    console.error('Erro ao carregar alunos da turma:', e)
    console.error('Detalhes do erro:', e.response?.data || e.message)
    alunosTurma.value = []
    alunosSelecionadosNovoObjetivo.value = []
    alunosSelecionadosObjetivo.value = []
    alunosExcluidosModal.value = []
    alert('Não foi possível carregar os alunos da turma.')
  }
  carregandoAlunos.value = false
}

function fecharModalAlunos() {
  mostrarModalAlunos.value = false
  modalEditando.value = false
  objetivoModal.value = null
}

async function carregarObjetivosAdaptados() {
  try {
    const resp = await axios.get('/api/objetivos-adaptados', {
      params: {
        turma_id: turmaSelecionada.value,
        ano: anoSelecionado.value
      }
    })
    // resp.data deve ser um array de objetos {id_objetivo, id_aluno}
    // Pegue apenas os IDs dos objetivos que possuem alunos vinculados
    objetivosComAlunos.value = [...new Set(resp.data.map(o => o.id_objetivo))]
  } catch (e) {
    objetivosComAlunos.value = []
  }
}

async function carregarObjetivosExcluidos() {
  try {
    const resp = await axios.get('/api/objetivos-excluidos', {
      params: {
        turma_id: turmaSelecionada.value,
        ano: anoSelecionado.value
      }
    })
    objetivosExcluidos.value = [...new Set(resp.data.map(o => o.id_objetivo))]
  } catch (e) {
    objetivosExcluidos.value = []
  }
}

function getCorBotaoAdaptado(objetivo) {
  // Painel de inserção
  if (!objetivo) {
    return alunosSelecionadosNovoObjetivo.value.length > 0 ? '#FFD600' : '#555'
  }
  // Modal aberto para este objetivo e há alunos marcados
  if (objetivoModal.value && objetivoModal.value.id_objetivo === objetivo.id_objetivo && alunosSelecionadosObjetivo.value.length > 0) {
    return '#FFD600'
  }
  // Objetivo tem alunos adaptados vinculados
  if (objetivosComAlunos.value.includes(objetivo.id_objetivo)) {
    return '#FFD600'
  }
  return '#555'
}

// Array para controle de alunos com objetivo oculto
const alunosExcluidosModal = ref([])  // IDs dos alunos que NÃO verão o objetivo

// Função simplificada para alterar estado do aluno (exibir/ocultar)
async function alterarEstadoAlunoSimplificado(idAluno, novoEstado) {
  if (!modalEditando.value || !objetivoModal.value) return
  
  try {
    if (novoEstado === 'ocultar') {
      // Adiciona à lista de excluídos
      if (!alunosExcluidosModal.value.includes(idAluno)) {
        await axios.post('/api/objetivos-excluidos', {
          id_objetivo: objetivoModal.value.id_objetivo,
          id_aluno: idAluno
        })
        alunosExcluidosModal.value.push(idAluno)
      }
    } else {
      // Remove da lista de excluídos (exibir)
      const index = alunosExcluidosModal.value.indexOf(idAluno)
      if (index > -1) {
        await axios.delete(`/api/objetivos-excluidos/${objetivoModal.value.id_objetivo}`, {
          params: { id_aluno: idAluno }
        })
        alunosExcluidosModal.value.splice(index, 1)
      }
    }
    await carregarObjetivosExcluidos()
  } catch (e) {
    console.error('Erro ao alterar estado do objetivo:', e)
  }
}

// Função para marcar todos os alunos de uma vez
async function marcarTodosAlunos(estado) {
  if (!modalEditando.value || !objetivoModal.value) return
  
  try {
    if (estado === 'ocultar') {
      // Ocultar para todos - usar endpoint batch
      const alunosParaOcultar = alunosTurma.value
        .filter(aluno => !alunosExcluidosModal.value.includes(aluno.id_aluno))
        .map(aluno => aluno.id_aluno)
      
      if (alunosParaOcultar.length > 0) {
        await axios.post('/api/objetivos-excluidos/batch/adicionar', {
          id_objetivo: objetivoModal.value.id_objetivo,
          alunos: alunosParaOcultar
        })
      }
      alunosExcluidosModal.value = alunosTurma.value.map(a => a.id_aluno)
    } else {
      // Exibir para todos - usar endpoint batch com lista vazia para remover todos
      await axios.post('/api/objetivos-excluidos/batch/remover', {
        id_objetivo: objetivoModal.value.id_objetivo,
        alunos: [] // Vazio significa remover todos os registros deste objetivo
      })
      alunosExcluidosModal.value = []
    }
    await carregarObjetivosExcluidos()
  } catch (e) {
    console.error('Erro ao marcar todos os alunos:', e)
  }
}

// Mantida para compatibilidade (não remove dados antigos de objetivos_adaptados)
// mas não é mais usada na nova interface simplificada
</script>

<style>
.modal {
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgb(0,0,0);
  background-color: rgba(0,0,0,0.4);
  padding-top: 60px;
}

.modal-conteudo {
  background-color: #fefefe;
  margin: 5% auto;
  padding: 20px;
  border: 1px solid #888;
  width: 80%;
  max-width: 500px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.fechar {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.fechar:hover,
.fechar:focus {
  color: black;
  text-decoration: none;
  cursor: pointer;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: #fefefe;
  padding: 2em;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  max-width: 500px;
  width: 90%;
}

.modal-alunos-amplo {
  max-width: 700px !important;
}

.modal-content h3 {
  margin-top: 0;
}

.modal-content ul {
  list-style-type: none;
  padding-left: 0;
}

.modal-content li {
  margin: 0.5em 0;
}
</style>


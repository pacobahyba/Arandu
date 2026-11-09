<template>
  <div class="container">
    <h2 class="titulo">Exportar notas</h2>

    <div v-if="perfilLogado !== 'Professor'" class="aviso-perfil">
      Esta tela está disponível apenas para perfil Professor.
    </div>

    <template v-else>
      <div class="selecao-container">
        <label for="ano-select" class="avaliacao-label">Ano:</label>
        <select id="ano-select" v-model="anoSelecionado" class="avaliacao-select combo-pequena">
          <option v-for="ano in anosDisponiveis" :key="ano" :value="ano">{{ ano }}</option>
        </select>

        <label for="materia-select" class="avaliacao-label">Matéria:</label>
        <select id="materia-select" v-model="materiaSelecionada" class="avaliacao-select" @change="carregarTurmas">
          <option value="">Selecione...</option>
          <option v-for="materia in materias" :key="materia.id_materia" :value="materia.id_materia">
            {{ materia.nome_materia }}
          </option>
        </select>

        <label for="turma-select" class="avaliacao-label">Turma:</label>
        <select
          id="turma-select"
          v-model="turmaSelecionada"
          class="avaliacao-select"
          :disabled="!materiaSelecionada"
          @change="carregarAlunos"
        >
          <option value="">Selecione...</option>
          <option v-for="turma in turmas" :key="turma.id_turma" :value="turma.id_turma">
            {{ turma.nome_turma }}
          </option>
        </select>

        <!-- O período vem depois da turma: a lista depende do regime da turma -->
        <label for="periodo-select" class="avaliacao-label">{{ substantivoPeriodo }}:</label>
        <select
          id="periodo-select"
          v-model="periodoSelecionado"
          class="avaliacao-select combo-pequena"
          :disabled="!periodos.length"
          @change="aoMudarPeriodo"
        >
          <option value="">Selecione...</option>
          <option v-for="p in periodos" :key="p" :value="p">{{ p }}</option>
        </select>

        <button
          :disabled="!filtrosSelecionados || carregandoTabela"
          @click="gerarTabelaExportacao"
          class="btn-gerar"
        >
          {{ carregandoTabela ? 'Carregando...' : 'Gerar tabela' }}
        </button>
      </div>

      <div v-if="erro" class="erro">{{ erro }}</div>

      <div v-if="mostrarTabela" class="painel-tabela">
        <div class="resumo">
          <strong>Registros:</strong> {{ linhasExportacao.length }} |
          <strong>Objetivos:</strong> {{ objetivos.length }} |
          <strong>Alunos:</strong> {{ alunos.length }}
          <button
            class="btn-pdf"
            :disabled="!linhasExportacao.length || exportandoPdf"
            @click="exportarEmPdf"
          >
            {{ exportandoPdf ? 'Exportando PDF...' : 'Exportar em PDF' }}
          </button>
        </div>

        <div class="tabela-wrap">
          <table class="tabela-exportacao" border="1" cellpadding="6">
            <thead>
              <tr>
                <th>Tipo</th>
                <th>Objetivo</th>
                <th>Est</th>
                <th>Prof</th>
                <th>Rec</th>
                <th>CF</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="grupo in linhasAgrupadasPorAluno" :key="`grupo-${grupo.id_aluno}`">
                <tr class="linha-grupo-aluno">
                  <td colspan="6"><strong>Aluno:</strong> {{ grupo.nome_aluno }}</td>
                </tr>
                <tr v-for="linha in grupo.linhas" :key="`${linha.id_objetivo}-${linha.id_aluno}`">
                  <td>{{ linha.tipo_objetivo === 'C' ? 'Conceitual' : 'Atitudinal' }}</td>
                  <td>{{ linha.nome_objetivo }}</td>
                  <td>{{ linha.est }}</td>
                  <td>{{ linha.prof }}</td>
                  <td>{{ linha.rec }}</td>
                  <td>{{ linha.cf }}</td>
                </tr>
              </template>
              <tr v-if="!linhasExportacao.length">
                <td colspan="6" class="vazio">Nenhum registro encontrado para os filtros selecionados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import { usePeriodos, origemDaTurma } from '../composables/usePeriodos'

const perfilLogado = ref(sessionStorage.getItem('perfil') || '')

const anosDisponiveis = ref([])
const anoSelecionado = ref(new Date().getFullYear())
const periodoSelecionado = ref('')

const materias = ref([])
const materiaSelecionada = ref('')

const turmas = ref([])
const turmaSelecionada = ref('')

const alunos = ref([])
const objetivos = ref([])
const linhasExportacao = ref([])

const mostrarTabela = ref(false)
const carregandoTabela = ref(false)
const exportandoPdf = ref(false)
const erro = ref('')

// Períodos válidos conforme o regime da turma selecionada.
const origemTurma = computed(() => origemDaTurma(turmas, turmaSelecionada.value))
const { periodos, substantivo: substantivoPeriodo } = usePeriodos(
  anoSelecionado, turmaSelecionada, origemTurma, periodoSelecionado
)

const filtrosSelecionados = computed(() => {
  return Boolean(anoSelecionado.value && periodoSelecionado.value && materiaSelecionada.value && turmaSelecionada.value)
})

const linhasAgrupadasPorAluno = computed(() => {
  const gruposMap = new Map()

  linhasExportacao.value.forEach((linha) => {
    const chave = String(linha.id_aluno)
    if (!gruposMap.has(chave)) {
      gruposMap.set(chave, {
        id_aluno: linha.id_aluno,
        nome_aluno: linha.nome_aluno,
        linhas: []
      })
    }
    gruposMap.get(chave).linhas.push(linha)
  })

  return Array.from(gruposMap.values()).sort((a, b) => a.nome_aluno.localeCompare(b.nome_aluno))
})

function aoMudarPeriodo() {
  materiaSelecionada.value = ''
  turmas.value = []
  turmaSelecionada.value = ''
  alunos.value = []
  objetivos.value = []
  linhasExportacao.value = []
  mostrarTabela.value = false
  carregarMaterias()
}

async function carregarAnosObjetivos() {
  try {
    const resp = await axios.get('/api/anos-objetivos')
    anosDisponiveis.value = Array.isArray(resp.data) ? resp.data : [new Date().getFullYear()]
    anoSelecionado.value = anosDisponiveis.value[0] || new Date().getFullYear()
  } catch {
    anosDisponiveis.value = [new Date().getFullYear()]
    anoSelecionado.value = new Date().getFullYear()
  }
}

async function carregarMaterias() {
  materias.value = []
  materiaSelecionada.value = ''
  turmas.value = []
  turmaSelecionada.value = ''
  alunos.value = []
  objetivos.value = []
  linhasExportacao.value = []
  mostrarTabela.value = false

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
  } catch {
    materias.value = []
  }
}

async function carregarTurmas() {
  turmas.value = []
  turmaSelecionada.value = ''
  alunos.value = []
  objetivos.value = []
  linhasExportacao.value = []
  mostrarTabela.value = false

  if (!materiaSelecionada.value) return

  const codigoProfessor = sessionStorage.getItem('codigo')
  let turmasSophia = []

  try {
    const response = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2',
      { headers: { Token: sessionStorage.getItem('tokenSophia') } }
    )

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
  } catch {
    turmasSophia = []
  }

  let turmasCustomizadas = []
  if (['6', '11', '22', '26', '93'].includes(String(materiaSelecionada.value))) {
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
    } catch {
      turmasCustomizadas = []
    }
  }

  turmas.value = [...turmasSophia, ...turmasCustomizadas]
}

async function carregarAlunos() {
  alunos.value = []
  if (!turmaSelecionada.value) return

  const turmaObj = turmas.value.find(t => String(t.id_turma) === String(turmaSelecionada.value))
  const ehTurmaCustomizada = turmaObj?.isCustomizada === true

  if (ehTurmaCustomizada && ['6', '11', '22', '26', '93'].includes(String(materiaSelecionada.value))) {
    try {
      const resp = await axios.get(`/api/turmas-customizadas/${turmaSelecionada.value}/alunos`)
      alunos.value = Array.isArray(resp.data)
        ? resp.data.filter(a => a.dt_fim == null).map(a => ({ id_aluno: a.id_aluno, nome_aluno: a.nome || '' }))
        : []

      if (alunos.value.some(a => !a.nome_aluno)) {
        const tokenSophia = sessionStorage.getItem('tokenSophia')
        const respSophia = await axios.get(
          'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7&Inativos=false&ConsiderarTurmaFinalizada=false',
          { headers: { Token: tokenSophia } }
        )
        const mapa = new Map((respSophia.data || []).map(a => [a.codigo, a.nome]))
        alunos.value.forEach(a => {
          if (!a.nome_aluno) a.nome_aluno = mapa.get(a.id_aluno) || '(Nome não encontrado)'
        })
      }

      alunos.value.sort((a, b) => a.nome_aluno.localeCompare(b.nome_aluno))
      return
    } catch {
      alunos.value = []
      return
    }
  }

  try {
    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7&Inativos=false&ConsiderarTurmaFinalizada=false'
    const resp = await axios.get(url, {
      headers: { Token: sessionStorage.getItem('tokenSophia') }
    })
    alunos.value = Array.isArray(resp.data)
      ? resp.data
          .filter(aluno =>
            Array.isArray(aluno.turmas) &&
            aluno.turmas.some(turma => String(turma.codigo) === String(turmaSelecionada.value))
          )
          .map(aluno => ({
            id_aluno: aluno.codigo,
            nome_aluno: aluno.nome
          }))
      : []
  } catch {
    alunos.value = []
  }
}

async function carregarNotasAlunos(idsAlunos) {
  if (!idsAlunos.length) return []
  try {
    const resp = await axios.get('/api/notas/lote', {
      params: {
        alunos: idsAlunos,
        periodo: periodoSelecionado.value
      }
    })
    return Array.isArray(resp.data) ? resp.data : []
  } catch {
    return []
  }
}

function resolverParametrosObjetivos() {
  let turmaId = turmaSelecionada.value
  let idMateria = materiaSelecionada.value

  if (String(materiaSelecionada.value).startsWith('custom_')) {
    turmaId = materiaSelecionada.value.replace('custom_', '')
  } else if (String(turmaId).startsWith('custom_')) {
    turmaId = turmaId.replace('custom_', '')
  }

  return { turmaId, idMateria }
}

function obterNotaDoMapa(mapa, idObjetivo, tipoNota) {
  return mapa.get(`${idObjetivo}__${tipoNota}`) ?? ''
}

function nomeMateriaSelecionada() {
  const materia = materias.value.find(m => String(m.id_materia) === String(materiaSelecionada.value))
  return materia?.nome_materia || String(materiaSelecionada.value || '')
}

function nomeTurmaSelecionada() {
  const turma = turmas.value.find(t => String(t.id_turma) === String(turmaSelecionada.value))
  return turma?.nome_turma || String(turmaSelecionada.value || '')
}

function valorPdf(nota) {
  if (nota === null || nota === undefined || nota === '') return '-'
  return String(nota)
}

function normalizarParaNomeArquivo(valor) {
  return String(valor || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-zA-Z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
    .toLowerCase()
}

async function exportarEmPdf() {
  if (!linhasExportacao.value.length || exportandoPdf.value) return

  exportandoPdf.value = true

  try {
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' })

    doc.setFontSize(14)
    doc.text('Exportacao de notas', 40, 36)

    doc.setFontSize(10)
    doc.text(
      `Ano: ${anoSelecionado.value} | Periodo: ${periodoSelecionado.value} | Materia: ${nomeMateriaSelecionada()} | Turma: ${nomeTurmaSelecionada()}`,
      40,
      54
    )
    doc.text(
      `Registros: ${linhasExportacao.value.length} | Objetivos: ${objetivos.value.length} | Alunos: ${alunos.value.length}`,
      40,
      70
    )

    const body = []
    linhasAgrupadasPorAluno.value.forEach((grupo) => {
      body.push([
        {
          content: `Aluno: ${grupo.nome_aluno}`,
          colSpan: 6,
          styles: {
            fillColor: [241, 245, 253],
            fontStyle: 'bold',
            halign: 'left'
          }
        }
      ])

      grupo.linhas.forEach((linha) => {
        body.push([
          linha.tipo_objetivo === 'C' ? 'Conceitual' : 'Atitudinal',
          linha.nome_objetivo,
          valorPdf(linha.est),
          valorPdf(linha.prof),
          valorPdf(linha.rec),
          valorPdf(linha.cf)
        ])
      })
    })

    autoTable(doc, {
      startY: 84,
      head: [['Tipo', 'Objetivo', 'Est', 'Prof', 'Rec', 'CF']],
      body,
      theme: 'grid',
      styles: {
        fontSize: 9,
        cellPadding: 4,
        valign: 'middle'
      },
      headStyles: {
        fillColor: [232, 237, 248],
        textColor: 20
      },
      columnStyles: {
        0: { cellWidth: 80 },
        1: { cellWidth: 360 },
        2: { cellWidth: 45, halign: 'center' },
        3: { cellWidth: 45, halign: 'center' },
        4: { cellWidth: 45, halign: 'center' },
        5: { cellWidth: 45, halign: 'center' }
      },
      didParseCell: (data) => {
        if (
          data.section === 'body' &&
          data.row.raw &&
          Array.isArray(data.row.raw) &&
          data.row.raw.length === 1 &&
          data.row.raw[0]?.colSpan === 6
        ) {
          data.cell.styles.fillColor = [241, 245, 253]
          data.cell.styles.fontStyle = 'bold'
        }
      }
    })

    const anoArquivo = normalizarParaNomeArquivo(anoSelecionado.value)
    const periodoArquivo = normalizarParaNomeArquivo(periodoSelecionado.value)
    const materiaArquivo = normalizarParaNomeArquivo(nomeMateriaSelecionada())
    const turmaArquivo = normalizarParaNomeArquivo(nomeTurmaSelecionada())
    const nomeArquivo = `exportacao_notas_${anoArquivo}_${periodoArquivo}_${materiaArquivo}_${turmaArquivo}.pdf`
    doc.save(nomeArquivo)
  } catch (e) {
    erro.value = 'Nao foi possivel gerar o PDF com os dados exibidos.'
  } finally {
    exportandoPdf.value = false
  }
}

async function gerarTabelaExportacao() {
  if (!filtrosSelecionados.value) return

  erro.value = ''
  mostrarTabela.value = false
  carregandoTabela.value = true
  linhasExportacao.value = []

  try {
    await carregarAlunos()

    const { turmaId, idMateria } = resolverParametrosObjetivos()

    const respObjetivos = await axios.get('/api/objetivos', {
      params: {
        id_materia: idMateria,
        turma_id: turmaId,
        periodo: periodoSelecionado.value,
        ano: anoSelecionado.value
      }
    })

    objetivos.value = Array.isArray(respObjetivos.data) ? respObjetivos.data : []

    if (!objetivos.value.length || !alunos.value.length) {
      mostrarTabela.value = true
      return
    }

    // Uma única requisição para todos os alunos (antes era uma por aluno).
    const notas = await carregarNotasAlunos(alunos.value.map(aluno => aluno.id_aluno))

    const notasMapPorAluno = new Map()
    notas.forEach((nota) => {
      const chaveAluno = String(nota.id_aluno)
      if (!notasMapPorAluno.has(chaveAluno)) notasMapPorAluno.set(chaveAluno, new Map())
      notasMapPorAluno.get(chaveAluno).set(`${nota.id_objetivo}__${nota.tipo_nota}`, nota.nota)
    })

    const linhas = []
    objetivos.value.forEach((objetivo) => {
      alunos.value.forEach((aluno) => {
        const mapaNotas = notasMapPorAluno.get(String(aluno.id_aluno)) || new Map()
        linhas.push({
          id_objetivo: objetivo.id_objetivo,
          tipo_objetivo: objetivo.tipo_objetivo,
          nome_objetivo: objetivo.nome_objetivo,
          id_aluno: aluno.id_aluno,
          nome_aluno: aluno.nome_aluno,
          est: obterNotaDoMapa(mapaNotas, objetivo.id_objetivo, 'Est'),
          prof: obterNotaDoMapa(mapaNotas, objetivo.id_objetivo, 'Prof'),
          rec: obterNotaDoMapa(mapaNotas, objetivo.id_objetivo, 'Rec'),
          cf: obterNotaDoMapa(mapaNotas, objetivo.id_objetivo, 'CF')
        })
      })
    })

    linhasExportacao.value = linhas.sort((a, b) => {
      if (a.tipo_objetivo !== b.tipo_objetivo) return a.tipo_objetivo.localeCompare(b.tipo_objetivo)
      if (a.nome_objetivo !== b.nome_objetivo) return a.nome_objetivo.localeCompare(b.nome_objetivo)
      return a.nome_aluno.localeCompare(b.nome_aluno)
    })

    mostrarTabela.value = true
  } catch {
    erro.value = 'Não foi possível gerar a tabela de exportação para os filtros informados.'
  } finally {
    carregandoTabela.value = false
  }
}

onMounted(async () => {
  await carregarAnosObjetivos()
  await carregarMaterias()
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

.btn-gerar {
  margin-left: 0.5em;
  padding: 0.6em 1.2em;
  font-weight: bold;
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

.painel-tabela {
  margin-top: 1.5em;
}

.resumo {
  margin-bottom: 0.7em;
}

.btn-pdf {
  margin-left: 0.8em;
  padding: 0.45em 0.9em;
  font-weight: bold;
  background: #7d1f24;
  color: #fff;
  border: 1px solid #61161b;
  border-radius: 4px;
  cursor: pointer;
}

.btn-pdf:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.tabela-wrap {
  overflow-x: auto;
}

.tabela-exportacao {
  border-collapse: collapse;
  width: 100%;
  min-width: 900px;
  background: #fff;
}

.tabela-exportacao thead th {
  background: #e8edf8;
  position: sticky;
  top: 0;
}

.tabela-exportacao td,
.tabela-exportacao th {
  text-align: left;
  vertical-align: top;
}

.linha-grupo-aluno td {
  background: #f1f5fd;
}

.vazio {
  text-align: center !important;
  color: #666;
  font-style: italic;
}
</style>

<template>
  <div class="turmas-customizadas">
    <h2>Turmas customizadas</h2>
    <p>Configure aqui grupos de alunos personalizados por turma.</p>

    <div v-if="perfilLogado !== 'Administrativo'" class="painel-criacao">
      <!-- Combo de Matéria -->
      <div class="campo">
        <label for="materia">Matéria:</label>
        <select id="materia" v-model="materiaSelecionada">
          <option value="">Selecione</option>
          <option v-for="m in materias" :key="m.codigo" :value="m.codigo">
            {{ m.nome }}
          </option>
        </select>
      </div>

      <div class="campo">
        <label for="nome-turma">Nome da turma</label>
        <input
          id="nome-turma"
          type="text"
          v-model="nomeTurma"
        />
      </div>

      <div class="acoes">
        <button type="button" class="btn-criar" @click="criarTurma" :disabled="criando">
          {{ criando ? 'Criando...' : 'Criar turma' }}
        </button>
      </div>
    </div>

    <!-- Novo painel: Turmas criadas -->
    <div class="painel-lista">
      <div class="painel-lista-header">
        <h3>Turmas criadas</h3>
        <small v-if="materiaSelecionada">Filtrando por matéria selecionada</small>
      </div>

      <div v-if="carregandoTurmas" class="info">Carregando turmas...</div>
      <div v-else>
        <ul v-if="turmas.length" class="lista-turmas">
          <li v-for="t in turmas" :key="t.idPadrao" class="linha-turma">
            <div class="nome">
              {{ t.nome_turma }}
              <button
                class="btn-ver-alunos"
                type="button"
                @click="abrirModalListaAlunos(t)"
                :title="`Ver alunos da turma '${t.nome_turma}'`"
                aria-label="Ver alunos"
              >
                <!-- Ícone (olho) -->
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#374151" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <div class="data">{{ formataData(t.dt_criacao) }}</div>
            <button
              class="btn-atribuir"
              type="button"
              @click="abrirModalAtribuir(t)"
              :title="`Atribuir alunos à turma '${t.nome_turma}'`"
              aria-label="Atribuir alunos"
            >
              <svg viewBox="0 0 24 24" width="20" height="20" fill="#555">
                <circle cx="12" cy="8" r="4" />
                <path d="M4 20v-2c0-2.2 3.7-4 8-4s8 1.8 8 4v2" />
                <path d="M19 3v3h3v2h-3v3h-2V8h-3V6h3V3z" />
              </svg>
            </button>
          </li>
        </ul>
        <div v-else class="info">Nenhuma turma criada.</div>
      </div>
    </div>

    <!-- MODAL ATRIBUIR ALUNOS -->
    <div v-if="mostrarModalAtribuirAlunos" class="modal-overlay">
      <div class="modal-content-atribuir">
        <h3 style="margin-top:0;">
          Atribuir alunos à turma: {{ turmaCustomizadaSelecionada?.nome_turma }}
        </h3>

        <div class="campo">
          <label for="turma-base">Turma de origem (Sophia):</label>
          <select id="turma-base" v-model="turmaSophiaSelecionada" :disabled="carregandoTurmasSophia">
            <option value="">Selecione</option>
            <option v-for="t in turmasSophia" :key="t.codigo" :value="t.codigo">
              {{ t.nome }}
            </option>
          </select>
        </div>

        <div v-if="carregandoTurmasSophia" class="info-pequena">Carregando turmas...</div>
        <div v-else-if="!turmasSophia.length" class="info-pequena">
          Nenhuma turma retornada.
        </div>

        <!-- NOVO: lista de alunos -->
        <div class="bloco-alunos" v-if="turmaSophiaSelecionada">
          <h4 class="titulo-alunos">Alunos da turma selecionada</h4>
          <div v-if="carregandoAlunosBase" class="info-pequena">Carregando alunos...</div>
          <div v-else-if="erroAlunosBase" class="erro-alunos">{{ erroAlunosBase }}</div>
          <ul v-else-if="alunosTurmaBase.length" class="lista-alunos">
            <li
              v-for="al in alunosTurmaBase"
              :key="al.id_aluno"
              class="aluno-item"
            >
              <button
                class="btn-add-aluno"
                type="button"
                :disabled="alunosTurmaCustomizada.has(al.id_aluno) || adicionandoAlunoIds.has(al.id_aluno)"
                @click="adicionarAluno(al)"
                :title="alunosTurmaCustomizada.has(al.id_aluno) ? 'Já adicionado' : 'Adicionar aluno'"
              >
                <span v-if="alunosTurmaCustomizada.has(al.id_aluno)">✓</span>
                <span v-else-if="adicionandoAlunoIds.has(al.id_aluno)">...</span>
                <span v-else>+</span>
              </button>
              <span class="nome-aluno">{{ al.nome }}</span>
            </li>
          </ul>
          <div v-else class="info-pequena">Nenhum aluno encontrado para esta turma.</div>
        </div>

        <div class="acoes-modal">
          <button type="button" class="btn-sec" @click="fecharModalAtribuir">Cancelar</button>
          <button type="button" class="btn-pri" @click="confirmarSelecaoTurmaBase" :disabled="!turmaSophiaSelecionada">
            Continuar
          </button>
        </div>
      </div>
    </div>

    <!-- NOVO MODAL LISTA DE ALUNOS DA TURMA CUSTOMIZADA -->
    <div v-if="mostrarModalListaAlunos" class="modal-overlay">
      <div class="modal-content-atribuir">
        <h3 style="margin-top:0;">Alunos da turma: {{ turmaListaAlunos?.nome_turma }}</h3>

        <div v-if="carregandoListaAlunos" class="info-pequena">Carregando alunos...</div>
        <div v-else-if="erroListaAlunos" class="erro-alunos">{{ erroListaAlunos }}</div>
        <ul v-else-if="alunosListaTurma.length" class="lista-alunos">
          <li
            v-for="al in alunosListaTurma"
            :key="al.id_aluno"
            class="aluno-item aluno-item-remover"
          >
            <span class="nome-aluno">{{ al.nome }}</span>
            <button
              class="btn-remover-aluno"
              type="button"
              :disabled="removendoAlunoIds.has(al.id_aluno)"
              @click="removerAluno(al)"
              :title="removendoAlunoIds.has(al.id_aluno) ? 'Removendo...' : 'Remover aluno'"
              aria-label="Remover aluno"
            >
              <svg v-if="!removendoAlunoIds.has(al.id_aluno)" viewBox="0 0 24 24" width="16" height="16" stroke="#b91c1c" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18"/>
                <path d="M8 6V4h8v2"/>
                <path d="M10 11v6"/>
                <path d="M14 11v6"/>
                <path d="M5 6l1 14c.1 1 1 2 2 2h8c1 0 1.9-1 2-2l1-14"/>
              </svg>
              <span v-else style="font-size:.65rem;">...</span>
            </button>
          </li>
        </ul>
        <div v-else class="info-pequena">Nenhum aluno atribuído a esta turma.</div>

        <div class="acoes-modal">
          <button type="button" class="btn-pri" @click="fecharModalListaAlunos">Fechar</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const perfilLogado = ref(sessionStorage.getItem('perfil') || 'Não definido')
const nomeTurma = ref('')
const materias = ref([]) // [{ codigo, nome }]
const materiaSelecionada = ref('')
const criando = ref(false)

// Lista de turmas criadas
const turmas = ref([])
const carregandoTurmas = ref(false)

// NOVO: estado do modal de atribuição
const mostrarModalAtribuirAlunos = ref(false)
const carregandoTurmasSophia = ref(false)
const turmasSophia = ref([]) // [{codigo, nome}]
const turmaSophiaSelecionada = ref('')
const turmaCustomizadaSelecionada = ref(null)

// NOVO: alunos da turma base selecionada
const alunosTurmaBase = ref([])
const carregandoAlunosBase = ref(false)
const erroAlunosBase = ref('')

const alunosTurmaCustomizada = ref(new Set())          // IDs já atribuídos à turma customizada
const adicionandoAlunoIds = ref(new Set())             // IDs em processo de adição
const removendoAlunoIds = ref(new Set())               // IDs em processo de remoção

// NOVO: estado do modal de lista de alunos
const mostrarModalListaAlunos = ref(false)
const turmaListaAlunos = ref(null)
const carregandoListaAlunos = ref(false)
const erroListaAlunos = ref('')
const alunosListaTurma = ref([])

// cache opcional para alunos Sophia (evita múltiplos downloads)
const cacheAlunosSophia = ref(null)

async function carregarMateriasProfessor() {
  const tokenSophia = sessionStorage.getItem('tokenSophia')
  const codigoProfessor = sessionStorage.getItem('codigo')
  if (!tokenSophia || !codigoProfessor) {
    console.warn('⚠️ Token ou código do professor ausente')
    materias.value = []
    return
  }

  try {
    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2'
    const resp = await axios.get(url, { headers: { Token: tokenSophia } })

    const mapa = new Map()
    if (Array.isArray(resp.data)) {
      resp.data.forEach(turma => {
        if (Array.isArray(turma.professoresDisciplinas)) {
          turma.professoresDisciplinas.forEach(pd => {
            const isTitular = Array.isArray(pd.colaboradores) &&
              pd.colaboradores.some(c => String(c.codigo) === String(codigoProfessor))
            const isAux = Array.isArray(pd.colaboradoresAuxiliares) &&
              pd.colaboradoresAuxiliares.some(c => String(c.codigo) === String(codigoProfessor))
            if ((isTitular || isAux) && pd.disciplina && pd.disciplina.codigo != null) {
              const codigo = pd.disciplina.codigo
              const nome = pd.disciplina.nome || pd.disciplina.descricao || `Disciplina ${codigo}`
              if (!mapa.has(codigo)) {
                mapa.set(codigo, { codigo, nome })
              }
            }
          })
        }
      })
    }
    materias.value = Array.from(mapa.values()).sort((a, b) => a.nome.localeCompare(b.nome))
    const salva = sessionStorage.getItem('materia_customizada_sel')
    if (salva && materias.value.some(m => String(m.codigo) === String(salva))) {
      materiaSelecionada.value = salva
    }
  } catch (e) {
    console.error('❌ Erro ao carregar matérias do professor:', e)
    materias.value = []
  }
}

async function carregarTurmas() {
  try {
    carregandoTurmas.value = true
    const resp = await axios.get('/api/turmas-customizadas', {
      params: {
        id_disciplina_mae: materiaSelecionada.value || undefined
      }
    })
    
    // Obter códigos de disciplinas do professor logado
    const codigosDisciplina = JSON.parse(sessionStorage.getItem('codigo_disciplina') || '[]')
    
    turmas.value = Array.isArray(resp.data)
      ? resp.data
          .map(t => {
            const idPadrao = t.id ?? t.id_turma_customizada ?? t.idTurma ?? null
            return { ...t, idPadrao }
          })
          .filter(t => {
            // Filtra apenas turmas com idPadrao válido
            if (t.idPadrao === null) return false
            
            // Se for Administrativo, mostra todas
            if (perfilLogado.value === 'Administrativo') return true
            
            // Para professores, filtra por id_disciplina_mae
            return codigosDisciplina.some(codigo => 
              String(t.id_disciplina_mae) === String(codigo)
            )
          })
      : []
  } catch (e) {
    console.error('Erro ao carregar turmas customizadas:', e)
    turmas.value = []
  } finally {
    carregandoTurmas.value = false
  }
}

function formataData(dt) {
  if (!dt) return ''
  const d = new Date(dt)
  return isNaN(d.getTime()) ? String(dt) : d.toLocaleString()
}

async function criarTurma() {
  if (!materiaSelecionada.value) {
    alert('Selecione a matéria.')
    return
  }
  if (!nomeTurma.value.trim()) {
    alert('Digite o nome da turma.')
    return
  }
  try {
    criando.value = true
    const resp = await axios.post('/api/turmas-customizadas', {
      id_disciplina_mae: Number(materiaSelecionada.value),
      nome_turma: nomeTurma.value.trim()
    })
    const nova = resp.data
    const idPadrao = nova.id ?? nova.id_turma_customizada ?? nova.idTurma ?? null
    if (idPadrao) {
      if (!turmas.value.some(t => t.idPadrao === idPadrao)) {
        turmas.value.unshift({ ...nova, idPadrao })
      }
    } else {
      console.warn('Turma criada sem ID retornado:', nova)
    }
    sessionStorage.setItem('materia_customizada_sel', String(materiaSelecionada.value))
    alert('Turma criada com sucesso!')
    nomeTurma.value = ''
    await carregarTurmas()
  } catch (e) {
    console.error('Erro ao criar turma customizada:', e)
    alert('Não foi possível criar a turma.')
  } finally {
    criando.value = false
  }
}

async function carregarTurmasSophia() {
  if (turmasSophia.value.length) return
  const tokenSophia = sessionStorage.getItem('tokenSophia')
  if (!tokenSophia) return
  try {
    carregandoTurmasSophia.value = true
    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2'
    const resp = await axios.get(url, { headers: { Token: tokenSophia } })
    const lista = []
    if (Array.isArray(resp.data)) {
      resp.data.forEach(t => {
        const nome = t.descricao || t.nome || `Turma ${t.codigo}`
        if (t.codigo != null) {
          lista.push({ codigo: t.codigo, nome })
        }
      })
    }
    turmasSophia.value = lista.sort((a,b)=>a.nome.localeCompare(b.nome))
  } catch (e) {
    console.error('Erro ao carregar turmas Sophia:', e)
  } finally {
    carregandoTurmasSophia.value = false
  }
}

async function carregarAlunosDaTurmaBase() {
  alunosTurmaBase.value = []
  erroAlunosBase.value = ''
  if (!turmaSophiaSelecionada.value) return
  const tokenSophia = sessionStorage.getItem('tokenSophia')
  if (!tokenSophia) {
    erroAlunosBase.value = 'Token não encontrado.'
    return
  }
  try {
    carregandoAlunosBase.value = true
    const urlAlunos = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7&Inativos=false&ConsiderarTurmaFinalizada=false'
    const resp = await axios.get(urlAlunos, { headers: { Token: tokenSophia } })
    if (Array.isArray(resp.data)) {
      const codigoTurma = String(turmaSophiaSelecionada.value)
      alunosTurmaBase.value = resp.data
        .filter(a => Array.isArray(a.turmas) && a.turmas.some(t => String(t.codigo) === codigoTurma))
        .map(a => ({ id_aluno: a.codigo, nome: a.nome }))
        .sort((a,b) => a.nome.localeCompare(b.nome))
    }
  } catch (e) {
    console.error('Erro ao carregar alunos da turma base:', e)
    erroAlunosBase.value = 'Erro ao carregar alunos.'
  } finally {
    carregandoAlunosBase.value = false
  }
}

// Dispara carga de alunos ao escolher a turma Sophia
watch(turmaSophiaSelecionada, () => {
  carregarAlunosDaTurmaBase()
})

async function carregarAlunosTurmaCustomizada(idTurmaCustomizada) {
  alunosTurmaCustomizada.value = new Set()
  if (!idTurmaCustomizada) {
    console.warn('ID da turma customizada ausente ao carregar alunos.')
    return
  }
  try {
    const resp = await axios.get(`/api/turmas-customizadas/${idTurmaCustomizada}/alunos`)
    if (Array.isArray(resp.data)) {
      resp.data.forEach(r => alunosTurmaCustomizada.value.add(Number(r.id_aluno)))
    }
  } catch (e) {
    console.error('Erro ao carregar alunos da turma customizada', e)
  }
}

function abrirModalAtribuir(turma) {
  const idTurma = turma.id ?? turma.idPadrao ?? turma.id_turma_customizada ?? turma.idTurma ?? null
  if (!idTurma) {
    alert('Não foi possível identificar o ID desta turma.')
    return
  }
  turmaCustomizadaSelecionada.value = { ...turma, idPadrao: idTurma }
  mostrarModalAtribuirAlunos.value = true
  carregarTurmasSophia()
  carregarAlunosTurmaCustomizada(idTurma)
}

function fecharModalAtribuir() {
  mostrarModalAtribuirAlunos.value = false
  turmaSophiaSelecionada.value = ''
  turmaCustomizadaSelecionada.value = null
}

function confirmarSelecaoTurmaBase() {
  if (!turmaSophiaSelecionada.value) {
    alert('Selecione uma turma de origem.')
    return
  }
  // Futuro: carregar alunos da turma base para atribuição
  fecharModalAtribuir()
}

async function adicionarAluno(aluno) {
  if (!turmaCustomizadaSelecionada.value) return
  const idTurma = turmaCustomizadaSelecionada.value.idPadrao || turmaCustomizadaSelecionada.value.id
  if (!idTurma) {
    alert('ID da turma inválido.')
    return
  }
  const idAluno = aluno.id_aluno
  if (alunosTurmaCustomizada.value.has(idAluno) || adicionandoAlunoIds.value.has(idAluno)) return
  try {
    adicionandoAlunoIds.value.add(idAluno)
    await axios.post(`/api/turmas-customizadas/${idTurma}/alunos`, { id_aluno: idAluno })
    alunosTurmaCustomizada.value.add(idAluno)
  } catch (e) {
    console.error('Erro ao adicionar aluno', e)
    alert('Falha ao adicionar aluno.')
  } finally {
    adicionandoAlunoIds.value.delete(idAluno)
  }
}

async function removerAluno(al) {
  if (!turmaListaAlunos.value) return
  const idTurma = turmaListaAlunos.value.idPadrao || turmaListaAlunos.value.id || turmaListaAlunos.value.id_turma_customizada
  if (!idTurma) return
  const idAluno = al.id_aluno
  if (removendoAlunoIds.value.has(idAluno)) return
  if (!confirm(`Remover o aluno "${al.nome}" desta turma?`)) return
  try {
    removendoAlunoIds.value.add(idAluno)
    await axios.delete(`/api/turmas-customizadas/${idTurma}/alunos/${idAluno}`)
    // Atualiza lista modal
    alunosListaTurma.value = alunosListaTurma.value.filter(a => a.id_aluno !== idAluno)
    // Atualiza set usado no outro modal (se mesma turma em edição)
    if (turmaCustomizadaSelecionada.value &&
        (turmaCustomizadaSelecionada.value.idPadrao || turmaCustomizadaSelecionada.value.id) == idTurma) {
      alunosTurmaCustomizada.value.delete(idAluno)
    }
  } catch (e) {
    console.error('Erro ao remover aluno', e)
    alert('Falha ao remover aluno.')
  } finally {
    removendoAlunoIds.value.delete(idAluno)
  }
}

async function obterAlunosSophiaTodos() {
  if (cacheAlunosSophia.value) return cacheAlunosSophia.value
  const tokenSophia = sessionStorage.getItem('tokenSophia')
  if (!tokenSophia) return []
  try {
    const urlAlunos = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7&Inativos=false&ConsiderarTurmaFinalizada=false'
    const resp = await axios.get(urlAlunos, { headers: { Token: tokenSophia } })
    if (Array.isArray(resp.data)) {
      cacheAlunosSophia.value = resp.data.map(a => ({
        id_aluno: a.codigo,
        nome: a.nome
      }))
      return cacheAlunosSophia.value
    }
    return []
  } catch (e) {
    console.error('Erro ao obter alunos Sophia (cache):', e)
    return []
  }
}

async function abrirModalListaAlunos(turma) {
  const idTurma = turma.idPadrao || turma.id || turma.id_turma_customizada
  if (!idTurma) {
    alert('ID da turma inválido.')
    return
  }
  turmaListaAlunos.value = turma
  mostrarModalListaAlunos.value = true
  carregandoListaAlunos.value = true
  erroListaAlunos.value = ''
  alunosListaTurma.value = []

  try {
    // 1) Buscar IDs dos alunos vinculados
    const resp = await axios.get(`/api/turmas-customizadas/${idTurma}/alunos`)
    const ids = Array.isArray(resp.data) ? resp.data.map(r => Number(r.id_aluno)) : []

    if (!ids.length) {
      alunosListaTurma.value = []
      return
    }

    // 2) Obter lista completa (cache)
    const todos = await obterAlunosSophiaTodos()

    // 3) Filtrar pelos IDs vinculados
    alunosListaTurma.value = ids
      .map(id => todos.find(a => Number(a.id_aluno) === id) || { id_aluno: id, nome: '(Nome não encontrado)' })
      .sort((a,b) => a.nome.localeCompare(b.nome))
  } catch (e) {
    console.error('Erro ao listar alunos da turma customizada:', e)
    erroListaAlunos.value = 'Falha ao carregar alunos.'
  } finally {
    carregandoListaAlunos.value = false
  }
}

function fecharModalListaAlunos() {
  mostrarModalListaAlunos.value = false
  turmaListaAlunos.value = null
  alunosListaTurma.value = []
  erroListaAlunos.value = ''
}

onMounted(async () => {
  await carregarMateriasProfessor()
  await carregarTurmas()
})

// Recarrega lista ao trocar a matéria (filtro)
watch(materiaSelecionada, () => {
  carregarTurmas()
})
</script>

<style scoped>
.turmas-customizadas {
  padding: 1rem;
}

.painel-criacao {
  /* Card */
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 1rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);

  /* Layout existente */
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin: 1rem 0;
}

.painel-lista {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 1rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
  margin-top: 1rem;
}

.painel-lista-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.lista-turmas {
  list-style: none;
  padding: 0;
  margin: 0;
}

.linha-turma {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.55rem 0.25rem;
  border-bottom: 1px solid #f0f0f0;
}

.linha-turma .nome {
  flex: 1;
}

.linha-turma .data {
  font-size: 0.85rem;
  color: #6b7280;
  white-space: nowrap;
}

.btn-atribuir {
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.35rem 0.45rem;
  cursor: pointer;
  display: flex;
  align-items: center;
}

.btn-atribuir:hover svg {
  fill: #1d4ed8;
}

.campo {
  flex: 1;
  width: 92%;
}

.campo label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.35rem;
}

.campo input,
.campo select {
  width: 100%;
  padding: 0.55rem 0.7rem;
  border: 1px solid #ccc;
  border-radius: 6px;
  outline: none;
  background: #fff;
}

.acoes {
  display: flex;
  justify-content: flex-end;
}

.btn-criar {
  padding: 0.6rem 1rem;
  border: none;
  background: #1976d2;
  color: #fff;
  border-radius: 6px;
  cursor: pointer;
  white-space: nowrap;
}

.btn-criar:hover {
  background: #155fa8;
}

.info {
  color: #6b7280;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 4vh 1rem;
  z-index: 999;
}

.modal-content-atribuir {
  background: #fff;
  border-radius: 12px;
  padding: 1.25rem 1.25rem 1.5rem;
  width: 100%;
  max-width: 480px;
  max-height: 88vh;
  overflow-y: auto;
  box-shadow: 0 6px 28px -4px rgba(0,0,0,0.25);
}

.acoes-modal {
  display: flex;
  justify-content: flex-end;
  gap: .6rem;
  margin-top: 1.2rem;
}

.btn-pri, .btn-sec {
  padding: .55rem 1.1rem;
  font-size: .95rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
}

.btn-pri {
  background: #1d4ed8;
  color: #fff;
}
.btn-pri:hover { background:#1742b3; }

.btn-sec {
  background: #f3f4f6;
  color: #374151;
}
.btn-sec:hover { background:#e5e7eb; }

.info-pequena {
  font-size: .8rem;
  color: #6b7280;
  margin-top: .35rem;
}

.bloco-alunos {
  margin-top: 1rem;
  padding: .75rem .85rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fafafa;
}

.titulo-alunos {
  margin: 0 0 .5rem;
  font-size: .95rem;
  font-weight: 600;
}

.lista-alunos {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 90%;
  overflow-y: auto;
  font-size: .9rem;
}

.aluno-item {
  display: flex;
  align-items: center;
  gap: .55rem;
  padding: .28rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.aluno-item:last-child {
  border-bottom: none;
}

.btn-add-aluno {
  width: 32px;
  height: 28px;
  border: 1px solid #d1d5db;
  background: #f9fafb;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.btn-add-aluno:disabled {
  cursor: default;
  background: #e0f2fe;
  border-color: #93c5fd;
  color: #1d4ed8;
}

.btn-add-aluno:not(:disabled):hover {
  background: #e5f2ff;
  border-color: #93c5fd;
}

.nome-aluno {
  flex: 1;
  text-align: left;
  line-height: 1.2;
  word-break: break-word;
}

.erro-alunos {
  color: #b91c1c;
  font-size: .85rem;
}

.btn-ver-alunos {
  margin-left: .4rem;
  background: #eef2ff;
  border: 1px solid #c7d2fe;
  border-radius: 6px;
  padding: .25rem .35rem;
  cursor: pointer;
  vertical-align: middle;
}
.btn-ver-alunos:hover {
  background: #e0e7ff;
  border-color: #a5b4fc;
}

.aluno-item-remover {
  justify-content: space-between;
}

.btn-remover-aluno {
  border: 1px solid #fca5a5;
  background: #fee2e2;
  color: #b91c1c;
  width: 34px;
  height: 28px;
  display: flex;
  align-items:center;
  justify-content:center;
  border-radius:6px;
  cursor:pointer;
  padding:0;
}

.btn-remover-aluno:hover {
  background:#fecaca;
  border-color:#f87171;
}

.btn-remover-aluno:disabled {
  opacity:.6;
  cursor:default;
}
</style>
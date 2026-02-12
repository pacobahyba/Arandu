<template>
  <div class="opcoes-container">
    <h1>Opções do Sistema</h1>
    <p>Painel de configurações e simulação de perfis para testes.</p>
    
    <div class="perfil-atual-info">
      <strong>Perfil Atual:</strong> {{ perfilAtual }}
    </div>
    
    <!-- Seção de Simulação de Perfis -->
    <div class="secao-simulacao">
      <h2>Simulação de Perfis</h2>
      <p class="descricao-simulacao">
        Escolha um perfil para simular.
      </p>
      
      <div class="perfis-grid">
        <!-- Card Professor com expansão -->
        <div :class="['perfil-card', { 'expandido': mostrandoProfessores }]">
          <h3>👨‍🏫 Professor</h3>
          <p v-if="!mostrandoProfessores">Simula um professor com acesso às turmas e disciplinas.</p>
          
          <button 
            v-if="!mostrandoProfessores"
            @click="abrirListaProfessores" 
            :disabled="simulando || carregandoProfessores"
          >
            {{ carregandoProfessores ? 'Carregando...' : 'Simular Professor' }}
          </button>
          
          <!-- Lista de professores expandida -->
          <div v-if="mostrandoProfessores" class="lista-professores">
            <div class="header-lista">
              <h4>Selecione um professor:</h4>
              <button class="btn-fechar" @click="fecharListaProfessores">✕</button>
            </div>
            
            <div class="busca-professor">
              <input 
                type="text" 
                v-model="buscaProfessor" 
                placeholder="Buscar por nome ou email..."
                class="input-busca"
              />
            </div>
            
            <div class="container-lista">
              <div 
                v-for="professor in professoresFiltrados" 
                :key="professor.codigo"
                class="item-professor"
                @click="selecionarProfessor(professor)"
              >
                <div class="professor-info">
                  <strong>{{ professor.nome }}</strong>
                  <span class="professor-email">{{ professor.email || 'Sem email' }}</span>
                  <span class="professor-codigo">Código: {{ professor.codigo }}</span>
                </div>
                <div class="professor-acao">
                  <button class="btn-selecionar">Selecionar</button>
                </div>
              </div>
              
              <div v-if="professoresFiltrados.length === 0" class="sem-resultados">
                Nenhum professor encontrado com esse termo de busca.
              </div>
            </div>
          </div>
        </div>
        
        <div class="perfil-card">
          <h3>👨‍💼 Administrativo</h3>
          <p>Simula acesso administrativo ao sistema.</p>
          <button @click="simularAdministrativo" :disabled="simulando">
            {{ simulando ? 'Simulando...' : 'Simular Administrativo' }}
          </button>
        </div>
        
        <!-- Card Aluno com expansão e accordion -->
        <div :class="['perfil-card', { 'expandido': mostrandoAlunos }]">
          <h3>👨‍🎓 Aluno</h3>
          <p v-if="!mostrandoAlunos">Simula um aluno com acesso às avaliações.</p>
          
          <button 
            v-if="!mostrandoAlunos"
            @click="abrirListaAlunos" 
            :disabled="simulando || carregandoAlunos"
          >
            {{ carregandoAlunos ? 'Carregando...' : 'Simular Aluno' }}
          </button>
          
          <!-- Lista de alunos expandida com accordion -->
          <div v-if="mostrandoAlunos" class="lista-alunos">
            <div class="header-lista">
              <h4>Selecione um aluno:</h4>
              <button class="btn-fechar" @click="fecharListaAlunos">✕</button>
            </div>
            
            <div class="busca-aluno">
              <input 
                type="text" 
                v-model="buscaAluno" 
                placeholder="Buscar por nome do aluno..."
                class="input-busca"
              />
            </div>
            
            <div class="container-accordion">
              <!-- Accordion por turma -->
              <div 
                v-for="turma in turmasComAlunos" 
                :key="turma.codigo"
                class="accordion-item"
              >
                <div 
                  class="accordion-header"
                  @click="toggleTurma(turma.codigo)"
                  :class="{ 'ativo': turmaExpandida === turma.codigo }"
                >
                  <span class="accordion-titulo">
                    📚 {{ turma.nome }} 
                    <small>({{ turma.alunos.length }} alunos)</small>
                  </span>
                  <span class="accordion-icone">
                    {{ turmaExpandida === turma.codigo ? '▼' : '▶' }}
                  </span>
                </div>
                
                <div 
                  v-show="turmaExpandida === turma.codigo"
                  class="accordion-content"
                >
                  <div 
                    v-for="aluno in turma.alunos" 
                    :key="aluno.codigo"
                    class="item-aluno"
                    @click="selecionarAluno(aluno)"
                  >
                    <div class="aluno-info">
                      <strong>{{ aluno.nome }}</strong>
                      <span class="aluno-codigo">Código: {{ aluno.codigo }}</span>
                    </div>
                    <div class="aluno-acao">
                      <button class="btn-selecionar">Selecionar</button>
                    </div>
                  </div>
                  
                  <div v-if="turma.alunos.length === 0" class="sem-resultados">
                    Nenhum aluno nesta turma.
                  </div>
                </div>
              </div>
              
              <div v-if="turmasComAlunos.length === 0" class="sem-resultados">
                Nenhuma turma encontrada com alunos.
              </div>
            </div>
          </div>
        </div>
        
        <div class="perfil-card desenvolvedor">
          <h3>💻 Desenvolvedor</h3>
          <p>Voltar ao modo desenvolvedor.</p>
          <button @click="voltarDesenvolvedor" :disabled="simulando" class="btn-desenvolvedor">
            Voltar para Desenvolvedor
          </button>
        </div>
      </div>
      
      <div v-if="mensagem" :class="['mensagem', mensagemTipo]">
        {{ mensagem }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const perfilAtual = ref(sessionStorage.getItem('perfil') || 'Desenvolvedor')
const simulando = ref(false)
const mensagem = ref('')
const mensagemTipo = ref('') // 'sucesso' ou 'erro'
const mostrandoProfessores = ref(false)
const carregandoProfessores = ref(false)
const professores = ref([])
const buscaProfessor = ref('')
const mostrandoAlunos = ref(false)
const carregandoAlunos = ref(false)
const alunos = ref([])
const turmas = ref([])
const buscaAluno = ref('')
const turmaExpandida = ref(null)

const professoresFiltrados = computed(() => {
  if (!buscaProfessor.value) return professores.value
  
  const termo = buscaProfessor.value.toLowerCase()
  return professores.value.filter(prof => 
    prof.nome.toLowerCase().includes(termo) ||
    (prof.email && prof.email.toLowerCase().includes(termo)) ||
    String(prof.codigo).includes(termo)
  )
})

const turmasComAlunos = computed(() => {
  if (!turmas.value || turmas.value.length === 0) return []
  
  const termo = buscaAluno.value.toLowerCase()
  
  return turmas.value
    .map(turma => ({
      ...turma,
      alunos: turma.alunos.filter(aluno => 
        !termo || aluno.nome.toLowerCase().includes(termo)
      )
    }))
    .filter(turma => turma.alunos.length > 0)
})

// Atualiza o perfil quando a sessão mudar
onMounted(() => {
  perfilAtual.value = sessionStorage.getItem('perfil') || 'Desenvolvedor'
})

function showAlert(msg) {
  alert(msg)
}

async function abrirListaProfessores() {
  carregandoProfessores.value = true
  mensagem.value = ''
  
  try {
    const tokenSophia = await autenticarSophia()
    
    if (!tokenSophia) {
      throw new Error('Erro ao autenticar na Sophia')
    }
    
    // Busca todos os colaboradores da API Sophia
    const respColabs = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Colaboradores',
      { headers: { Token: tokenSophia } }
    )
    
    // Filtra apenas colaboradores que lecionam e ordena por nome
    professores.value = respColabs.data
      .filter(c => c.leciona === true)
      .sort((a, b) => a.nome.localeCompare(b.nome))
    
    // Salva o token para usar depois
    sessionStorage.setItem('tokenSophia_temp', tokenSophia)
    
    if (professores.value.length === 0) {
      mensagem.value = '❌ Nenhum professor encontrado'
      mensagemTipo.value = 'erro'
    } else {
      mostrandoProfessores.value = true
    }
    
  } catch (error) {
    console.error('Erro ao carregar professores:', error)
    mensagem.value = '❌ Erro ao carregar lista de professores. Verifique o console.'
    mensagemTipo.value = 'erro'
  } finally {
    carregandoProfessores.value = false
  }
}

function fecharListaProfessores() {
  mostrandoProfessores.value = false
  buscaProfessor.value = ''
}

async function abrirListaAlunos() {
  carregandoAlunos.value = true
  mensagem.value = ''
  
  try {
    const tokenSophia = await autenticarSophia()
    
    if (!tokenSophia) {
      throw new Error('Erro ao autenticar na Sophia')
    }
    
    // Busca turmas
    const respTurmas = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2',
      { headers: { Token: tokenSophia } }
    )
    
    // Busca alunos
    const respAlunos = await axios.get(
      'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7&Inativos=false&ConsiderarTurmaFinalizada=false',
      { headers: { Token: tokenSophia } }
    )
    
    // Organiza alunos por turma
    turmas.value = respTurmas.data.map(turma => ({
      codigo: turma.codigo,
      nome: turma.nome,
      alunos: respAlunos.data
        .filter(aluno => 
          aluno.turmas && 
          aluno.turmas.some(t => t.codigo === turma.codigo)
        )
        .sort((a, b) => a.nome.localeCompare(b.nome))
    }))
    .filter(turma => turma.alunos.length > 0) // Apenas turmas com alunos
    .sort((a, b) => a.nome.localeCompare(b.nome))
    
    alunos.value = respAlunos.data
    
    // Salva o token para usar depois
    sessionStorage.setItem('tokenSophia_temp', tokenSophia)
    
    if (turmas.value.length === 0) {
      mensagem.value = '❌ Nenhuma turma com alunos encontrada'
      mensagemTipo.value = 'erro'
    } else {
      mostrandoAlunos.value = true
    }
    
  } catch (error) {
    console.error('Erro ao carregar alunos:', error)
    mensagem.value = '❌ Erro ao carregar lista de alunos. Verifique o console.'
    mensagemTipo.value = 'erro'
  } finally {
    carregandoAlunos.value = false
  }
}

function fecharListaAlunos() {
  mostrandoAlunos.value = false
  buscaAluno.value = ''
  turmaExpandida.value = null
}

function toggleTurma(codigoTurma) {
  turmaExpandida.value = turmaExpandida.value === codigoTurma ? null : codigoTurma
}

async function selecionarAluno(aluno) {
  simulando.value = true
  mensagem.value = ''
  
  try {
    const tokenSophia = sessionStorage.getItem('tokenSophia_temp')
    
    if (!tokenSophia) {
      throw new Error('Token Sophia não encontrado')
    }
    
    // Popula variáveis de sessão
    sessionStorage.setItem('perfil', 'Aluno')
    sessionStorage.setItem('aluno_codigo', aluno.codigo)
    sessionStorage.setItem('aluno_nome', aluno.nome)
    sessionStorage.setItem('email', aluno.email || 'aluno@exemplo.com')
    sessionStorage.setItem('tokenSophia', tokenSophia)
    
    // Salva códigos das turmas do aluno
    if (aluno.turmas && aluno.turmas.length > 0) {
      // Filtra turmas, ignorando aquelas com "Pré Matrícula" na descrição/nome
      const turmasFiltradas = aluno.turmas.filter(t => {
        const texto = (t.descricao || t.nome || '').toLowerCase()
        return !(texto.includes('pré matrícula') || texto.includes('pre matricula'))
      })
      const codigosTurmas = turmasFiltradas.map(t => t.codigo)
      sessionStorage.setItem('aluno_codigos_turma', JSON.stringify(codigosTurmas))
    }
    
    perfilAtual.value = 'Aluno'
    mensagem.value = `✅ Simulando Aluno: ${aluno.nome}`
    mensagemTipo.value = 'sucesso'
    
    // Limpa token temporário
    sessionStorage.removeItem('tokenSophia_temp')
    
    // Recarrega a página após 1.5s para aplicar mudanças
    setTimeout(() => {
      window.location.reload()
    }, 1500)
    
  } catch (error) {
    console.error('Erro ao simular aluno:', error)
    mensagem.value = '❌ Erro ao simular aluno. Verifique o console.'
    mensagemTipo.value = 'erro'
  } finally {
    simulando.value = false
  }
}

async function selecionarProfessor(professor) {
  simulando.value = true
  mensagem.value = ''
  
  try {
    const tokenSophia = sessionStorage.getItem('tokenSophia_temp')
    
    if (!tokenSophia) {
      throw new Error('Token Sophia não encontrado')
    }
    
    // Popula variáveis de sessão
    sessionStorage.setItem('perfil', 'Professor')
    sessionStorage.setItem('codigo', professor.codigo)
    sessionStorage.setItem('nome', professor.nome)
    sessionStorage.setItem('email', professor.email || 'professor@exemplo.com')
    sessionStorage.setItem('tokenSophia', tokenSophia)
    
    // Busca disciplinas do professor
    await buscarDisciplinasProfessor(professor.codigo, tokenSophia)
    
    perfilAtual.value = 'Professor'
    mensagem.value = `✅ Simulando Professor: ${professor.nome}`
    mensagemTipo.value = 'sucesso'
    
    // Limpa token temporário
    sessionStorage.removeItem('tokenSophia_temp')
    
    // Recarrega a página após 1.5s para aplicar mudanças
    setTimeout(() => {
      window.location.reload()
    }, 1500)
    
  } catch (error) {
    console.error('Erro ao simular professor:', error)
    mensagem.value = '❌ Erro ao simular professor. Verifique o console.'
    mensagemTipo.value = 'erro'
  } finally {
    simulando.value = false
  }
}

async function simularProfessor() {
  // Função mantida por compatibilidade, mas agora usa abrirListaProfessores
  await abrirListaProfessores()
}

async function simularAdministrativo() {
  simulando.value = true
  mensagem.value = ''
  
  try {
    const tokenSophia = await autenticarSophia()
    
    if (!tokenSophia) {
      throw new Error('Erro ao autenticar na Sophia')
    }
    
    // Popula variáveis de sessão para Administrativo
    sessionStorage.setItem('perfil', 'Administrativo')
    sessionStorage.setItem('codigo', '99999')
    sessionStorage.setItem('nome', 'Administrador Simulado')
    sessionStorage.setItem('email', 'admin@exemplo.com')
    sessionStorage.setItem('tokenSophia', tokenSophia)
    
    perfilAtual.value = 'Administrativo'
    mensagem.value = '✅ Simulando Administrativo'
    mensagemTipo.value = 'sucesso'
    
    setTimeout(() => {
      window.location.reload()
    }, 1500)
    
  } catch (error) {
    console.error('Erro ao simular administrativo:', error)
    mensagem.value = '❌ Erro ao simular administrativo. Verifique o console.'
    mensagemTipo.value = 'erro'
  } finally {
    simulando.value = false
  }
}

function voltarDesenvolvedor() {
  // Limpa todas as variáveis de sessão exceto as essenciais
  const token = sessionStorage.getItem('token')
  const tokenSophia = sessionStorage.getItem('tokenSophia')
  
  sessionStorage.clear()
  
  sessionStorage.setItem('perfil', 'Desenvolvedor')
  sessionStorage.setItem('token', token)
  sessionStorage.setItem('tokenSophia', tokenSophia)
  sessionStorage.setItem('email', 'arandu@arandu.com.br')
  
  perfilAtual.value = 'Desenvolvedor'
  mensagem.value = '✅ Voltou para modo Desenvolvedor'
  mensagemTipo.value = 'sucesso'
  
  setTimeout(() => {
    window.location.reload()
  }, 1500)
}

async function autenticarSophia() {
  try {
    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Autenticacao'
    const payload = { usuario: 'FichadeAvaliacao', senha: '123456' }
    const resp = await axios.post(url, payload, {
      headers: { 'Content-Type': 'application/json' }
    })
    
    const token = resp?.data?.token || resp?.data?.Token || resp?.data
    return String(token).replace(/"/g, '')
  } catch (err) {
    console.error('Erro ao autenticar na Sophia:', err)
    return null
  }
}

async function buscarDisciplinasProfessor(codigoProfessor, tokenSophia) {
  try {
    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2'
    const resp = await axios.get(url, { headers: { Token: tokenSophia } })
    
    const codigosDisciplina = []
    resp.data.forEach(turma => {
      if (Array.isArray(turma.professoresDisciplinas)) {
        turma.professoresDisciplinas.forEach(pd => {
          const isTitular = Array.isArray(pd.colaboradores) &&
            pd.colaboradores.some(colab => String(colab.codigo) === String(codigoProfessor))
          const isAuxiliar = Array.isArray(pd.colaboradoresAuxiliares) &&
            pd.colaboradoresAuxiliares.some(colab => String(colab.codigo) === String(codigoProfessor))
          if ((isTitular || isAuxiliar) && pd.disciplina && pd.disciplina.codigo !== undefined) {
            codigosDisciplina.push(pd.disciplina.codigo)
          }
        })
      }
    })
    
    const codigosUnicos = Array.from(new Set(codigosDisciplina))
    sessionStorage.setItem('codigo_disciplina', JSON.stringify(codigosUnicos))
    
    // Verifica se pode acessar turmas customizadas
    const codigosPermitidos = [6, 11, 22, 26, 93]
    const podeCustomizadas = codigosUnicos.map(Number).some(codigo => codigosPermitidos.includes(codigo))
    sessionStorage.setItem('pode_turmas_customizadas', podeCustomizadas ? '1' : '0')
    
  } catch (e) {
    console.error('Erro ao buscar disciplinas:', e)
  }
}
</script>

<style scoped>
.opcoes-container {
  padding: 2em;
  max-width: 1200px;
  margin: 0 auto;
}

h1 {
  color: #73331d;
  margin-bottom: 0.5em;
}

h2 {
  color: #b91509;
  margin-top: 2em;
  margin-bottom: 1em;
  border-bottom: 2px solid #b91509;
  padding-bottom: 0.5em;
}

.perfil-atual-info {
  background: #f0f8ff;
  border: 2px solid #4a90e2;
  border-radius: 8px;
  padding: 1em;
  margin: 1em 0;
  font-size: 1.1em;
  color: #2c3e50;
}

.secao-simulacao {
  background: #fafafa;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 2em;
  margin: 2em 0;
}

.descricao-simulacao {
  color: #666;
  margin-bottom: 1.5em;
  font-size: 1.05em;
}

.perfis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5em;
  margin-bottom: 1.5em;
}

.perfil-card {
  background: #fff;
  border: 2px solid #ddd;
  border-radius: 10px;
  padding: 1.5em;
  text-align: center;
  transition: all 0.3s;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.perfil-card.expandido {
  grid-column: 1 / -1;
  text-align: left;
  max-width: 100%;
}

.perfil-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
  border-color: #b91509;
}

.perfil-card.expandido:hover {
  transform: none;
}

.perfil-card.desenvolvedor {
  border-color: #6c757d;
}

.perfil-card.desenvolvedor:hover {
  border-color: #343a40;
}

.perfil-card h3 {
  color: #b91509;
  margin: 0 0 0.5em 0;
  font-size: 1.3em;
}

.perfil-card.desenvolvedor h3 {
  color: #6c757d;
}

.perfil-card p {
  color: #666;
  margin-bottom: 1em;
  line-height: 1.5;
  min-height: 3em;
}

.perfil-card button {
  background: #b91509;
  color: white;
  border: none;
  padding: 0.75em 1.5em;
  border-radius: 5px;
  cursor: pointer;
  font-weight: bold;
  font-size: 0.95em;
  transition: all 0.2s;
  width: 100%;
}

.perfil-card button:hover:not(:disabled) {
  background: #8b1007;
  transform: scale(1.05);
}

.perfil-card button:disabled {
  background: #ccc;
  cursor: not-allowed;
  opacity: 0.6;
}

.perfil-card button.btn-desenvolvedor {
  background: #6c757d;
}

.perfil-card button.btn-desenvolvedor:hover:not(:disabled) {
  background: #5a6268;
}

/* Estilos da lista de professores expandida */
.lista-professores {
  margin-top: 1em;
  animation: expandir 0.3s ease-out;
}

@keyframes expandir {
  from {
    opacity: 0;
    max-height: 0;
  }
  to {
    opacity: 1;
    max-height: 1000px;
  }
}

.header-lista {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1em;
  padding-bottom: 0.75em;
  border-bottom: 2px solid #b91509;
}

.header-lista h4 {
  margin: 0;
  color: #b91509;
  font-size: 1.2em;
}

.btn-fechar {
  background: #dc3545;
  color: white;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.2em;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-fechar:hover {
  background: #c82333;
  transform: rotate(90deg);
}

.busca-professor {
  margin-bottom: 1em;
}

.input-busca {
  width: 100%;
  padding: 0.75em;
  border: 2px solid #ddd;
  border-radius: 8px;
  font-size: 1em;
  transition: border-color 0.2s;
}

.input-busca:focus {
  outline: none;
  border-color: #b91509;
}

.container-lista {
  max-height: 400px;
  overflow-y: auto;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: #f9f9f9;
}

.item-professor {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1em;
  border-bottom: 1px solid #e0e0e0;
  background: white;
  transition: all 0.2s;
  cursor: pointer;
}

.item-professor:last-child {
  border-bottom: none;
}

.item-professor:hover {
  background: #f0f8ff;
  transform: translateX(5px);
}

.professor-info {
  display: flex;
  flex-direction: column;
  gap: 0.3em;
  flex: 1;
}

.professor-info strong {
  color: #2c3e50;
  font-size: 1.05em;
}

.professor-email {
  color: #666;
  font-size: 0.9em;
}

.professor-codigo {
  color: #999;
  font-size: 0.85em;
}

.professor-acao {
  margin-left: 1em;
}

.btn-selecionar {
  background: #28a745;
  color: white;
  border: none;
  padding: 0.6em 1.2em;
  border-radius: 5px;
  cursor: pointer;
  font-weight: bold;
  font-size: 0.9em;
  transition: all 0.2s;
}

.btn-selecionar:hover {
  background: #218838;
  transform: scale(1.05);
}

.sem-resultados {
  padding: 2em;
  text-align: center;
  color: #999;
  font-style: italic;
}

.mensagem {
  padding: 1em;
  border-radius: 8px;
  margin-top: 1.5em;
  font-weight: bold;
  text-align: center;
  animation: fadeIn 0.3s ease-in;
}

.mensagem.sucesso {
  background: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
}

.mensagem.erro {
  background: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.opcoes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2em;
  margin-top: 3em;
}

.opcao-card {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 1.5em;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.opcao-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.opcao-card h3 {
  color: #b91509;
  margin-top: 0;
  margin-bottom: 0.5em;
}

.opcao-card p {
  color: #666;
  margin-bottom: 1em;
  line-height: 1.5;
}

.opcao-card button {
  background: #b91509;
  color: white;
  border: none;
  padding: 0.75em 1.5em;
  border-radius: 5px;
  cursor: pointer;
  font-weight: bold;
  transition: background 0.2s;
}

.opcao-card button:hover {
  background: #8b1007;
}

.opcao-card button:active {
  transform: scale(0.98);
}

@media (max-width: 768px) {
  .opcoes-container {
    padding: 1em;
  }
  
  .secao-simulacao {
    padding: 1.5em;
  }
  
  .perfis-grid,
  .opcoes-grid {
    grid-template-columns: 1fr;
    gap: 1em;
  }
}

/* Estilos para lista de alunos com accordion */
.lista-alunos {
  animation: expandir 0.3s ease-out;
  margin-top: 1em;
}

.container-accordion {
  max-height: 500px;
  overflow-y: auto;
  margin-top: 1em;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  background: #fafafa;
}

.accordion-item {
  border-bottom: 1px solid #e0e0e0;
}

.accordion-item:last-child {
  border-bottom: none;
}

.accordion-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1em 1.5em;
  background: #fff;
  cursor: pointer;
  transition: background 0.2s;
  user-select: none;
}

.accordion-header:hover {
  background: #f5f5f5;
}

.accordion-header.ativo {
  background: #e8f4fd;
  border-bottom: 2px solid #2196F3;
}

.accordion-titulo {
  font-weight: 600;
  color: #333;
  display: flex;
  align-items: center;
  gap: 0.5em;
}

.accordion-titulo small {
  font-weight: normal;
  color: #666;
  font-size: 0.85em;
}

.accordion-icone {
  font-size: 0.85em;
  color: #666;
  transition: transform 0.3s;
}

.accordion-content {
  padding: 0.5em 1em 1em 1em;
  background: #fafafa;
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    max-height: 0;
  }
  to {
    opacity: 1;
    max-height: 500px;
  }
}

.item-aluno {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1em;
  margin-bottom: 0.5em;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.item-aluno:hover {
  background: #f0f8ff;
  border-color: #2196F3;
  transform: translateX(5px);
  box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
}

.aluno-info {
  display: flex;
  flex-direction: column;
  gap: 0.3em;
}

.aluno-info strong {
  color: #333;
  font-size: 1em;
}

.aluno-codigo {
  font-size: 0.85em;
  color: #666;
}

.aluno-acao {
  display: flex;
  align-items: center;
}

.busca-aluno {
  margin-bottom: 1em;
}
</style>

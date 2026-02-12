<template>
  <div class="layout-principal">
    <div class="container-geral">
      <div v-if="loading" class="loading-container">
        <p>Carregando...</p>
      </div>
      <div v-else :class="['app-container', { 'fundo-centralizado': autenticado }]">
        <!-- Componente de Login -->
  <LoginComponent v-if="!autenticado" @login-sucesso="onLoginSucesso" />
        
        <!-- Conteúdo Principal após Login -->
        <div v-else class="main-content">
          <nav class="menu">
            <!-- seu menu existente -->
            <template v-if="perfilLogado === 'Sem perfil'">
              <button @click="logout" class="logout-button">Sair</button>
            </template>
            <!-- Menu exclusivo para Desenvolvedor -->
            <template v-else-if="perfilLogado === 'Desenvolvedor'">
              <button
                :class="{ ativo: paginaAtual === 'opcoes' }"
                @click="paginaAtual = 'opcoes'"
              >
                Opções
              </button>
              <button @click="logout" class="logout-button">Sair</button>
            </template>
            <!-- Menu para outros perfis -->
            <template v-else>
              <button
                :class="{ ativo: paginaAtual === 'inicio' }"
                @click="paginaAtual = 'inicio'"
              >
                Início
              </button>
              <button
                v-if="perfilLogado === 'Professor' || perfilLogado === 'Administrativo'"
                :class="{ ativo: paginaAtual === 'objetivos' }"
                @click="paginaAtual = 'objetivos'"
              >
                Objetivos
              </button>
              <!-- AJUSTE: só mostra para Administrativo ou Professor com disciplina 11 -->
              <button
                v-if="perfilLogado === 'Administrativo' || (perfilLogado === 'Professor' && podeTurmasCustomizadas)"
                :class="{ ativo: paginaAtual === 'turmas-customizadas' }"
                @click="paginaAtual = 'turmas-customizadas'"
              >
                Turmas customizadas
              </button>
              <button
                v-if="perfilLogado === 'Administrativo'"
                :class="{ ativo: paginaAtual === 'periodos' }"
                @click="paginaAtual = 'periodos'"
              >
                Períodos
              </button>
              <button
                v-if="perfilLogado !== 'Administrativo'"
                :class="{ ativo: paginaAtual === 'avaliacao' }"
                @click="paginaAtual = 'avaliacao'"
              >
                Avaliação
              </button>
              <button
                  v-if="perfilLogado === 'Administrativo' || perfilLogado === 'Professor'"
                :class="{ ativo: paginaAtual === 'exportar' }"
                @click="paginaAtual = 'exportar'"
              >
                Exportar
              </button>
              <button @click="logout" class="logout-button">Sair</button>
            </template>
          </nav>
          
          <div class="content-container">
            <div class="perfil-info">
              Perfil logado: {{ perfilLogado }}
              <div v-if="perfilLogado === 'Aluno' && alunoNome" class="nome-aluno">
                {{ alunoNome }}
              </div>
            </div>
            <div v-if="paginaAtual === 'inicio'">
              <div class="inicio-container">
                <img
                  src="/public/images/logo_arandu.png"
                  alt="Logo Arandu"
                  class="logo-circular"
                />
                <h1>Bem-vindo ao sistema Arandu!</h1>
                <p>Escolha uma opção no menu acima.</p>
              </div>
            </div>
            <div v-else-if="paginaAtual === 'objetivos'">
              <ObjetivoComponent />
            </div>
            <div v-else-if="paginaAtual === 'turmas-customizadas'">
              <TurmasCustomizadasComponent />
            </div>
            <div v-else-if="paginaAtual === 'avaliacao'">
              <AvaliacaoComponent />
            </div>
            <div v-else-if="paginaAtual === 'periodos'">
              <PeriodosComponent />
            </div>
            <div v-else-if="paginaAtual === 'exportar'">
              <ExportarComponent />
            </div>
            <div v-else-if="paginaAtual === 'opcoes'">
              <OpcoesComponent />
            </div>
          </div>
        </div>
        
        <div v-if="avisoExpirado" class="aviso-expirado">
          {{ avisoExpirado }}
        </div>
      </div>
    </div>
    <div v-if="!autenticado" class="logo-lateral"></div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useAuthStore } from './stores/auth'
import AvaliacaoComponent from './components/AvaliacaoComponent.vue'
import ObjetivoComponent from './components/ObjetivoComponent.vue'
import LoginComponent from './components/LoginComponent.vue'
import PeriodosComponent from './components/PeriodosComponent.vue'
import TurmasCustomizadasComponent from './components/TurmasCustomizadasComponent.vue'
import ExportarComponent from './components/ExportarComponent.vue'
import OpcoesComponent from './components/OpcoesComponent.vue'

// Estado da aplicação
const loading = ref(true)
const perfilLogado = ref('')
const avisoExpirado = ref('')
const alunoNome = ref('')

// NOVO: controle de permissão para Turmas customizadas
const podeTurmasCustomizadas = ref(false)

const auth = useAuthStore()

const paginaAtual = ref(localStorage.getItem('paginaAtual') || 'inicio')
const autenticado = computed(() => !!auth.token)

// Computed properties
const isLoggedIn = computed(() => {
  return sessionStorage.getItem('tokenSophia') && sessionStorage.getItem('perfil')
})

const userDisplayName = computed(() => {
  return sessionStorage.getItem('nome') || 
         sessionStorage.getItem('aluno_nome') || 
         sessionStorage.getItem('email') || 
         'Usuário'
})

// Salva a página sempre que mudar
watch(paginaAtual, async (novaPagina) => {
  // Valide o token Sophia ao trocar de seção (exceto para Desenvolvedor e Aluno)
  const perfil = sessionStorage.getItem('perfil')
  if (perfil !== 'Desenvolvedor' && perfil !== 'Aluno') {
    const tokenValido = await validarTokenSophia()
    if (!tokenValido) {
      await autenticarSophia()
    }
  }
  localStorage.setItem('paginaAtual', novaPagina)
})

// Atualiza perfil e nome quando o usuário faz login
watch(autenticado, async (estaAutenticado) => {
  if (estaAutenticado) {
    const perfil = sessionStorage.getItem('perfil')
    
    // Se o perfil for 'Desenvolvedor', apenas atualiza a variável local
    if (perfil === 'Desenvolvedor') {
      perfilLogado.value = 'Desenvolvedor'
      return
    }
    
    // Se o perfil for 'Sem perfil', busca o perfil correto na Sophia
    if (perfil === 'Sem perfil') {
      await autenticarSophia()
      await buscarCodigoProfessor()
    } else {
      // Se já tiver perfil definido, apenas atualiza
      perfilLogado.value = perfil
      alunoNome.value = sessionStorage.getItem('aluno_nome') || ''
    }
  }
})

// Monitora mudanças no perfil para atualizar nome do aluno
watch(perfilLogado, (novoPerfil) => {
  if (novoPerfil === 'Aluno') {
    alunoNome.value = sessionStorage.getItem('aluno_nome') || ''
  } else {
    alunoNome.value = ''
  }
})

// Verificação do token ao montar o App
onMounted(() => {
  const token = sessionStorage.getItem('token')
  if (token && !auth.token) {
    auth.setToken(token) // já configura axios via store
  } else if (auth.token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${auth.token}`
  }
  // Atualiza o perfil ao recarregar
  perfilLogado.value = sessionStorage.getItem('perfil') || 'Não definido'
  alunoNome.value = sessionStorage.getItem('aluno_nome') || ''
})

// Métodos
function logout() {
  // Limpa tokens e dados relacionados à sessão/integrações
  auth.logout()
  delete axios.defaults.headers.common['Authorization']
  delete axios.defaults.headers.common['Token']

  sessionStorage.clear()
  localStorage.removeItem('paginaAtual')
  localStorage.removeItem('loginTimestamp')

  // NÃO faça window.location.reload()
  // Apenas atualize os estados para mostrar o login
  perfilLogado.value = ''
  paginaAtual.value = 'inicio'
  avisoExpirado.value = ''
  // Se usar Pinia/Vuex, garanta que o token foi limpo
}

// função para autenticar na API Sophia e salvar tokenSophia
async function autenticarSophia() {
  try {
    const existing = sessionStorage.getItem('tokenSophia')
    if (existing) {
      axios.defaults.headers.common['Token'] = existing
      await buscarCodigoProfessor() // chama imediatamente se já tem tokenSophia
      return existing
    }

    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Autenticacao'
    const payload = { usuario: 'FichadeAvaliacao', senha: '123456' }
    const resp = await axios.post(url, payload, { headers: { 'Content-Type': 'application/json' } })

    const token = resp?.data?.token || resp?.data?.Token || resp?.data
    if (token) {
      sessionStorage.setItem('tokenSophia', String(token))
      axios.defaults.headers.common['Token'] = String(token)

      // após obter tokenSophia, buscar Professor usando o email salvo em session (se houver)
      await buscarCodigoProfessor()
      return token
    }
    return null
  } catch (err) {
    console.error('Erro ao autenticar na Sophia:', err)
    return null
  }
}

// busca Colaboradores?Email=... usando o email salvo em sessionStorage
async function buscarCodigoProfessor() {
  const email = sessionStorage.getItem('email')
  if (!email) return
  
  // Verificar se já está definido como Administrativo pelo backend
  const perfilAtual = sessionStorage.getItem('perfil')
  if (perfilAtual === 'Administrativo') {
    // Se já for Administrativo, mantém esse perfil e não sobrescreve
    perfilLogado.value = 'Administrativo'
    console.log('Mantendo perfil Administrativo definido pelo backend')
    return
  }

  try {
    const url = `https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Colaboradores?Email=${encodeURIComponent(email)}`
    const resp = await axios.get(url, {
      headers: {
        Token: sessionStorage.getItem('tokenSophia') || axios.defaults.headers.common['Token']
      }
    })

    if (Array.isArray(resp.data) && resp.data.length > 0) {
      const col = resp.data[0]

      if (col && col.codigo !== undefined) {
        sessionStorage.setItem('codigo', String(col.codigo))
        
        //array com os coordenadores
        const emailAdministrativo = ['pedrohenriqueb@ogamita.com.br', 'bethsaraiva@ogamita.com.br', 'monica@ogamita.com.br'];

        // Verificar o campo "leciona"
        if(emailAdministrativo.includes(email)){
          sessionStorage.setItem('perfil', 'Administrativo')
          perfilLogado.value = 'Administrativo'
        } else if (col.leciona === true || col.leciona === "true" || col.leciona === 1) {
          sessionStorage.setItem('perfil', 'Professor')
          perfilLogado.value = 'Professor'
        } 
        
        sessionStorage.setItem('nome', col.nome || '')
        
        // Só busca turmas se for professor
        if (perfilLogado.value === 'Professor') {
          await buscarTurmasEMateriasDoProfessor()
        }
        return true
      }
    } 
    
    // Se não encontrou professor, tenta buscar como aluno
    const encontrouAluno = await buscarAluno()
    
    // Se não encontrou nem como professor nem como aluno
    if (!encontrouAluno) {
      sessionStorage.setItem('perfil', 'Sem perfil')
      perfilLogado.value = 'Sem perfil'
      sessionStorage.removeItem('codigo')
      sessionStorage.removeItem('codigo_disciplina')
      sessionStorage.removeItem('nome')
      sessionStorage.removeItem('aluno_codigo')
      sessionStorage.removeItem('aluno_nome')
      sessionStorage.removeItem('aluno_codigos_turma')
    }
  } catch (e) {
    console.error('Erro ao buscar Professor Sophia:', e)
    // Tenta buscar como aluno mesmo em caso de erro
    await buscarAluno()
  }
}

// Busca turmas e armazena todos os códigos de disciplina em sessionStorage
async function buscarTurmasEMateriasDoProfessor() {
  const tokenSophia = sessionStorage.getItem('tokenSophia')
  const codigoProfessor = sessionStorage.getItem('codigo')
  if (!tokenSophia || !codigoProfessor) return

  try {
    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2'
    const resp = await axios.get(url, { headers: { Token: tokenSophia } })

    if (Array.isArray(resp.data)) {
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

      // NOVO: habilita o menu se possuir disciplina código 6, 11, 22, 26 ou 93
      const codigosPermitidos = [6, 11, 22, 26, 93]
      podeTurmasCustomizadas.value = codigosUnicos.map(Number).some(codigo => codigosPermitidos.includes(codigo))
      sessionStorage.setItem('pode_turmas_customizadas', podeTurmasCustomizadas.value ? '1' : '0')
    }
  } catch (e) {
    console.error('Erro ao buscar turmas/matérias:', e)
    sessionStorage.removeItem('codigo_disciplina')
    podeTurmasCustomizadas.value = false
    sessionStorage.removeItem('pode_turmas_customizadas')
  }
}

// Busca aluno pelo e-mail
async function buscarAluno() {
  const email = sessionStorage.getItem('email')
  if (!email) return false

  try {
    const tokenSophia = sessionStorage.getItem('tokenSophia')
    if (!tokenSophia) return false

    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Alunos?Unidades=2&Cursos=20,7'
    const resp = await axios.get(url, {
      headers: {
        Token: tokenSophia
      }
    })

    if (Array.isArray(resp.data)) {
      // Percorre todos os alunos até encontrar o email correspondente
      const emailLowerCase = email.toLowerCase()
      const alunoEncontrado = resp.data.find(aluno => 
        aluno.email && aluno.email.toLowerCase() === emailLowerCase
      )

      if (alunoEncontrado) {
        // Encontrou o aluno - salva seus dados na sessão
        sessionStorage.setItem('aluno_codigo', String(alunoEncontrado.codigo))
        sessionStorage.setItem('aluno_nome', alunoEncontrado.nome || '')
        
        // Salva códigos das turmas do aluno, se disponíveis
        if (Array.isArray(alunoEncontrado.turmas) && alunoEncontrado.turmas.length > 0) {
          // Filtra turmas, ignorando aquelas com "Pré Matrícula" na descrição
          const turmasFiltradas = alunoEncontrado.turmas.filter(turma => {
            const descricao = turma.descricao || ''
            return !descricao.toLowerCase().includes('pré matrícula')
          })
          
          const codigosTurmas = turmasFiltradas.map(t => t.codigo)
          sessionStorage.setItem('aluno_codigos_turma', JSON.stringify(codigosTurmas))
          
          // Busca os detalhes das turmas do aluno (incluindo professoresDisciplinas)
          await buscarTurmasDoAluno()
        }
        
        // Define o perfil como Aluno
        sessionStorage.setItem('perfil', 'Aluno')
        perfilLogado.value = 'Aluno'
        return true
      }
    }
    
    // Se chegou aqui, não encontrou o aluno
    return false
  } catch (e) {
    console.error('Erro ao buscar Aluno Sophia:', e)
    return false
  }
}

// Busca detalhes das turmas do aluno
async function buscarTurmasDoAluno() {
  const tokenSophia = sessionStorage.getItem('tokenSophia')
  const codigosTurma = JSON.parse(sessionStorage.getItem('aluno_codigos_turma') || '[]')
  
  if (!tokenSophia || !codigosTurma.length) return
  
  try {
    // Constrói a URL com os códigos das turmas do aluno
    const url = `https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Codigos=${codigosTurma.join(',')}`
    
    const resp = await axios.get(url, {
      headers: {
        Token: tokenSophia
      }
    })
    
    if (Array.isArray(resp.data)) {
      // Extrai todos os professoresDisciplinas de todas as turmas
      const professoresDisciplinas = []
      
      resp.data.forEach(turma => {
        if (Array.isArray(turma.professoresDisciplinas)) {
          // Adiciona a referência da turma para cada disciplina
          turma.professoresDisciplinas.forEach(pd => {
            professoresDisciplinas.push({
              ...pd,
              turmaCodigo: turma.codigo,
              turmaNome: turma.nome
            })
          })
        }
      })
      
      // Armazena em sessionStorage
      sessionStorage.setItem('professoresDisciplinas', JSON.stringify(professoresDisciplinas))
      console.log('Disciplinas das turmas do aluno carregadas:', professoresDisciplinas.length)
    }
  } catch (e) {
    console.error('Erro ao buscar detalhes das turmas do aluno:', e)
  }
}

// chamar após login bem-sucedido
async function onLoginSucesso() {
  // PRIMEIRO: Garanta que o auth store tem o token
  const token = sessionStorage.getItem('token')
  if (token) {
    auth.setToken(token)
  }
  
  // Salva o momento do login
  localStorage.setItem('loginTimestamp', Date.now().toString())
  
  const perfil = sessionStorage.getItem('perfil') || 'Não definido'
  perfilLogado.value = perfil
  
  // Se for Desenvolvedor, não faz chamadas à API Sophia
  if (perfil !== 'Desenvolvedor') {
    // Autenticar na Sophia e obter o token
    const tokenSophia = await autenticarSophia()
  }
  
  paginaAtual.value = 'inicio'
  
  // Forçar a atualização da UI
  await nextTick()
}

// também tenta autenticar Sophia no carregamento, se houver token da app
onMounted(async () => {
  // Mude 'token' para 'savedToken' aqui
  const savedToken = sessionStorage.getItem('token')
  if (savedToken && !auth.token) {
    auth.setToken(savedToken)
  }
  // Se houver token de sessão mas não houver timestamp de login, inicializa agora
  if (savedToken && !localStorage.getItem('loginTimestamp')) {
    localStorage.setItem('loginTimestamp', Date.now().toString())
  }
  // tenta autenticar Sophia e obter codigo de Professor se houver email
  // Otimização: evita chamada desnecessária se já existir tokenSophia
  const hasTokenSophia = !!sessionStorage.getItem('tokenSophia')
  if (auth.token && !hasTokenSophia) {
    await autenticarSophia()
  }
  // Verificar se já está logado (refresh de página)
  const perfil = sessionStorage.getItem('perfil')
  const token = sessionStorage.getItem('tokenSophia')
  
  if (perfil && token) {
    perfilLogado.value = perfil
    axios.defaults.headers.common['Token'] = token
    
    // Se for Aluno e tiver códigos de turma, buscar detalhes das turmas
    if (perfil === 'Aluno' && sessionStorage.getItem('aluno_codigos_turma')) {
      await buscarTurmasDoAluno()
    }
  }
  
  // NOVO: restaura flag se já calculada em sessão (ex.: refresh)
  if (perfil === 'Professor') {
    const flag = sessionStorage.getItem('pode_turmas_customizadas')
    if (flag !== null) {
      podeTurmasCustomizadas.value = flag === '1'
    }
  }

  // Verifica se o login expirou (24h)
  if (loginExpirado()) {
    avisoExpirado.value = 'Sessão expirada. Faça login novamente.'
    logout()
    loading.value = false
    return
  }

  loading.value = false
})

async function validarTokenSophia() {
  // Se for Desenvolvedor ou Aluno, não valida token Sophia
  const perfil = sessionStorage.getItem('perfil')
  if (perfil === 'Desenvolvedor' || perfil === 'Aluno') {
    return true
  }
  
  const tokenSophia = sessionStorage.getItem('tokenSophia')
  if (!tokenSophia) {
    avisoExpirado.value = 'Acesso expirado. Redirecionando para a página de login.'
    setTimeout(() => {
      logout()
    }, 2000)
    return false
  }

  try {
    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Unidades'
    await axios.get(url, {
      headers: { Token: tokenSophia }
    })
    return true // Token válido
  } catch (e) {
    avisoExpirado.value = 'Acesso expirado. Redirecionando para a página de login.'
    setTimeout(() => {
      logout()
    }, 2000)
    return false
  }
}

async function aoTrocarDeSecao() {
  const tokenValido = await validarTokenSophia()
  if (!tokenValido) {
    // Reautentica e atualiza o token
    await autenticarSophia()
  }
  // Continue with the action normally...
}

// Verifica se o login expirou (24h)
function loginExpirado() {
  const ts = Number(localStorage.getItem('loginTimestamp'))
  if (!ts) return true
  const agora = Date.now()
  // 24 horas = 86400000 ms
  return (agora - ts) > 86400000
}
</script>

<style scoped>
.nome-aluno {
  font-size: 0.9em;
  color: #666;
  margin-top: 0.3em;
  font-weight: normal;
}
</style>

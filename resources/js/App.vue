<template>
  <div v-if="loading" class="loading-container">
    <p>Carregando...</p>
  </div>
  <div
    v-else
    :id="'container-geral'"
    :class="{'login-centralizado': !isLoggedIn, 'app-centralizado': isLoggedIn}"
  >
    <!-- Conteúdo principal -->
    <div style="margin-top: 6rem;">
      <template v-if="autenticado">
        <nav style="margin-bottom: 2em;">
          <template v-if="perfilLogado === 'Sem perfil'">
            <button @click="logout" class="logout-button">Sair</button>
          </template>
          <template v-else>
            <button
              :class="{ ativo: paginaAtual === 'inicio' }"
              @click="paginaAtual = 'inicio'"
            >
              Início
            </button>
            <button
              v-if="perfilLogado !== 'Aluno'"
              :class="{ ativo: paginaAtual === 'objetivos' }"
              @click="paginaAtual = 'objetivos'"
            >
              Objetivos
            </button>
            <button
              :class="{ ativo: paginaAtual === 'avaliacao' }"
              @click="paginaAtual = 'avaliacao'"
            >
              Avaliação
            </button>
            <button @click="logout" class="logout-button">Sair</button>
          </template>
        </nav>
        <div style="margin-bottom: 1em; font-weight: bold; color: #4f6c33;">
          Perfil logado: {{ perfilLogado }}
        </div>
        <div v-if="paginaAtual === 'inicio'">
          <div
            style="
              display: flex;
              flex-direction: column;
              align-items: center;
              margin-top: 2em;
            "
          >
            <img
              src="/public/images/logo_arandu.png"
              alt="Logo Arandu"
              style="height: 160px; padding: 1em; margin-bottom: 1em; border-radius: 50%; border: 1px solid #c0c0c0; background-color: #FFF;"
            />
            <h1>Bem-vindo ao sistema Arandu!</h1>
            <p>Escolha uma opção no menu acima.</p>
          </div>
        </div>
        <div v-else-if="paginaAtual === 'objetivos'">
          <ObjetivoComponent />
        </div>
        <div v-else-if="paginaAtual === 'avaliacao'">
          <AvaliacaoComponent />
        </div>
      </template>
      <template v-else>
        <LoginComponent @login-sucesso="onLoginSucesso" />
      </template>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import AlunoComponent from './components/AlunoComponent.vue'
import TurmaComponent from './components/TurmaComponent.vue'
import MateriaComponent from './components/MateriaComponent.vue'
import AvaliacaoComponent from './components/AvaliacaoComponent.vue'
import ObjetivoComponent from './components/ObjetivoComponent.vue'
import LoginComponent from './components/LoginComponent.vue'
import { useRouter } from 'vue-router'

// Estado da aplicação
const loading = ref(true)
const perfilLogado = ref('')
const router = useRouter()
const currentYear = new Date().getFullYear()

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
watch(paginaAtual, (novaPagina) => {
  localStorage.setItem('paginaAtual', novaPagina)
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
})

// Métodos
function logout() {
  // Apaga tokens e dados relacionados à sessão/integrações
  auth.logout()
  delete axios.defaults.headers.common['Authorization']
  delete axios.defaults.headers.common['Token']

  sessionStorage.removeItem('token')
  sessionStorage.removeItem('tokenSophia')
  sessionStorage.removeItem('email')
  sessionStorage.removeItem('perfil')
  sessionStorage.removeItem('aluno_id')
  sessionStorage.removeItem('turma_id')
  sessionStorage.removeItem('Professor')
  sessionStorage.removeItem('Professor_codigo')
  sessionStorage.removeItem('codigo')

  localStorage.removeItem('paginaAtual')

  // Recarrega para garantir que a UI volte ao estado não autenticado
  window.location.reload()
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
        sessionStorage.setItem('perfil', 'Professor')
        perfilLogado.value = 'Professor'
        sessionStorage.setItem('nome', col.nome || '')
        await buscarTurmasEMateriasDoProfessor()
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
  const codigoProfessor = sessionStorage.getItem('codigo') // deve ser "1092" para seu exemplo
  if (!tokenSophia || !codigoProfessor) return

  try {
    const url = 'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2'
    const resp = await axios.get(url, {
      headers: {
        Token: tokenSophia
      }
    })

    if (Array.isArray(resp.data)) {
      const codigosDisciplina = []
      resp.data.forEach(turma => {
        if (Array.isArray(turma.professoresDisciplinas)) {
          turma.professoresDisciplinas.forEach(pd => {
            // Verifica se o professor está como titular
            const isTitular = Array.isArray(pd.colaboradores) &&
              pd.colaboradores.some(colab => String(colab.codigo) === String(codigoProfessor))
            // Verifica se o professor está como auxiliar
            const isAuxiliar = Array.isArray(pd.colaboradoresAuxiliares) &&
              pd.colaboradoresAuxiliares.some(colab => String(colab.codigo) === String(codigoProfessor))
            if ((isTitular || isAuxiliar) && pd.disciplina && pd.disciplina.codigo !== undefined) {
              codigosDisciplina.push(pd.disciplina.codigo)
            }
          })
        }
      })
      // Remove duplicados
      const codigosUnicos = Array.from(new Set(codigosDisciplina))
      sessionStorage.setItem('codigo_disciplina', JSON.stringify(codigosUnicos))
    }
  } catch (e) {
    console.error('Erro ao buscar turmas/matérias:', e)
    sessionStorage.removeItem('codigo_disciplina')
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
          const codigosTurmas = alunoEncontrado.turmas.map(t => t.codigo)
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
  perfilLogado.value = sessionStorage.getItem('perfil') || 'Não definido'
  // autentica na Sophia e guarda tokenSophia
  await autenticarSophia()
}

// também tenta autenticar Sophia no carregamento, se houver token da app
onMounted(async () => {
  // Mude 'token' para 'savedToken' aqui
  const savedToken = sessionStorage.getItem('token')
  if (savedToken && !auth.token) {
    auth.setToken(savedToken)
  }
  // tenta autenticar Sophia e obter codigo de Professor se houver email
  if (auth.token) {
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
  
  loading.value = false
})
</script>
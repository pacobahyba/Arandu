import axios from 'axios'

const URL_TURMAS_SOPHIA =
  'https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Turmas?Cursos=20,7&Unidades=2'

/**
 * Todas as turmas da escola (Sophia + customizadas), sem filtro de matéria ou
 * professor. Usado pelas telas administrativas.
 *
 * As turmas do Sophia e as customizadas compartilham o mesmo espaço de ids
 * (`codigo` do Sophia vs autoincrement local), por isso cada item carrega
 * `origem` — 'S' ou 'C' — e a chave de lista precisa combinar os dois campos.
 */
export async function carregarTodasTurmas() {
  const turmas = []

  try {
    const resp = await axios.get(URL_TURMAS_SOPHIA, {
      headers: { Token: sessionStorage.getItem('tokenSophia') }
    })
    if (Array.isArray(resp.data)) {
      resp.data.forEach(t => {
        turmas.push({
          id_turma: t.codigo,
          nome_turma: t.nome || t.descricao || `Turma ${t.codigo}`,
          origem: 'S'
        })
      })
    }
  } catch (e) {
    // Sem Sophia, ainda listamos as customizadas.
  }

  try {
    const resp = await axios.get('/api/turmas-customizadas')
    if (Array.isArray(resp.data)) {
      resp.data.forEach(t => {
        const id = t.id ?? t.id_turma_customizada ?? t.id_turma
        if (id == null) return
        turmas.push({
          id_turma: id,
          nome_turma: `${t.nome_turma} (Customizada)`,
          origem: 'C'
        })
      })
    }
  } catch (e) {
    // ignora
  }

  return turmas.sort((a, b) => String(a.nome_turma).localeCompare(String(b.nome_turma)))
}

// Disciplinas que também têm turmas customizadas: Química(6), Inglês(11),
// Arte(22), Educação Musical(26), Teatro do Oprimido(93).
const DISCIPLINAS_COM_CUSTOMIZADAS = ['6', '11', '22', '26', '93']

/**
 * Matérias lecionadas pelo professor logado (códigos gravados no login em
 * sessionStorage 'codigo_disciplina'). Mesma lógica da tela Exportar.
 */
export async function carregarMateriasProfessor() {
  const codigos = JSON.parse(sessionStorage.getItem('codigo_disciplina') || '[]')
  if (!codigos.length) return []

  try {
    const resp = await axios.get(
      `https://portal.sophia.com.br/SophiAWebApi/5494/api/v1/Disciplinas?Codigos=${codigos.join(',')}`,
      { headers: { Token: sessionStorage.getItem('tokenSophia') } }
    )
    return Array.isArray(resp.data)
      ? resp.data.map(d => ({ id_materia: d.codigo, nome_materia: d.nome }))
      : []
  } catch {
    return []
  }
}

/**
 * Turmas em que o professor logado leciona a matéria: turmas do Sophia
 * filtradas pelo código do professor + turmas customizadas da disciplina.
 * Mesma lógica da tela Exportar; `isCustomizada` alimenta origemDaTurma().
 */
export async function carregarTurmasProfessor(idMateria) {
  if (!idMateria) return []

  const codigoProfessor = sessionStorage.getItem('codigo')
  let turmasSophia = []

  try {
    const resp = await axios.get(URL_TURMAS_SOPHIA, {
      headers: { Token: sessionStorage.getItem('tokenSophia') }
    })
    turmasSophia = (Array.isArray(resp.data) ? resp.data : [])
      .filter(turma =>
        Array.isArray(turma.professoresDisciplinas) &&
        turma.professoresDisciplinas.some(pd =>
          pd.disciplina &&
          pd.disciplina.codigo == idMateria &&
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
  if (DISCIPLINAS_COM_CUSTOMIZADAS.includes(String(idMateria))) {
    try {
      const resp = await axios.get('/api/turmas-customizadas', {
        params: { id_disciplina_mae: idMateria }
      })
      turmasCustomizadas = (Array.isArray(resp.data) ? resp.data : [])
        .filter(t => (t.id ?? t.id_turma_customizada) != null)
        .map(t => ({
          id_turma: t.id ?? t.id_turma_customizada,
          nome_turma: `${t.nome_turma} (Customizada)`,
          isCustomizada: true
        }))
    } catch {
      turmasCustomizadas = []
    }
  }

  return [...turmasSophia, ...turmasCustomizadas]
}

/** Chave estável para v-for, já que id_turma sozinho pode colidir entre origens. */
export function chaveTurma(turma) {
  return `${turma.origem}-${turma.id_turma}`
}

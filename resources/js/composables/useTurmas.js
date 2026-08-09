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

/** Chave estável para v-for, já que id_turma sozinho pode colidir entre origens. */
export function chaveTurma(turma) {
  return `${turma.origem}-${turma.id_turma}`
}

<?php

namespace Tests\Feature;

use App\Models\PeriodoAvaliacao;
use App\Models\RegimeAvaliacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegimeAvaliacaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Anos anteriores nunca tiveram regime cadastrado. Eles precisam continuar
     * trimestrais, senão todo o histórico some das telas.
     */
    public function test_ano_sem_configuracao_cai_em_trimestral(): void
    {
        $resp = $this->getJson('/api/regime-turma?ano=2025&id_turma=42&origem=S');

        $resp->assertOk()
            ->assertJson([
                'regime'      => 'TRI',
                'substantivo' => 'Trimestre',
                'periodos'    => ['1º Tri', '2º Tri', '3º Tri'],
            ]);
    }

    public function test_padrao_do_ano_vale_para_turma_sem_excecao(): void
    {
        $this->postJson('/api/regimes-avaliacao', ['ano' => 2026, 'regime' => 'BIM'])
            ->assertOk();

        $this->getJson('/api/regime-turma?ano=2026&id_turma=42&origem=S')
            ->assertOk()
            ->assertJson([
                'regime'   => 'BIM',
                'periodos' => ['1º Bim', '2º Bim', '3º Bim', '4º Bim'],
            ]);
    }

    public function test_excecao_da_turma_vence_o_padrao_do_ano(): void
    {
        $this->postJson('/api/regimes-avaliacao', ['ano' => 2026, 'regime' => 'BIM'])->assertOk();
        $this->postJson('/api/regimes-avaliacao', [
            'ano' => 2026, 'regime' => 'SEM', 'id_turma' => 7, 'origem' => 'S',
        ])->assertOk();

        // Turma com exceção
        $this->getJson('/api/regime-turma?ano=2026&id_turma=7&origem=S')
            ->assertJson(['regime' => 'SEM', 'periodos' => ['1º Sem', '2º Sem']]);

        // Turma sem exceção continua no padrão
        $this->getJson('/api/regime-turma?ano=2026&id_turma=42&origem=S')
            ->assertJson(['regime' => 'BIM']);
    }

    /**
     * turmas_customizadas.id_turma é um autoincrement pequeno que colide com o
     * `codigo` de turma do Sophia — os dois precisam ser resolvidos separadamente.
     */
    public function test_mesmo_id_em_origens_diferentes_nao_colide(): void
    {
        $this->postJson('/api/regimes-avaliacao', ['ano' => 2026, 'regime' => 'TRI'])->assertOk();
        $this->postJson('/api/regimes-avaliacao', [
            'ano' => 2026, 'regime' => 'BIM', 'id_turma' => 5, 'origem' => 'S',
        ])->assertOk();
        $this->postJson('/api/regimes-avaliacao', [
            'ano' => 2026, 'regime' => 'SEM', 'id_turma' => 5, 'origem' => 'C',
        ])->assertOk();

        $this->getJson('/api/regime-turma?ano=2026&id_turma=5&origem=S')
            ->assertJson(['regime' => 'BIM']);
        $this->getJson('/api/regime-turma?ano=2026&id_turma=5&origem=C')
            ->assertJson(['regime' => 'SEM']);
    }

    public function test_salvar_o_mesmo_ano_duas_vezes_atualiza_em_vez_de_duplicar(): void
    {
        $this->postJson('/api/regimes-avaliacao', ['ano' => 2026, 'regime' => 'BIM'])->assertOk();
        $this->postJson('/api/regimes-avaliacao', ['ano' => 2026, 'regime' => 'SEM'])->assertOk();

        $this->assertSame(1, RegimeAvaliacao::where('ano', 2026)->count());
        $this->assertSame('SEM', RegimeAvaliacao::padraoDoAno(2026)->value);
    }

    public function test_troca_de_regime_com_lancamentos_exige_confirmacao(): void
    {
        $idObjetivo = DB::table('objetivos')->insertGetId([
            'id_turma'         => 7,
            'id_materia'       => 3,
            'periodo_objetivo' => '1º Tri',
            'nome_objetivo'    => 'Objetivo antigo',
            'tipo_objetivo'    => 'C',
            'ano_objetivo'     => 2026,
        ], 'id_objetivo');

        DB::table('notas')->insert([
            'id_objetivo' => $idObjetivo,
            'id_aluno'    => 1,
            'tipo_nota'   => 'Prof',
            'nota'        => 8,
            'periodo'     => '1º Tri',
        ]);

        $this->postJson('/api/regimes-avaliacao', ['ano' => 2026, 'regime' => 'BIM'])
            ->assertStatus(409)
            ->assertJson(['conflito' => true, 'objetivos' => 1, 'notas' => 1]);

        $this->assertSame('TRI', RegimeAvaliacao::padraoDoAno(2026)->value);

        // Com confirmação explícita, a troca acontece.
        $this->postJson('/api/regimes-avaliacao', ['ano' => 2026, 'regime' => 'BIM', 'confirmar' => true])
            ->assertOk();

        $this->assertSame('BIM', RegimeAvaliacao::padraoDoAno(2026)->value);
    }

    public function test_regime_padrao_nao_pode_ser_removido(): void
    {
        $this->postJson('/api/regimes-avaliacao', ['ano' => 2026, 'regime' => 'BIM'])->assertOk();
        $padrao = RegimeAvaliacao::where('ano', 2026)->first();

        $this->deleteJson("/api/regimes-avaliacao/{$padrao->id_regime}")->assertStatus(422);
    }

    public function test_remover_excecao_devolve_turma_ao_padrao(): void
    {
        $this->postJson('/api/regimes-avaliacao', ['ano' => 2026, 'regime' => 'BIM'])->assertOk();
        $resp = $this->postJson('/api/regimes-avaliacao', [
            'ano' => 2026, 'regime' => 'SEM', 'id_turma' => 7, 'origem' => 'S',
        ])->assertOk();

        $this->deleteJson('/api/regimes-avaliacao/' . $resp->json('id_regime'))->assertOk();

        $this->getJson('/api/regime-turma?ano=2026&id_turma=7&origem=S')
            ->assertJson(['regime' => 'BIM']);
    }

    public function test_periodo_invalido_e_recusado(): void
    {
        $this->postJson('/api/periodos-avaliacao', [
            'ano'             => 2026,
            'periodo'         => '4º Tri', // não existe em nenhum regime
            'dt_primeiro_dia' => '2026-02-01',
        ])->assertStatus(422);
    }

    // ---------- Calendário ----------

    public function test_calendario_da_turma_vence_o_calendario_padrao(): void
    {
        $this->postJson('/api/periodos-avaliacao', [
            'ano' => 2026, 'periodo' => '1º Bim',
            'dt_primeiro_dia' => '2026-02-01', 'dt_ultimo_dia' => '2026-04-15',
        ])->assertOk();

        $this->postJson('/api/periodos-avaliacao', [
            'ano' => 2026, 'periodo' => '1º Bim', 'id_turma' => 7, 'origem' => 'S',
            'dt_primeiro_dia' => '2026-02-01', 'dt_ultimo_dia' => '2026-04-20',
        ])->assertOk();

        $this->getJson('/api/periodos-avaliacao?ano=2026&periodo=1º Bim&id_turma=7&origem=S')
            ->assertOk()
            ->assertJsonPath('dt_ultimo_dia', '2026-04-20');

        // Turma sem override cai no calendário padrão
        $this->getJson('/api/periodos-avaliacao?ano=2026&periodo=1º Bim&id_turma=42&origem=S')
            ->assertOk()
            ->assertJsonPath('dt_ultimo_dia', '2026-04-15');
    }

    public function test_calendario_inexistente_responde_200_sem_datas(): void
    {
        // O frontend só checa `resp.data.dt_ultimo_dia`; sem essa chave o
        // período é tratado como não expirado. Mantém o mesmo corpo que a
        // rota antiga devolvia, para não mudar comportamento.
        $this->getJson('/api/periodos-avaliacao?ano=2026&periodo=1º Bim')
            ->assertOk()
            ->assertJsonMissingPath('dt_ultimo_dia');
    }

    public function test_update_de_periodo_preserva_o_ano(): void
    {
        $periodo = PeriodoAvaliacao::create([
            'ano' => 2028, 'periodo' => '1º Sem',
            'dt_primeiro_dia' => '2028-02-01', 'dt_ultimo_dia' => '2028-06-30',
            'id_turma' => 0, 'origem_turma' => 'A',
        ]);

        $this->putJson("/api/periodos-avaliacao/{$periodo->id_periodo_avaliacao}", [
            'periodo' => '1º Sem',
            'dt_primeiro_dia' => '2028-02-05',
            'dt_ultimo_dia' => '2028-06-30',
        ])->assertOk();

        // Antes, o update sobrescrevia o ano com date('Y').
        $this->assertSame(2028, $periodo->fresh()->ano);
    }

    // ---------- Histórico ----------

    public function test_periodos_do_historico_vem_do_que_existe_no_banco(): void
    {
        DB::table('objetivos')->insert([
            ['id_turma' => 1, 'id_materia' => 3, 'periodo_objetivo' => '1º Tri', 'nome_objetivo' => 'A', 'tipo_objetivo' => 'C', 'ano_objetivo' => 2025],
            ['id_turma' => 1, 'id_materia' => 3, 'periodo_objetivo' => '2º Tri', 'nome_objetivo' => 'B', 'tipo_objetivo' => 'C', 'ano_objetivo' => 2025],
            ['id_turma' => 1, 'id_materia' => 3, 'periodo_objetivo' => '1º Bim', 'nome_objetivo' => 'C', 'tipo_objetivo' => 'C', 'ano_objetivo' => 2026],
        ]);

        $this->getJson('/api/periodos-objetivos?ano=2025')
            ->assertOk()
            ->assertExactJson(['1º Tri', '2º Tri']);
    }

    /** O parâmetro legado `trimestre` continua funcionando. */
    public function test_objetivos_aceitam_periodo_e_o_legado_trimestre(): void
    {
        DB::table('objetivos')->insert([
            'id_turma' => 1, 'id_materia' => 3, 'periodo_objetivo' => '1º Bim',
            'nome_objetivo' => 'Objetivo', 'tipo_objetivo' => 'C', 'ano_objetivo' => 2026,
        ]);

        $this->getJson('/api/objetivos?id_materia=3&turma_id=1&ano=2026&periodo=1º Bim')
            ->assertOk()->assertJsonCount(1);

        $this->getJson('/api/objetivos?id_materia=3&turma_id=1&ano=2026&trimestre=1º Bim')
            ->assertOk()->assertJsonCount(1);
    }
}

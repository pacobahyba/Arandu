<?php
// app/Http/Controllers/LiberacaoNotasController.php
namespace App\Http\Controllers;

use App\Enums\RegimePeriodo;
use App\Models\LiberacaoNotas;
use App\Models\RegimeAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Professor libera/tranca a visualização, pelos alunos, das notas que deu
 * (Prof, Rec, CF e nota final) por ano + matéria + turma + período.
 */
class LiberacaoNotasController extends Controller
{
    /** Status atual. Consumido pela tela do professor e pela Avaliação do aluno. */
    public function show(Request $request)
    {
        $dados = $request->validate($this->regras());

        return response()->json($this->status($dados));
    }

    public function update(Request $request)
    {
        $dados = $request->validate($this->regras() + [
            'liberado'         => 'required|boolean',
            'perfil'           => 'nullable|string',
            'codigo_professor' => 'nullable|integer',
        ]);

        // Mesmo padrão de POST /notas: o perfil vem da sessão do frontend.
        if (($dados['perfil'] ?? null) !== 'Professor') {
            return response()->json([
                'message' => 'Apenas professores podem liberar ou trancar notas.',
            ], 403);
        }

        LiberacaoNotas::updateOrCreate(
            [
                'ano'          => (int) $dados['ano'],
                'id_materia'   => (int) $dados['id_materia'],
                'id_turma'     => (int) $dados['id_turma'],
                'origem_turma' => RegimeAvaliacao::normalizarOrigem($dados['origem'] ?? null),
                'periodo'      => $dados['periodo'],
            ],
            [
                'liberado'         => $request->boolean('liberado'),
                'codigo_professor' => $dados['codigo_professor'] ?? null,
            ]
        );

        return response()->json($this->status($dados));
    }

    private function regras(): array
    {
        return [
            'ano'        => 'required|integer',
            'id_materia' => 'required|integer',
            'id_turma'   => 'required|integer',
            'origem'     => 'nullable|string',
            'periodo'    => ['required', Rule::in(RegimePeriodo::todosRotulos())],
        ];
    }

    private function status(array $dados): array
    {
        $ano = (int) $dados['ano'];

        $registro = LiberacaoNotas::buscar(
            $ano,
            (int) $dados['id_materia'],
            (int) $dados['id_turma'],
            $dados['origem'] ?? null,
            $dados['periodo']
        );

        return [
            'liberado'      => $registro ? $registro->liberado : LiberacaoNotas::padrao($ano),
            // true = o professor ainda não configurou este período (primeiro acesso)
            'padrao'        => $registro === null,
            'atualizado_em' => $registro?->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdenServicio;
use App\Models\Ruta;
use App\Models\Unidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RutaController extends Controller
{
    private const ESTATUS = ['pendiente', 'iniciada', 'pausada', 'finalizada'];

    private const TRANSICIONES_OPERADOR = [
        'pendiente' => ['iniciada'],
        'iniciada' => ['pausada', 'finalizada'],
        'pausada' => ['iniciada'],
        'finalizada' => [],
    ];

    public function listar(Request $request): JsonResponse
    {
        if (! $this->puedeVer($request)) {
            return $this->respuestaNoAutorizada();
        }

        $rutas = Ruta::with(['ordenServicio.cliente', 'ordenServicio.residuos', 'unidad', 'operador:id,name,email', 'ayudante:id,name,email'])
            ->when($request->user()->hasRole('operador'), fn ($query) => $query->where('operador_id', $request->user()->id))
            ->orderByDesc('fecha')
            ->get();

        return response()->json(['mensaje' => 'Rutas consultadas correctamente.', 'datos' => $rutas]);
    }

    public function registrar(Request $request): JsonResponse
    {
        if (! $this->puedeGestionar($request)) {
            return $this->respuestaNoAutorizada();
        }

        $datos = $request->validate($this->reglas());

        $error = $this->validarUnidadCompatibleConTipoServicio($datos['unidad_id'] ?? null, $datos['orden_servicio_id'] ?? null);
        if ($error) {
            return response()->json(['mensaje' => $error], 422);
        }

        $ruta = Ruta::create($datos);

        return response()->json(['mensaje' => 'Ruta registrada correctamente.', 'datos' => $ruta->load(['ordenServicio.cliente', 'ordenServicio.residuos', 'unidad', 'operador:id,name,email', 'ayudante:id,name,email'])], 201);
    }

    public function mostrar(Request $request, Ruta $ruta): JsonResponse
    {
        if (! $this->puedeVerRuta($request, $ruta)) {
            return $this->respuestaNoAutorizada();
        }

        return response()->json(['mensaje' => 'Ruta consultada correctamente.', 'datos' => $ruta->load(['ordenServicio.cliente', 'ordenServicio.residuos', 'unidad', 'operador:id,name,email', 'ayudante:id,name,email', 'incidencias'])]);
    }

    public function actualizar(Request $request, Ruta $ruta): JsonResponse
    {
        if ($request->user()->hasRole('operador')) {
            return $this->actualizarComoOperador($request, $ruta);
        }

        if (! $this->puedeGestionar($request)) {
            return $this->respuestaNoAutorizada();
        }

        $datos = $request->validate($this->reglas(true));

        $unidadId = array_key_exists('unidad_id', $datos) ? $datos['unidad_id'] : $ruta->unidad_id;
        $ordenServicioId = array_key_exists('orden_servicio_id', $datos) ? $datos['orden_servicio_id'] : $ruta->orden_servicio_id;

        $error = $this->validarUnidadCompatibleConTipoServicio($unidadId, $ordenServicioId);
        if ($error) {
            return response()->json(['mensaje' => $error], 422);
        }

        $ruta->update($datos);

        return response()->json(['mensaje' => 'Ruta actualizada correctamente.', 'datos' => $ruta->load(['ordenServicio.cliente', 'ordenServicio.residuos', 'unidad', 'operador:id,name,email', 'ayudante:id,name,email'])]);
    }

    public function eliminar(Request $request, Ruta $ruta): JsonResponse
    {
        if (! $request->user()->hasRole('director_general')) {
            return $this->respuestaNoAutorizada('Solo el director general puede eliminar rutas.');
        }

        $ruta->delete();

        return response()->json(['mensaje' => 'Ruta eliminada correctamente.']);
    }

    private function actualizarComoOperador(Request $request, Ruta $ruta): JsonResponse
    {
        if ($ruta->operador_id !== $request->user()->id) {
            return $this->respuestaNoAutorizada('Solo puedes actualizar rutas asignadas a tu usuario.');
        }

        $datos = $request->validate([
            'estatus' => ['required', Rule::in(self::ESTATUS)],
            'observaciones' => ['nullable', 'string'],
        ]);

        $transicionesValidas = self::TRANSICIONES_OPERADOR[$ruta->estatus] ?? [];

        if (! in_array($datos['estatus'], $transicionesValidas, true)) {
            return response()->json([
                'mensaje' => "No puedes cambiar el estatus de la ruta de '{$ruta->estatus}' a '{$datos['estatus']}'.",
            ], 422);
        }

        $ruta->update($datos);

        return response()->json([
            'mensaje' => 'Estatus de ruta actualizado correctamente.',
            'datos' => $ruta->load(['ordenServicio.cliente', 'ordenServicio.residuos', 'unidad', 'operador:id,name,email', 'ayudante:id,name,email']),
        ]);
    }

    private function reglas(bool $actualizar = false): array
    {
        $requerido = $actualizar ? 'sometimes' : 'required';

        return [
            'orden_servicio_id' => [$requerido, 'exists:ordenes_servicio,id'],
            'unidad_id' => ['nullable', 'exists:unidades,id'],
            'operador_id' => ['nullable', 'exists:users,id'],
            'ayudante_id' => ['nullable', 'exists:users,id'],
            'domicilio' => [$requerido, 'string', 'max:255'],
            'fecha' => ['nullable', 'date'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i'],
            'estatus' => ['sometimes', Rule::in(self::ESTATUS)],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    private function puedeVer(Request $request): bool
    {
        return $request->user()->hasAnyRole(['director_general', 'jefe_logistica', 'operador']);
    }

    private function puedeVerRuta(Request $request, Ruta $ruta): bool
    {
        if (! $this->puedeVer($request)) {
            return false;
        }

        return ! $request->user()->hasRole('operador') || $ruta->operador_id === $request->user()->id;
    }

    private function puedeGestionar(Request $request): bool
    {
        return $request->user()->hasAnyRole(['director_general', 'jefe_logistica']);
    }

    private function validarUnidadCompatibleConTipoServicio(?int $unidadId, ?int $ordenServicioId): ?string
    {
        if (! $unidadId) {
            return null;
        }

        $unidad = Unidad::find($unidadId);

        if (! $unidad || $unidad->tipo_servicio !== 'RPBI') {
            return null;
        }

        $tipoServicioOrden = $ordenServicioId ? OrdenServicio::find($ordenServicioId)?->tipo_servicio : null;

        if ($tipoServicioOrden !== 'RPBI') {
            return 'La unidad seleccionada es exclusiva para servicios de tipo RPBI.';
        }

        return null;
    }

    private function respuestaNoAutorizada(string $mensaje = 'No tienes permisos para realizar esta accion.'): JsonResponse
    {
        return response()->json(['mensaje' => $mensaje], 403);
    }
}

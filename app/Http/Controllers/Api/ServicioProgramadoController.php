<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdenServicio;
use App\Models\ServicioProgramado;
use App\Models\Unidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServicioProgramadoController extends Controller
{
    public function listar(Request $request): JsonResponse
    {
        if (! $this->puedeVer($request)) {
            return $this->respuestaNoAutorizada();
        }

        $servicios = ServicioProgramado::with(['ordenServicio', 'cliente', 'unidad', 'operador:id,name,email'])
            ->when($request->user()->hasRole('operador'), fn ($query) => $query->where('operador_id', $request->user()->id))
            ->when($request->query('fecha'), fn ($query, $fecha) => $query->whereDate('fecha', $fecha))
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get();

        return response()->json(['mensaje' => 'Servicios programados consultados correctamente.', 'datos' => $servicios]);
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

        $servicioProgramado = ServicioProgramado::create($datos);

        return response()->json(['mensaje' => 'Servicio programado registrado correctamente.', 'datos' => $servicioProgramado->load(['ordenServicio', 'cliente', 'unidad', 'operador:id,name,email'])], 201);
    }

    public function mostrar(Request $request, ServicioProgramado $servicioProgramado): JsonResponse
    {
        if (! $this->puedeVerServicio($request, $servicioProgramado)) {
            return $this->respuestaNoAutorizada();
        }

        return response()->json(['mensaje' => 'Servicio programado consultado correctamente.', 'datos' => $servicioProgramado->load(['ordenServicio', 'cliente', 'unidad', 'operador:id,name,email'])]);
    }

    public function actualizar(Request $request, ServicioProgramado $servicioProgramado): JsonResponse
    {
        if ($request->user()->hasRole('operador')) {
            return $this->actualizarComoOperador($request, $servicioProgramado);
        }

        if (! $this->puedeGestionar($request)) {
            return $this->respuestaNoAutorizada();
        }

        $datos = $request->validate($this->reglas(true));

        $unidadId = array_key_exists('unidad_id', $datos) ? $datos['unidad_id'] : $servicioProgramado->unidad_id;
        $ordenServicioId = array_key_exists('orden_servicio_id', $datos) ? $datos['orden_servicio_id'] : $servicioProgramado->orden_servicio_id;

        $error = $this->validarUnidadCompatibleConTipoServicio($unidadId, $ordenServicioId);
        if ($error) {
            return response()->json(['mensaje' => $error], 422);
        }

        $servicioProgramado->update($datos);

        return response()->json(['mensaje' => 'Servicio programado actualizado correctamente.', 'datos' => $servicioProgramado->load(['ordenServicio', 'cliente', 'unidad', 'operador:id,name,email'])]);
    }

    public function eliminar(Request $request, ServicioProgramado $servicioProgramado): JsonResponse
    {
        if (! $request->user()->hasRole('director_general')) {
            return $this->respuestaNoAutorizada('Solo el director general puede eliminar servicios programados.');
        }

        $servicioProgramado->delete();

        return response()->json(['mensaje' => 'Servicio programado eliminado correctamente.']);
    }

    private function actualizarComoOperador(Request $request, ServicioProgramado $servicioProgramado): JsonResponse
    {
        if ($servicioProgramado->operador_id !== $request->user()->id) {
            return $this->respuestaNoAutorizada('Solo puedes actualizar servicios asignados a tu usuario.');
        }

        $datos = $request->validate([
            'estatus' => ['required', Rule::in(['completado', 'cancelado'])],
            'observaciones' => ['nullable', 'string'],
        ]);

        $datos['color_calendario'] = $datos['estatus'] === 'completado' ? 'verde' : 'rojo';
        $servicioProgramado->update($datos);

        return response()->json(['mensaje' => 'Estatus del calendario actualizado correctamente.', 'datos' => $servicioProgramado]);
    }

    private function reglas(bool $actualizar = false): array
    {
        $requerido = $actualizar ? 'sometimes' : 'required';

        return [
            'orden_servicio_id' => ['nullable', 'exists:ordenes_servicio,id'],
            'cliente_id' => [$requerido, 'exists:clientes,id'],
            'unidad_id' => ['nullable', 'exists:unidades,id'],
            'operador_id' => ['nullable', 'exists:users,id'],
            'fecha' => [$requerido, 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'estatus' => ['sometimes', Rule::in(['pendiente', 'asignado', 'en_ruta', 'en_proceso', 'completado', 'cancelado'])],
            'color_calendario' => ['sometimes', Rule::in(['amarillo', 'verde', 'rojo'])],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    private function puedeVer(Request $request): bool
    {
        return $request->user()->hasAnyRole(['director_general', 'jefe_logistica', 'ventas', 'operador']);
    }

    private function puedeVerServicio(Request $request, ServicioProgramado $servicioProgramado): bool
    {
        if (! $this->puedeVer($request)) {
            return false;
        }

        return ! $request->user()->hasRole('operador') || $servicioProgramado->operador_id === $request->user()->id;
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

        if (! $ordenServicioId) {
            return null;
        }

        $tipoServicioOrden = OrdenServicio::find($ordenServicioId)?->tipo_servicio;

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

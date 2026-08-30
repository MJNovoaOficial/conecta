<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Suma horas de trabajo, no horas de reloj.
 *
 * Los plazos de SLA se contaban con addHours(), así que corrían de noche, los
 * fines de semana y en feriados. Un ticket de prioridad media creado un viernes
 * a las 17:00 vencía el sábado a la 1 de la madrugada: nacía incumplido sin que
 * nadie pudiera hacer nada, y ese incumplimiento contaminaba los reportes.
 *
 * Esta clase avanza únicamente dentro del horario configurado en config/sla.php.
 */
class HorarioLaboral
{
    /**
     * Tope de días que se recorren buscando el siguiente hábil.
     *
     * Existe solo para que una configuración inválida —por ejemplo, dejar la
     * lista de días vacía— falle con un error claro en vez de colgar el proceso.
     */
    private const MAX_DIAS = 400;

    /**
     * Devuelve el instante en que se cumplen $horas de trabajo desde $desde.
     *
     * Si el horario laboral está desactivado, o la prioridad está marcada como
     * de atención continua, se comporta como el addHours() de siempre.
     */
    public static function sumarHoras(CarbonInterface $desde, float $horas, ?string $prioridad = null): Carbon
    {
        $minutos = (int) round($horas * 60);

        if (! self::aplicaHorario($prioridad)) {
            return Carbon::instance($desde->toDateTime())->addMinutes($minutos);
        }

        if ($minutos <= 0) {
            return self::proximoInstanteHabil(Carbon::instance($desde->toDateTime()));
        }

        $cursor = self::proximoInstanteHabil(Carbon::instance($desde->toDateTime()));

        for ($vuelta = 0; $vuelta < self::MAX_DIAS; $vuelta++) {
            $finJornada = self::finDeJornada($cursor);
            $disponibles = $cursor->diffInMinutes($finJornada, false);

            if ($disponibles >= $minutos) {
                return $cursor->copy()->addMinutes($minutos);
            }

            $minutos -= $disponibles;
            $cursor = self::proximoInstanteHabil($finJornada->copy()->addMinute());
        }

        throw new \RuntimeException(
            'No se pudo calcular el plazo de SLA: revisa el horario laboral en config/sla.php.'
        );
    }

    /**
     * Minutos de trabajo entre dos instantes. Sirve para medir cuánto se demoró
     * realmente la atención, descontando noches y fines de semana.
     */
    public static function minutosEntre(CarbonInterface $desde, CarbonInterface $hasta): int
    {
        $inicio = Carbon::instance($desde->toDateTime());
        $fin    = Carbon::instance($hasta->toDateTime());

        if ($fin->lte($inicio)) {
            return 0;
        }

        if (! self::aplicaHorario(null)) {
            return (int) $inicio->diffInMinutes($fin);
        }

        $total  = 0;
        $cursor = $inicio;

        for ($vuelta = 0; $vuelta < self::MAX_DIAS; $vuelta++) {
            if ($cursor->gte($fin)) {
                return $total;
            }

            if (self::esDiaHabil($cursor)) {
                $desdeHabil = $cursor->copy()->max(self::inicioDeJornada($cursor));
                $hastaHabil = $fin->copy()->min(self::finDeJornada($cursor));

                if ($hastaHabil->gt($desdeHabil)) {
                    $total += (int) $desdeHabil->diffInMinutes($hastaHabil);
                }
            }

            $cursor = $cursor->copy()->addDay()->setTimeFromTimeString(self::config('inicio'));
        }

        return $total;
    }

    // ── Interno ───────────────────────────────────────────────────────

    private static function aplicaHorario(?string $prioridad): bool
    {
        if (! config('sla.horario_laboral.activo', true)) {
            return false;
        }

        return ! in_array($prioridad, config('sla.prioridades_24_7', []), true);
    }

    private static function config(string $clave)
    {
        return config('sla.horario_laboral.' . $clave);
    }

    private static function esDiaHabil(Carbon $momento): bool
    {
        if (! in_array($momento->dayOfWeekIso, self::config('dias') ?: [], true)) {
            return false;
        }

        return ! in_array($momento->toDateString(), self::config('feriados') ?: [], true);
    }

    private static function inicioDeJornada(Carbon $momento): Carbon
    {
        return $momento->copy()->setTimeFromTimeString(self::config('inicio'));
    }

    private static function finDeJornada(Carbon $momento): Carbon
    {
        return $momento->copy()->setTimeFromTimeString(self::config('fin'));
    }

    /**
     * El primer instante hábil desde $momento, que puede ser él mismo.
     *
     * Un ticket creado un sábado, o a las 22:00 de un martes, empieza a contar
     * su plazo cuando abre la mesa de ayuda: antes de eso no hay nadie que
     * pueda atenderlo.
     */
    private static function proximoInstanteHabil(Carbon $momento): Carbon
    {
        $cursor = $momento->copy();

        for ($vuelta = 0; $vuelta < self::MAX_DIAS; $vuelta++) {
            if (self::esDiaHabil($cursor)) {
                $inicio = self::inicioDeJornada($cursor);
                $fin    = self::finDeJornada($cursor);

                if ($cursor->lt($inicio)) {
                    return $inicio;
                }

                if ($cursor->lt($fin)) {
                    return $cursor;
                }
            }

            $cursor = $cursor->copy()->addDay()->setTimeFromTimeString(self::config('inicio'));
        }

        throw new \RuntimeException(
            'No se encontró un día hábil: revisa el horario laboral en config/sla.php.'
        );
    }
}

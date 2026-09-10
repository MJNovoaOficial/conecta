<?php

namespace Tests\Unit;

use App\Support\HorarioLaboral;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Pruebas del cálculo de plazos de SLA.
 *
 * Esta clase decide cuándo vence cada ticket, así que un error acá no se ve:
 * simplemente los plazos quedan mal y los reportes de cumplimiento mienten.
 * Por eso se prueban los dos modos, el de horas corridas —que es el que corre
 * hoy, con turnos 24/7— y el de horario de oficina, que quedó disponible por
 * si la empresa vuelve atrás.
 */
class HorarioLaboralTest extends TestCase
{
    /** Deja la configuración en horario de oficina de lunes a viernes. */
    private function horarioDeOficina(array $extra = []): void
    {
        config(array_merge([
            'sla.horario_laboral.activo'   => true,
            'sla.horario_laboral.dias'     => [1, 2, 3, 4, 5],
            'sla.horario_laboral.inicio'   => '08:30',
            'sla.horario_laboral.fin'      => '18:30',
            'sla.horario_laboral.feriados' => [],
            'sla.prioridades_24_7'         => [],
        ], $extra));
    }

    /** Deja la configuración como corre hoy: turnos los siete días. */
    private function turnos247(): void
    {
        config(['sla.horario_laboral.activo' => false]);
    }

    // ── Modo turnos 24/7, que es el que corre en producción ───────────

    public function test_con_turnos_24_7_los_plazos_corren_en_horas_de_reloj(): void
    {
        $this->turnos247();

        // Sábado a las 2 de la madrugada, ticket crítico: hay gente de turno,
        // así que el plazo corre en ese momento y no espera al lunes.
        $vence = HorarioLaboral::sumarHoras(Carbon::parse('2026-09-12 02:00'), 1);

        $this->assertSame('2026-09-12 03:00', $vence->format('Y-m-d H:i'));
    }

    public function test_con_turnos_24_7_el_plazo_cruza_la_medianoche(): void
    {
        $this->turnos247();

        $vence = HorarioLaboral::sumarHoras(Carbon::parse('2026-09-11 22:00'), 6);

        $this->assertSame('2026-09-12 04:00', $vence->format('Y-m-d H:i'));
    }

    public function test_con_turnos_24_7_los_minutos_transcurridos_son_los_del_reloj(): void
    {
        $this->turnos247();

        $minutos = HorarioLaboral::minutosEntre(
            Carbon::parse('2026-09-11 17:00'),
            Carbon::parse('2026-09-14 09:00'),   // viernes tarde a lunes mañana
        );

        $this->assertSame(64 * 60, $minutos);
    }

    // ── Modo horario de oficina, por si vuelven atrás ─────────────────

    public function test_en_horario_de_oficina_el_plazo_salta_el_fin_de_semana(): void
    {
        $this->horarioDeOficina();

        // Viernes 17:00 + 8 horas hábiles. Quedan 1.5 h del viernes, así que
        // las 6.5 restantes caen el lunes desde las 08:30.
        $vence = HorarioLaboral::sumarHoras(Carbon::parse('2026-09-11 17:00'), 8);

        $this->assertSame('2026-09-14 15:00', $vence->format('Y-m-d H:i'));
    }

    public function test_en_horario_de_oficina_un_plazo_dentro_de_la_jornada_no_se_mueve(): void
    {
        $this->horarioDeOficina();

        $vence = HorarioLaboral::sumarHoras(Carbon::parse('2026-09-09 10:00'), 4);

        $this->assertSame('2026-09-09 14:00', $vence->format('Y-m-d H:i'));
    }

    public function test_un_ticket_creado_de_madrugada_empieza_a_contar_al_abrir(): void
    {
        $this->horarioDeOficina();

        // Miércoles 03:00: nadie puede atenderlo hasta las 08:30, así que el
        // plazo de 2 horas vence a las 10:30 y no a las 05:00.
        $vence = HorarioLaboral::sumarHoras(Carbon::parse('2026-09-09 03:00'), 2);

        $this->assertSame('2026-09-09 10:30', $vence->format('Y-m-d H:i'));
    }

    public function test_un_feriado_se_trata_como_un_dia_no_habil(): void
    {
        $this->horarioDeOficina(['sla.horario_laboral.feriados' => ['2026-09-18']]);

        // Jueves 17 a las 18:00 con el viernes 18 feriado. Alcanzan a correr
        // 30 minutos del jueves antes de que cierre la jornada; la hora y media
        // que queda se cuenta el lunes 21 desde las 08:30.
        $vence = HorarioLaboral::sumarHoras(Carbon::parse('2026-09-17 18:00'), 2);

        $this->assertSame('2026-09-21 10:00', $vence->format('Y-m-d H:i'));
    }

    public function test_una_prioridad_marcada_24_7_ignora_el_horario(): void
    {
        $this->horarioDeOficina(['sla.prioridades_24_7' => ['critical']]);

        $desde = Carbon::parse('2026-09-12 02:00');   // sábado de madrugada

        $this->assertSame(
            '2026-09-12 03:00',
            HorarioLaboral::sumarHoras($desde, 1, 'critical')->format('Y-m-d H:i'),
            'una prioridad de atención continua no debe esperar al lunes'
        );

        $this->assertSame(
            '2026-09-14 09:30',
            HorarioLaboral::sumarHoras($desde, 1, 'high')->format('Y-m-d H:i'),
            'el resto de las prioridades sí debe respetar el horario'
        );
    }

    public function test_en_horario_de_oficina_no_se_cuentan_las_noches_ni_el_fin_de_semana(): void
    {
        $this->horarioDeOficina();

        // Viernes 17:00 a lunes 09:00: hora y media del viernes más media hora
        // del lunes. El fin de semana entero no cuenta.
        $minutos = HorarioLaboral::minutosEntre(
            Carbon::parse('2026-09-11 17:00'),
            Carbon::parse('2026-09-14 09:00'),
        );

        $this->assertSame(120, $minutos);
    }

    // ── Bordes ────────────────────────────────────────────────────────

    public function test_un_plazo_que_ya_paso_devuelve_cero_minutos(): void
    {
        $this->turnos247();

        $minutos = HorarioLaboral::minutosEntre(
            Carbon::parse('2026-09-11 17:00'),
            Carbon::parse('2026-09-11 15:00'),   // antes que el inicio
        );

        $this->assertSame(0, $minutos);
    }

    public function test_una_configuracion_sin_dias_habiles_falla_con_un_mensaje_claro(): void
    {
        // Dejar la lista de días vacía haría que el cálculo buscara para
        // siempre. Debe cortar y decir dónde está el problema, no colgarse.
        $this->horarioDeOficina(['sla.horario_laboral.dias' => []]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/horario laboral/');

        HorarioLaboral::sumarHoras(Carbon::parse('2026-09-09 10:00'), 2);
    }
}

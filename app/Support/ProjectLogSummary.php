<?php

namespace App\Support;

use App\Models\ProjectLogEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class ProjectLogSummary
{
    public static function current(): array
    {
        $today = CarbonImmutable::now(DisplayDate::timezone());
        $month = [$today->startOfMonth()->toDateString(), $today->endOfMonth()->toDateString()];
        $year = [$today->startOfYear()->toDateString(), $today->endOfYear()->toDateString()];
        $monthly = ProjectLogEntry::whereBetween('performed_on', $month);
        $projects = (clone $monthly)->join('project_log_projects as projects', 'projects.id', '=', 'project_log_entries.project_id')
            ->select('project_id', 'projects.name')
            ->selectRaw('COUNT(*) as updates')
            ->groupBy('project_id', 'projects.name')->orderByDesc('updates')->orderBy('projects.name')->limit(5)->get();

        return [
            'month_label' => $today->locale('es')->isoFormat('MMMM YYYY'), 'year_label' => $today->format('Y'),
            'month_range' => ['date_from' => $month[0], 'date_to' => $month[1]],
            'year_range' => ['date_from' => $year[0], 'date_to' => $year[1]],
            'month' => self::totals($monthly),
            'year' => self::totals(ProjectLogEntry::whereBetween('performed_on', $year)),
            'projects' => $projects,
        ];
    }

    private static function totals(Builder $query): array
    {
        $totals = (clone $query)->selectRaw('COUNT(*) as updates, COUNT(DISTINCT project_id) as projects');
        foreach (array_keys(ProjectLogEntry::TYPES) as $type) {
            $totals->selectRaw("COALESCE(SUM(CASE WHEN type = ? THEN 1 ELSE 0 END), 0) as {$type}", [$type]);
        }
        return array_map('intval', $totals->first()->getAttributes());
    }
}

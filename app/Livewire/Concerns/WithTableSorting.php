<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * Clickable column sorting for admin list tables.
 *
 * The component must also use Livewire\WithPagination, and must declare which
 * columns may be sorted via sortableColumns(). Anything not in that map is
 * ignored, so a crafted request cannot reach orderBy().
 */
trait WithTableSorting
{
    #[Url(except: '')]
    public string $sortField = '';

    #[Url(except: 'asc')]
    public string $sortDirection = 'asc';

    /**
     * Public sort key => database column (or aliased select, e.g. a withCount).
     *
     * @return array<string, string>
     */
    abstract protected function sortableColumns(): array;

    public function sortBy(string $field): void
    {
        if (! array_key_exists($field, $this->sortableColumns())) {
            return;
        }

        $this->sortDirection = $this->sortField === $field
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';

        $this->sortField = $field;

        // Sorting reorders the whole result set, so the current page number
        // is meaningless afterwards.
        $this->resetPage();
    }

    /**
     * Joins needed only when the active sort targets another table.
     *
     * Override where a sortable column does not live on the base table. It is
     * called only when a sort is actually active, so an unsorted list pays
     * nothing for it.
     */
    protected function applySortJoins(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Apply the active sort, falling back to the list's natural order.
     */
    protected function applySorting(Builder $query, string $fallback, string $fallbackDirection = 'desc'): Builder
    {
        $column = $this->sortableColumns()[$this->sortField] ?? null;

        if ($column === null) {
            return $query->orderBy($fallback, $fallbackDirection);
        }

        return $this->applySortJoins($query)
            ->orderBy($column, $this->sortDirection === 'asc' ? 'asc' : 'desc');
    }
}

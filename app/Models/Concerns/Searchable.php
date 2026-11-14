<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Case-insensitive "contains" search across the given columns. LIKE wildcards in the term are escaped
     * so users cannot craft expensive patterns.
     *
     * @param  Builder<static>  $query
     * @param  array<int, string>  $columns
     * @return Builder<static>
     */
    public function scopeSearch(Builder $query, ?string $term, array $columns = ['title']): Builder
    {
        if (! filled($term)) {
            return $query;
        }

        $like = self::containsPattern($term);
        $grammar = $query->getQuery()->getGrammar();

        return $query->where(function (Builder $q) use ($columns, $like, $grammar): void {
            foreach ($columns as $column) {
                $q->orWhereRaw($grammar->wrap($column)." like ? escape '!'", [$like]);
            }
        });
    }

    /**
     * Builds a LIKE pattern matching `$term` anywhere. "!" is the escape character (declared with
     * `ESCAPE '!'`), which behaves identically on MySQL, SQLite and PostgreSQL, unlike backslashes.
     */
    public static function containsPattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($term)).'%';
    }
}

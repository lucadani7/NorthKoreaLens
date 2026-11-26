<?php

namespace App\Http\Controllers;

use App\Data\CategoryData;
use App\Data\PaginationData;
use App\Data\PostData;
use App\Enums\SourceType;
use App\Models\Category;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public const PER_PAGE = 20;

    public function __invoke(Request $request): Response
    {
        $sourceTypeValues = array_map(
            fn (SourceType $type): string => $type->value,
            SourceType::cases(),
        );
        /** @var array<string, string> $supportedLocales */
        $supportedLocales = config('locales.supported');
        $supportedLanguages = array_keys($supportedLocales);
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'source_type' => ['nullable', Rule::in($sourceTypeValues)],
            'period' => ['nullable', Rule::in(['7d', '30d', '90d', '365d', 'custom'])],
            'from' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
            'language' => ['nullable', Rule::in($supportedLanguages)],
        ]);
        $query = trim($validated['q'] ?? '');
        $filters = [
            'category' => isset($validated['category']) ? (string) $validated['category'] : '',
            'source_type' => $validated['source_type'] ?? '',
            'period' => $validated['period'] ?? '',
            'from' => $validated['from'] ?? '',
            'to' => $validated['to'] ?? '',
            'language' => $validated['language'] ?? '',
        ];
        $dateRange = $this->dateRange($filters);
        $hasFilters = $filters['category'] !== ''
            || $filters['source_type'] !== ''
            || $filters['language'] !== ''
            || $dateRange !== null;
        $locale = app()->getLocale();
        $relations = [
            'source',
            'category',
            'topics',
            'translations' => fn (Relation $relation) => $relation->where('locale', $locale),
        ];

        if ($query === '' && ! $hasFilters) {
            $posts = Post::query()
                ->whereKey([])
                ->with($relations)
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        } elseif ($query === '') {
            $posts = $this->filteredQuery($filters, $dateRange)
                ->with($relations)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        } else {
            $search = Post::search($query)
                ->query(function (Builder $builder) use ($filters, $dateRange, $relations): void {
                    $builder->whereNotNull('published_at')
                        ->where('published_at', '<=', now())
                        ->with($relations);

                    $this->applyFilters($builder, $filters, $dateRange);
                });

            if (config('scout.driver') === 'meilisearch') {
                if ($filters['category'] !== '') {
                    $search->where('category_id', (int) $filters['category']);
                }

                if ($filters['source_type'] !== '') {
                    $search->where('source_type', $filters['source_type']);
                }

                if ($filters['language'] !== '') {
                    $search->where('available_languages', $filters['language']);
                }

                if ($dateRange !== null) {
                    if ($dateRange['from'] !== null) {
                        $search->where('search_date', '>=', $dateRange['from']->getTimestamp());
                    }

                    if ($dateRange['to'] !== null) {
                        $search->where('search_date', '<=', $dateRange['to']->getTimestamp());
                    }
                }
            }

            $posts = $search->paginate(self::PER_PAGE)->withQueryString();
        }

        $sourceTypes = [
            ['value' => SourceType::Independent->value, 'label' => __('search.source_types.independent')],
            ['value' => SourceType::Analysis->value, 'label' => __('search.source_types.analysis')],
            ['value' => SourceType::Testimony->value, 'label' => __('search.source_types.testimony')],
            ['value' => SourceType::TravelVlog->value, 'label' => __('search.source_types.travel_vlog')],
            ['value' => SourceType::StateMedia->value, 'label' => __('search.source_types.state_media')],
        ];
        $languages = collect($supportedLocales)
            ->map(fn (string $name, string $code): array => ['code' => $code, 'name' => $name])
            ->values()
            ->all();

        return Inertia::render('search', [
            'query' => $query,
            'filters' => $filters,
            'filterLabels' => [
                'title' => __('search.filters'),
                'category' => __('search.category'),
                'allCategories' => __('search.all_categories'),
                'sourceType' => __('search.source_type'),
                'allSourceTypes' => __('search.all_source_types'),
                'period' => __('search.period'),
                'anyTime' => __('search.any_time'),
                'last7Days' => __('search.last_7_days'),
                'last30Days' => __('search.last_30_days'),
                'last90Days' => __('search.last_90_days'),
                'lastYear' => __('search.last_year'),
                'customRange' => __('search.custom_range'),
                'from' => __('search.from'),
                'to' => __('search.to'),
                'language' => __('search.language'),
                'allLanguages' => __('search.all_languages'),
                'clear' => __('search.clear_filters'),
                'matchingResults' => __('search.matching_results'),
                'noMatchingResults' => __('search.no_matching_results'),
            ],
            'categories' => CategoryData::collect(Category::orderBy('sort_order')->get()),
            'sourceTypes' => $sourceTypes,
            'languages' => $languages,
            'posts' => PostData::collect($posts->items()),
            'pagination' => PaginationData::fromPaginator($posts),
            'now' => now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array{category: int|string, source_type: string, period: string, from: string, to: string, language: string}  $filters
     * @return array{from: CarbonImmutable|null, to: CarbonImmutable|null}|null
     */
    private function dateRange(array $filters): ?array
    {
        $now = CarbonImmutable::now();

        return match ($filters['period']) {
            '7d' => ['from' => $now->subDays(7), 'to' => $now],
            '30d' => ['from' => $now->subDays(30), 'to' => $now],
            '90d' => ['from' => $now->subDays(90), 'to' => $now],
            '365d' => ['from' => $now->subDays(365), 'to' => $now],
            'custom' => [
                'from' => CarbonImmutable::parse($filters['from'])->startOfDay(),
                'to' => CarbonImmutable::parse($filters['to'])->endOfDay(),
            ],
            default => null,
        };
    }

    /**
     * @param  array{category: int|string, source_type: string, period: string, from: string, to: string, language: string}  $filters
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null}|null  $dateRange
     * @return Builder<Post>
     */
    private function filteredQuery(array $filters, ?array $dateRange): Builder
    {
        $query = Post::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        $this->applyFilters($query, $filters, $dateRange);

        return $query;
    }

    /**
     * @param  array{category: int|string, source_type: string, period: string, from: string, to: string, language: string}  $filters
     * @param  array{from: CarbonImmutable|null, to: CarbonImmutable|null}|null  $dateRange
     * @param  Builder<Post>  $query
     */
    private function applyFilters(Builder $query, array $filters, ?array $dateRange): void
    {
        if ($filters['category'] !== '') {
            $query->where('category_id', (int) $filters['category']);
        }

        if ($filters['source_type'] !== '') {
            $query->whereHas('source', fn (Builder $source) => $source->where('type', $filters['source_type']));
        }

        if ($filters['language'] !== '') {
            $query->where(function (Builder $languageQuery) use ($filters): void {
                $languageQuery->where('language', $filters['language'])
                    ->orWhereHas('translations', fn (Builder $translation) => $translation->where('locale', $filters['language']));
            });
        }

        if ($dateRange !== null) {
            $publicationDate = 'CASE WHEN posts.source_id IS NULL THEN posts.published_at ELSE COALESCE(posts.source_published_at, posts.published_at) END';

            if ($dateRange['from'] !== null) {
                $query->whereRaw($publicationDate.' >= ?', [$dateRange['from']->toDateTimeString()]);
            }

            if ($dateRange['to'] !== null) {
                $query->whereRaw($publicationDate.' <= ?', [$dateRange['to']->toDateTimeString()]);
            }
        }
    }
}

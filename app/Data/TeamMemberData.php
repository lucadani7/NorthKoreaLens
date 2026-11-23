<?php

namespace App\Data;

use Illuminate\Support\Str;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use Spatie\LaravelData\Data;

/** A person on the Team page (config/team.php), in the reader's language. */
class TeamMemberData extends Data
{
    public function __construct(
        public string $key,
        public string $name,
        public ?string $country,
        public ?string $profession,
        public ?string $roles,
        /** Rendered from Markdown, so it can hold links (raw HTML is stripped). */
        public ?string $notesHtml,
    ) {}

    /** @return list<self> */
    public static function fromConfig(): array
    {
        /** @var array<string, array{name: string}> $members */
        $members = config('team.members', []);
        $list = [];

        foreach ($members as $key => $member) {
            $list[] = new self(
                key: $key,
                name: $member['name'],
                country: self::field($key, 'country'),
                profession: self::field($key, 'profession'),
                roles: self::field($key, 'roles'),
                notesHtml: self::html(self::field($key, 'notes')),
            );
        }

        return $list;
    }

    private static function html(?string $markdown): ?string
    {
        if ($markdown === null) {
            return null;
        }

        return trim(Str::inlineMarkdown($markdown, PageData::markdownOptions(), [new ExternalLinkExtension]));
    }

    /** One translated field, or null when it is not written in any language. */
    private static function field(string $key, string $field): ?string
    {
        $text = __("team.{$key}.{$field}");

        return is_string($text) && $text !== "team.{$key}.{$field}" ? $text : null;
    }
}

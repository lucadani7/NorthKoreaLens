declare namespace App {
namespace Data {
export type CategoryData = {
id: number,
slug: string,
name: string,
isPropaganda: boolean,
};
export type PageData = {
slug: string,
title: string,
description: string | null,
html: string,
locale: string,
};
export type PaginationData = {
currentPage: number,
lastPage: number,
total: number,
previousPageUrl: string | null,
nextPageUrl: string | null,
};
export type PostData = {
id: number,
title: string,
excerpt: string | null,
originalUrl: string | null,
imageUrl: string | null,
publishedAt: string | null,
isPropaganda: boolean,
editorialNote: string | null,
hasBody: boolean,
linkWithheld: boolean,
youtubeId: string | null,
category: App.Data.CategoryData,
topics: App.Data.TopicData[],
contentLocale: string,
source: App.Data.SourceData | null,
};
export type SourceData = {
id: number,
name: string,
slug: string,
type: App.Enums.SourceType,
websiteUrl: string | null,
forbidsAi: boolean,
notice: string | null,
};
export type SourceListingData = {
id: number,
name: string,
type: App.Enums.SourceType,
websiteUrl: string | null,
};
export type TopicData = {
id: number,
slug: string,
name: string,
};
}
namespace Enums {
export type SourceType = 'independent' | 'analysis' | 'testimony' | 'state_media';
}
}
declare namespace Illuminate {
export type CursorPaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
path: string,
per_page: number,
next_cursor: string | null,
next_page_url: string | null,
prev_cursor: string | null,
prev_page_url: string | null,
},
};
export type CursorPaginatorInterface<TKey, TValue> = Illuminate.CursorPaginator<TKey, TValue>;
export type LengthAwarePaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
total: number,
current_page: number,
first_page_url: string,
from: number | null,
last_page: number,
last_page_url: string,
next_page_url: string | null,
path: string,
per_page: number,
prev_page_url: string | null,
to: number | null,
},
};
export type LengthAwarePaginatorInterface<TKey, TValue> = Illuminate.LengthAwarePaginator<TKey, TValue>;
}
declare namespace Spatie {
namespace LaravelData {
export type CursorPaginatedDataCollection<TKey, TValue> = Illuminate.CursorPaginator<TKey, TValue>;
export type PaginatedDataCollection<TKey, TValue> = Illuminate.LengthAwarePaginator<TKey, TValue>;
}
}

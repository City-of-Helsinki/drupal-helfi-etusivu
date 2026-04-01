import useSWR from 'swr';
import type { SearchResponse } from '../types/SearchResult';
import AppSettings from '../enum/AppSettings';

const fetcher = (url: string): Promise<SearchResponse> => fetch(url).then((res) => res.json());

const useSearchQuery = (query: string, bundle?: string) => {
  const searchUrl = drupalSettings?.helfi_site_search?.search_url;
  const url =
    query.length >= AppSettings.MIN_QUERY_LENGTH && searchUrl ? new URL(searchUrl, window.location.origin) : null;

  url?.searchParams.set('q', query);
  bundle && url?.searchParams.set('bundle', bundle);

  // Use this model as default for now
  url?.searchParams.set('model', 'text-embedding-3-large');

  const key = url?.toString() ?? null;

  return useSWR<SearchResponse>(key, fetcher, { revalidateOnFocus: false });
};

export default useSearchQuery;

import useSWR from 'swr';
import AppSettings from '../enum/AppSettings';
import type { SearchResponse } from '../types/SearchResult';

const fetcher = (url: string): Promise<SearchResponse> => fetch(url).then((res) => res.json());

const useSearchQuery = (query: string, bundle?: string, page: number = 1) => {
  const searchUrl = drupalSettings?.helfi_site_search?.search_url;
  const url =
    query.length >= AppSettings.MIN_QUERY_LENGTH && searchUrl ? new URL(searchUrl, window.location.origin) : null;

  url?.searchParams.set('q', query);
  bundle && url?.searchParams.set('bundle', bundle);
  url?.searchParams.set('page', String(page));
  url?.searchParams.set('size', String(AppSettings.SIZE));
  if (DEBUG_MODE) {
    url?.searchParams.set('debug', '1');
  }

  const key = url?.toString() ?? null;

  return useSWR<SearchResponse>(key, fetcher, { revalidateOnFocus: false, keepPreviousData: true });
};

export default useSearchQuery;

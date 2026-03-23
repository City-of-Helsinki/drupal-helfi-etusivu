import useSWR from 'swr';
import type { SearchResponse } from '../types/SearchResult';
import AppSettings from '../enum/AppSettings';

const fetcher = (url: string): Promise<SearchResponse> =>
  fetch(url).then((res) => res.json());

const useSearchQuery = (query: string) => {
  const searchUrl = drupalSettings?.helfi_site_search?.search_url;
  const key =
    query.length >= AppSettings.MIN_QUERY_LENGTH && searchUrl
      ? `${searchUrl}?q=${encodeURIComponent(query)}`
      : null;

  return useSWR<SearchResponse>(key, fetcher, { revalidateOnFocus: false });
};

export default useSearchQuery;

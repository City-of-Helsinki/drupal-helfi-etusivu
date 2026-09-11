import useSWR from 'swr';
import type { SearchSuggestion } from '../types/SearchSuggestion';

const fetcher = (url: string): Promise<SearchSuggestion[]> => fetch(url).then((res) => res.json());

/**
 * Suggestions are served from the current instance under the active
 * language prefix like for example `/fi/api/v1/search-suggestions`. This
 * is the same endpoint hdbt's vanilla header/hero search suggestions use.
 */
const useSearchSuggestions = (lang: string) =>
  useSWR<SearchSuggestion[]>(`/${lang}/api/v1/search-suggestions`, fetcher, { revalidateOnFocus: false });

export default useSearchSuggestions;

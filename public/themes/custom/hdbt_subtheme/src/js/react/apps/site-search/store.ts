import { atom, type Getter } from 'jotai';

declare const ELASTIC_DEV_URL: string | undefined;

const params = new URLSearchParams(window.location.search);
const initialQuery = params.get('s') ?? '';
const initialBundles = params.get('bundles')?.split(',').filter(Boolean) ?? [];
const parsedPage = Number(params.get('page'));
const initialPage = Number.isFinite(parsedPage) && parsedPage >= 1 ? Math.floor(parsedPage) : 1;

export const stagedQueryAtom = atom<string>(initialQuery);
export const queryAtom = atom<string>(initialQuery);

export const stagedBundlesAtom = atom<string[]>(initialBundles);
export const committedBundlesAtom = atom<string[]>(initialBundles);

export const pageAtom = atom<number>(initialPage);

export const queryResetCountAtom = atom(0);

export const submitCountAtom = atom(0);

export const QUERY_TAG_KEY = ':query';

// Key of the selection tag (a bundle or QUERY_TAG_KEY) that should take focus
// once the search has settled, or null to fall back to the filters. Read by
// useSelectionFocus.
export const selectionFocusAtom = atom<{ tag: string | null } | null>(null);

const getNeighbourTag = (get: Getter, removedTag: string) => {
  const tags = [...(get(queryAtom) ? [QUERY_TAG_KEY] : []), ...get(committedBundlesAtom)];
  const index = tags.indexOf(removedTag);

  return tags[index - 1] ?? tags[index + 1] ?? null;
};

export const submitAllSearchAtom = atom(null, (get, set) => {
  const query = get(stagedQueryAtom);
  const bundles = get(stagedBundlesAtom);

  set(queryAtom, query);
  set(committedBundlesAtom, bundles);
  set(pageAtom, 1);
  set(submitCountAtom, get(submitCountAtom) + 1);

  const newUrl = new URL(window.location.toString());
  query ? newUrl.searchParams.set('s', query) : newUrl.searchParams.delete('s');
  bundles.length > 0 ? newUrl.searchParams.set('bundles', bundles.join(',')) : newUrl.searchParams.delete('bundles');
  newUrl.searchParams.delete('page');

  window.history.pushState({}, '', newUrl);
});

export const submitNewsSearchAtom = atom(null, (get, set) => {
  const query = get(stagedQueryAtom);

  set(queryAtom, query);
  set(pageAtom, 1);
  set(submitCountAtom, get(submitCountAtom) + 1);

  const newUrl = new URL(window.location.toString());
  query ? newUrl.searchParams.set('s', query) : newUrl.searchParams.delete('s');
  newUrl.searchParams.delete('page');

  window.history.pushState({}, '', newUrl);
});

export const removeBundleAtom = atom(null, (get, set, bundle: string) => {
  const newBundles = get(committedBundlesAtom).filter((b) => b !== bundle);

  set(selectionFocusAtom, { tag: getNeighbourTag(get, bundle) });
  set(stagedBundlesAtom, newBundles);
  set(committedBundlesAtom, newBundles);
  set(pageAtom, 1);

  const newUrl = new URL(window.location.toString());
  newBundles.length > 0
    ? newUrl.searchParams.set('bundles', newBundles.join(','))
    : newUrl.searchParams.delete('bundles');
  newUrl.searchParams.delete('page');

  window.history.pushState({}, '', newUrl);
});

export const removeQueryAtom = atom(null, (get, set) => {
  set(selectionFocusAtom, { tag: getNeighbourTag(get, QUERY_TAG_KEY) });
  set(stagedQueryAtom, '');
  set(queryResetCountAtom, (count) => count + 1);
  set(queryAtom, '');
  set(pageAtom, 1);

  const newUrl = new URL(window.location.toString());
  newUrl.searchParams.delete('s');
  newUrl.searchParams.delete('page');

  window.history.pushState({}, '', newUrl);
});

export const clearSelectionsAtom = atom(null, (_get, set) => {
  set(stagedQueryAtom, '');
  set(queryResetCountAtom, (count) => count + 1);
  set(queryAtom, '');
  set(stagedBundlesAtom, []);
  set(committedBundlesAtom, []);
  set(pageAtom, 1);
  set(selectionFocusAtom, { tag: null });

  const newUrl = new URL(window.location.toString());
  newUrl.searchParams.delete('s');
  newUrl.searchParams.delete('bundles');
  newUrl.searchParams.delete('page');

  window.history.pushState({}, '', newUrl);
});

export const setPageAtom = atom(null, (_get, set, page: number) => {
  const clamped = Math.max(1, Math.floor(page));
  set(pageAtom, clamped);

  const newUrl = new URL(window.location.toString());
  clamped > 1 ? newUrl.searchParams.set('page', String(clamped)) : newUrl.searchParams.delete('page');

  window.history.pushState({}, '', newUrl);
});

/**
 * Resolve the URL the search requests are sent to.
 */
const getElasticUrl = () => {
  const devUrl = typeof ELASTIC_DEV_URL !== 'undefined' ? ELASTIC_DEV_URL : '';

  if (devUrl?.length) {
    // Site search doesn't use Elastic, but route requests to test server if dev url is set anyway
    return new URL('https://www.test.hel.ninja/fi/api/v1/search', window.location.origin).toString();
  }

  const searchUrl = drupalSettings?.helfi_site_search?.search_url || '';

  if (!searchUrl) {
    return '';
  }

  return new URL(searchUrl, window.location.origin).toString();
};

export const getElasticUrlAtom = atom(getElasticUrl());

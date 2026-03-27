import { atom } from 'jotai';

const params = new URLSearchParams(window.location.search);
const initialQuery = params.get('s') ?? '';
const initialNewsFilter = params.get('type') === 'news';
const initialBundles = params.get('bundles')?.split(',').filter(Boolean) ?? [];

export const stagedQueryAtom = atom<string>(initialQuery);
export const queryAtom = atom<string>(initialQuery);

export const stagedBundlesAtom = atom<string[]>(initialBundles);
export const committedBundlesAtom = atom<string[]>(initialBundles);

export const submitAllSearchAtom = atom(null, (get, set) => {
  const query = get(stagedQueryAtom);
  const bundles = get(stagedBundlesAtom);

  set(queryAtom, query);
  set(committedBundlesAtom, bundles);

  const newUrl = new URL(window.location.toString());
  query ? newUrl.searchParams.set('s', query) : newUrl.searchParams.delete('s');
  bundles.length > 0 ? newUrl.searchParams.set('bundles', bundles.join(',')) : newUrl.searchParams.delete('bundles');

  window.history.pushState({}, '', newUrl);
});

export const submitNewsSearchAtom = atom(null, (get, set) => {
  const query = get(stagedQueryAtom);

  set(queryAtom, query);

  const newUrl = new URL(window.location.toString());
  query ? newUrl.searchParams.set('s', query) : newUrl.searchParams.delete('s');

  window.history.pushState({}, '', newUrl);
});

export const activeTabAtom = atom<number>(initialNewsFilter ? 1 : 0);

export const setActiveTabAtom = atom(null, (_get, set, tabIndex: number) => {
  set(activeTabAtom, tabIndex);

  const newUrl = new URL(window.location.toString());
  tabIndex === 1 ? newUrl.searchParams.set('type', 'news') : newUrl.searchParams.delete('type');

  window.history.pushState({}, '', newUrl);
});

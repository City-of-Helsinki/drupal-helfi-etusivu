import { atom } from 'jotai';

const params = new URLSearchParams(window.location.search);
const initialQuery = params.get('s') ?? '';
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

export const removeBundleAtom = atom(null, (get, set, bundle: string) => {
  const newBundles = get(committedBundlesAtom).filter((b) => b !== bundle);

  set(stagedBundlesAtom, newBundles);
  set(committedBundlesAtom, newBundles);

  const newUrl = new URL(window.location.toString());
  newBundles.length > 0
    ? newUrl.searchParams.set('bundles', newBundles.join(','))
    : newUrl.searchParams.delete('bundles');

  window.history.pushState({}, '', newUrl);
});

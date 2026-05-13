import { atom } from 'jotai';

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

export const submitAllSearchAtom = atom(null, (get, set) => {
  const query = get(stagedQueryAtom);
  const bundles = get(stagedBundlesAtom);

  set(queryAtom, query);
  set(committedBundlesAtom, bundles);
  set(pageAtom, 1);

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

  const newUrl = new URL(window.location.toString());
  query ? newUrl.searchParams.set('s', query) : newUrl.searchParams.delete('s');
  newUrl.searchParams.delete('page');

  window.history.pushState({}, '', newUrl);
});

export const removeBundleAtom = atom(null, (get, set, bundle: string) => {
  const newBundles = get(committedBundlesAtom).filter((b) => b !== bundle);

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

export const setPageAtom = atom(null, (_get, set, page: number) => {
  const clamped = Math.max(1, Math.floor(page));
  set(pageAtom, clamped);

  const newUrl = new URL(window.location.toString());
  clamped > 1 ? newUrl.searchParams.set('page', String(clamped)) : newUrl.searchParams.delete('page');

  window.history.pushState({}, '', newUrl);
});

import { atom } from 'jotai';

const params = new URLSearchParams(window.location.search);
const initialQuery = params.get('s') ?? '';

export const queryAtom = atom<string>(initialQuery);

export const setQueryAtom = atom(null, (_get, set, query: string) => {
  set(queryAtom, query);

  const newUrl = new URL(window.location.toString());

  if (query) {
    newUrl.searchParams.set('s', query);
  } else {
    newUrl.searchParams.delete('s');
  }

  window.history.pushState({}, '', newUrl);
});

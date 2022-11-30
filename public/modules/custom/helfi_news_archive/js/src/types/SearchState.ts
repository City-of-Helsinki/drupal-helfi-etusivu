import type OptionType from './OptionType';

type SearchStateItem = {
  aggregations?: any;
  value: OptionType[];
};

type SearchState = {
  [key: string]: SearchStateItem;
};

export default SearchState;

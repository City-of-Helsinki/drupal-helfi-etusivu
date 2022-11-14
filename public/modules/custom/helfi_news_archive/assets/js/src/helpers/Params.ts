import SearchComponents from '../enum/SearchComponents';
import type OptionType from '../types/OptionType';
import type SearchState from '../types/SearchState';

const stateToParams = (searchState: SearchState) => {
  return {
    [SearchComponents.NEIGHBOURHOODS]: searchState[SearchComponents.NEIGHBOURHOODS]?.value?.map(
      (value: OptionType) => value.value
    ),
    [SearchComponents.NEWS_GROUPS]: searchState[SearchComponents.NEWS_GROUPS]?.value?.map(
      (value: OptionType) => value.value
    ),
    [SearchComponents.TOPIC]: searchState[SearchComponents.TOPIC]?.value?.map((value: OptionType) => value.value),
  };
};

export default stateToParams;

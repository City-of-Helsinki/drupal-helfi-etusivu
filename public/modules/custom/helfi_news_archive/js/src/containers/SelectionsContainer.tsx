import { Button, IconCross } from 'hds-react';
import { ReactElement, memo } from 'react';

import SearchComponents from '../enum/SearchComponents';
import { clearParams } from '../helpers/Params';
import type OptionType from '../types/OptionType';

type SelectionsContainerProps = {
  clearSelection: Function;
  searchState: any;
  setSearchState: Function;
};

const SelectionsContainer = ({ clearSelection, searchState, setSearchState }: SelectionsContainerProps) => {
  const clearSelections = () => {
    setSearchState({});
    clearParams();
  };

  const filters: ReactElement<HTMLLIElement>[] = [];

  const selections = [SearchComponents.NEWS_GROUPS, SearchComponents.NEIGHBOURHOODS, SearchComponents.TOPIC];

  selections.forEach((key) => {
    if (searchState[key]?.value?.length) {
      searchState[key].value.forEach((selection: OptionType) =>
        filters.push(
          <li
            className='content-tags__tags__tag content-tags__tags--interactive'
            key={`${key}-${selection.value}`}
            onClick={() => clearSelection(selection.value, key)}
          >
            <Button
              aria-label={Drupal.t(
                'Remove @item from search results',
                { '@item': selection.value },
                { context: 'Search: remove item aria label' }
              )}
              className='news-form__remove-selection-button'
              iconRight={<IconCross />}
              variant='supplementary'
            >
              {selection.label}
            </Button>
          </li>
        )
      );
    }
  });

  return (
    <div className='news-form__selections-wrapper'>
      <ul className='news-form__selections-container content-tags__tags'>
        {filters}
        <li className='news-form__clear-all'>
          <Button
            aria-hidden={filters.length ? 'true' : 'false'}
            className='news-form__clear-all-button'
            iconLeft={<IconCross className='news-form__clear-all-icon' />}
            onClick={clearSelections}
            style={filters.length ? {} : { visibility: 'hidden' }}
            variant='supplementary'
          >
            {Drupal.t('Clear selections', {}, { context: 'News archive clear selections' })}
          </Button>
        </li>
      </ul>
    </div>
  );
};

const updateSelections = (prev: SelectionsContainerProps, next: SelectionsContainerProps) => {
  if (prev.searchState[SearchComponents.SUBMIT]?.value === next.searchState[SearchComponents.SUBMIT]?.value) {
    return true;
  }

  return false;
};

export default memo(SelectionsContainer, updateSelections);

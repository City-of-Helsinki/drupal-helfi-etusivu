import { Button, ButtonVariant, TextInput } from 'hds-react';
import { useAtomValue, useSetAtom } from 'jotai';
import { type SyntheticEvent, useState } from 'react';
import { queryAtom, setQueryAtom } from '../store';

const FormContainer = () => {
  const currentQuery = useAtomValue(queryAtom);
  const setQuery = useSetAtom(setQueryAtom);
  const [inputValue, setInputValue] = useState<string>(currentQuery);

  const onSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault();
    setQuery(inputValue);
  };

  return (
    <form className='hdbt-search--react__form-container' role='search' onSubmit={onSubmit}>
      <TextInput
        id='site-search-input'
        label={Drupal.t('Search', {}, { context: 'Site search: input label' })}
        value={inputValue}
        onChange={(e) => setInputValue(e.target.value)}
        className='hdbt-search--react__input'
      />
      <div className='hdbt-search--react__submit'>
        <Button
          className='hdbt-search--react__submit-button'
          type='submit'
          variant={ButtonVariant.Primary}
        >
          {Drupal.t('Search', {}, { context: 'React search: submit button label' })}
        </Button>
      </div>
    </form>
  );
};

export default FormContainer;

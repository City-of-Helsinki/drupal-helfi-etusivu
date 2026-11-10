import { useState } from 'react';
import CardItem from '@/react/common/Card';
import Icon from '@/react/common/Icon';
import DebugBlock from './DebugBlock';

const EXCERPT_LENGTH = 300;

type ResultCardProps = {
  url?: string;
  fragmentUrl?: string;
  title: string;
  contactPersonAddresses?: string[];
  contactPersonEmail?: string;
  contactPersonNumbers?: string[];
  contactPersonOrganisation?: string[];
  contactPersonTitle?: string;
  description?: string;
  bundle?: string;
  publishDate?: number;
  cardModifierClass?: string;
};

const parsePublishDate = (value: number): Date | null => {
  return new Date(value * 1000);
};

const isOlderThanOneYear = (date: Date): boolean => {
  const oneYearAgo = new Date();
  oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);
  return date < oneYearAgo;
};

const toExcerpt = (text: string): string => {
  const boundary = text.lastIndexOf(' ', EXCERPT_LENGTH);
  return `${text.slice(0, boundary > 0 ? boundary : EXCERPT_LENGTH)}...`;
};

const ResultCard = ({
  url,
  fragmentUrl,
  title,
  contactPersonAddresses,
  contactPersonEmail,
  contactPersonNumbers,
  contactPersonOrganisation,
  contactPersonTitle,
  description,
  bundle,
  publishDate,
  cardModifierClass,
}: ResultCardProps) => {
  const isNewsItem = bundle === 'news_item';
  const parsedDate = publishDate ? parsePublishDate(publishDate) : null;
  const isOutdated = isNewsItem && parsedDate ? isOlderThanOneYear(parsedDate) : false;
  const lang = drupalSettings?.path?.currentLanguage ?? 'fi';
  const formattedDate = parsedDate ? parsedDate.toLocaleDateString(lang) : undefined;
  const [expanded, setExpanded] = useState(false);
  const isExpandable = Boolean(description && description.length > EXCERPT_LENGTH);

  const descriptionToggle = (
    <button
      key='description-toggle'
      type='button'
      className='card__description-toggle'
      aria-expanded={expanded}
      onClick={() => setExpanded(!expanded)}
    >
      {expanded
        ? Drupal.t('Close', {}, { context: 'Site search' })
        : Drupal.t('Show more', {}, { context: 'Site search' })}
    </button>
  );

  const fragmentLink = (
    <a key='fragment-link' href={fragmentUrl} className='card__fragment-link'>
      {Drupal.t('Go to the search result on the page', {}, { context: 'Site search' })}
    </a>
  );

  const contactPersonLinksList =
    contactPersonNumbers?.length || contactPersonEmail?.length ? (
      <div className='card__person-links'>
        {contactPersonNumbers?.map((number, index) => (
          // biome-ignore lint/suspicious/noArrayIndexKey: phone numbers are not unique so using the index (list never reorders).
          <div key={index} className='card__person-link card__person-link--phone'>
            <a href={`tel:${number}`} data-is-external='true' data-protocol='tel'>
              {number}
              <span className='link__type link__type--tel' />
              <span className='visually-hidden'>
                {Drupal.t(
                  'Link starts a phone call',
                  {},
                  {
                    context:
                      'Explanation for screen-reader software that the icon visible next to this link means that the link starts a phone call.',
                  },
                )}
              </span>
            </a>
          </div>
        ))}
        {contactPersonEmail && (
          <div className='card__person-link card__person-link--email'>
            <a href={`mailto:${contactPersonEmail}`} data-is-external='true' data-protocol='mailto'>
              {contactPersonEmail}
              <span className='link__type link__type--mailto' />
              <span className='visually-hidden'>
                {Drupal.t(
                  'Link opens default mail program',
                  {},
                  {
                    context:
                      'Explanation for screen-reader software that the icon visible next to this link means that the link opens default mail program.',
                  },
                )}
              </span>
            </a>
          </div>
        )}
      </div>
    ) : undefined;

  const contactPersonAddressesList = contactPersonAddresses?.length ? (
    <div className='card__persons-addresses'>
      {contactPersonAddresses?.map((address, index) => (
        // biome-ignore lint/suspicious/noArrayIndexKey: addresses are not unique so using the index (list never reorders).
        <div key={index} className='card__person-address'>
          {address}
        </div>
      ))}
    </div>
  ) : undefined;

  const contactPersonOrganisationStructure = contactPersonOrganisation?.length ? (
    <div className='card__person-organisation'>
      {contactPersonOrganisation?.map((organisation, index) => (
        // biome-ignore lint/suspicious/noArrayIndexKey: the organisation is not unique so using the index (list never reorders).
        <span key={index} className='card__person-organisation-item'>
          {organisation}
        </span>
      ))}
    </div>
  ) : undefined;

  const cardItem = (
    <CardItem
      cardTitle={title}
      cardUrl={url}
      cardContactPersonAddresses={contactPersonAddressesList}
      cardContactPersonLinks={contactPersonLinksList}
      cardContactPersonOrganisation={contactPersonOrganisationStructure}
      cardContactPersonTitle={contactPersonTitle}
      cardDescription={description && isExpandable && !expanded ? toExcerpt(description) : description}
      cardModifierClass={cardModifierClass}
      cardTitleLevel={3}
      {...(isExpandable && {
        customMetaRows: { top: expanded && fragmentUrl ? [fragmentLink, descriptionToggle] : [descriptionToggle] },
      })}
      {...(isNewsItem &&
        formattedDate && {
          date: formattedDate,
          dateLabel: Drupal.t('Published', {}, { context: 'Site search' }),
        })}
      {...(isOutdated && {
        cardTags: [
          {
            tag: Drupal.t(
              'Published over a year ago',
              {},
              { context: 'The helper text before the node published timestamp' },
            ),
            color: 'alert',
            iconStart: <Icon icon='alert-circle' />,
          },
        ],
      })}
    />
  );

  if (DEBUG_MODE) {
    return (
      <div>
        {cardItem}
        <DebugBlock data={{ url, fragmentUrl, title, description, bundle, publishDate }} />
      </div>
    );
  }

  return cardItem;
};

export default ResultCard;

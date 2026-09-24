import { useState } from 'react';
import CardItem from '@/react/common/Card';
import Icon from '@/react/common/Icon';
import DebugBlock from './DebugBlock';

const EXCERPT_LENGTH = 300;

type ResultCardProps = {
  url: string;
  title: string;
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

const ResultCard = ({ url, title, description, bundle, publishDate, cardModifierClass }: ResultCardProps) => {
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

  const cardItem = (
    <CardItem
      cardTitle={title}
      cardUrl={url}
      cardDescription={description && isExpandable && !expanded ? toExcerpt(description) : description}
      cardModifierClass={cardModifierClass}
      cardTitleLevel={3}
      {...(isExpandable && { customMetaRows: { bottom: [descriptionToggle] } })}
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
        <DebugBlock data={{ url, title, description, bundle, publishDate }} />
      </div>
    );
  }

  return cardItem;
};

export default ResultCard;

import CardItem from '@/react/common/Card';
import Icon from '@/react/common/Icon';

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

const ResultCard = ({ url, title, description, bundle, publishDate, cardModifierClass }: ResultCardProps) => {
  const isNewsItem = bundle === 'news_item';
  const parsedDate = publishDate ? parsePublishDate(publishDate) : null;
  const isOutdated = isNewsItem && parsedDate ? isOlderThanOneYear(parsedDate) : false;
  const lang = drupalSettings?.path?.currentLanguage ?? 'fi';
  const formattedDate = parsedDate ? parsedDate.toLocaleDateString(lang) : undefined;

  return (
    <CardItem
      cardTitle={title}
      cardUrl={url}
      cardDescription={description}
      cardDescriptionHtml={true}
      cardDescriptionAllowedTags={['p', 'ol', 'ul', 'li']}
      cardModifierClass={cardModifierClass}
      cardTitleLevel={3}
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
};

export default ResultCard;

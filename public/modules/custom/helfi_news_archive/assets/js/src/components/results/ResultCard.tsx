import { format } from 'date-fns';

import Result from '../../types/Result';

const ResultCard = ({
  alt,
  field_main_image_caption,
  field_main_image,
  field_photographer,
  title,
  published_at,
  url,
}: Result) => {
  const getPublished = () => {
    if (!published_at || !published_at.length) {
      return null;
    }

    const published = new Date(published_at[0] * 1000);
    const htmlTime = `${format(published, 'Y-MM-dd')}T${format(published, 'HH:mm')}`;
    const visibleTime = format(published, 'd.M.Y H:mm');

    return (
      <time dateTime={htmlTime} className='news-listing__datetime news-listing__datetime--published'>
        <span className='visually-hidden'>
          {Drupal.t('Published', {}, { context: 'The helper text before the node published timestamp' })}
        </span>
        {visibleTime}
      </time>
    );
  };

  const getAlt = () => {
    if (field_main_image_caption && field_main_image_caption.length) {
      return field_main_image_caption[0];
    }
    if (alt && alt.length) {
      return alt[0];
    }

    return '';
  };

  const getImage = () => {
    if (!field_main_image || !field_main_image.length) {
      return null;
    }

    return (
      <img
        src={field_main_image[0]}
        alt={getAlt()}
        data-photographer={field_photographer && field_photographer.length ? field_photographer[0] : null}
      />
    );
  };

  return (
    <li className='news-listing__item'>
      <div className='news-listing__content news-listing__content--with-image' role='article'>
        <h3 className='news-listing__title'>
          <a href={url[0]} className='news-listing__link'>
            {title}
          </a>
        </h3>
        {getPublished()}
      </div>
      {field_main_image && field_main_image.length && <div className='news-listing__img'>{getImage()}</div>}
    </li>
  );
};

export default ResultCard;

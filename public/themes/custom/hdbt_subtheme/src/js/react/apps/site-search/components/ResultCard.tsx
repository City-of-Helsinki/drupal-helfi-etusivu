type ResultCardProps = {
  url: string;
  title: string;
  entity_type?: string;
  description?: string;
  className?: string;
};

const ResultCard = ({ url, title, entity_type, description, className }: ResultCardProps) => (
  <div className={`result-card${className ? ` ${className}` : ''}`}>
    <a href={url} className='result-card__link'>
      {title}
    </a>
    {description && <p className='result-card__description'>{description}</p>}
    {entity_type && <span className='result-card__type'>{entity_type}</span>}
  </div>
);

export default ResultCard;

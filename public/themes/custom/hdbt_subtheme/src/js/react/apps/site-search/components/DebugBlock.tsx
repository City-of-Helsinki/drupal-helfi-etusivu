type DebugBlockProps = {
  label?: string;
  data: unknown;
};

const DebugBlock = ({ label, data }: DebugBlockProps) => (
  <pre
    style={{
      background: '#fffae5',
      border: '1px solid #d8a200',
      padding: '8px',
      fontSize: '12px',
      margin: '0 0 16px',
      whiteSpace: 'pre-wrap',
    }}
  >
    {label ? `${label}\n` : ''}
    {JSON.stringify(data, null, 2)}
  </pre>
);

export default DebugBlock;

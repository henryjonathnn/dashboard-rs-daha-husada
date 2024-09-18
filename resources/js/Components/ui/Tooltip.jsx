import React from 'react';

const Tooltip = ({ message, isVisible }) => {
  if (!isVisible) return null;
  
  return (
    <div className="tooltip">
      <div className="tooltip-content">{message}</div>
      <div className="tooltip-arrow" />
    </div>
  );
};

export default React.memo(Tooltip);
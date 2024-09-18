import React, { useState } from 'react';

const Card = ({ name, icon, bgColor, value, hasDetail, onClick, tooltipText }) => {
  const [isTooltipVisible, setIsTooltipVisible] = useState(false);

  return (
    <div
      className={`relative flex flex-col items-center rounded-lg bg-white shadow-lg px-4 py-4 lg:py-6 w-full transition-transform transform ${
        hasDetail ? 'cursor-pointer hover:bg-gray-100 hover:scale-105' : ''
      }`}
      onClick={hasDetail ? onClick : undefined}
      onMouseEnter={() => setIsTooltipVisible(true)}
      onMouseLeave={() => setIsTooltipVisible(false)}
    >
      <div className='flex items-center space-x-2'>
        <div className={`flex items-center justify-center rounded-full p-2 w-9 h-9 ${bgColor}`}>
          {icon}
        </div>
        <div className='text-slate-800 font-bold text-base lg:text-lg'>{name}</div>
      </div>
      <div className='mt-4 text-base lg:text-lg font-bold text-slate-700'>{value}</div>

      {tooltipText && isTooltipVisible && (
        <div className="tooltip absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-max max-w-[200px] px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm transition-opacity duration-300">
          <div className="tooltip-content">{tooltipText}</div>
          <div className="tooltip-arrow absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-8 border-transparent border-t-gray-900" />
        </div>
      )}
    </div>
  );
};

export default React.memo(Card);
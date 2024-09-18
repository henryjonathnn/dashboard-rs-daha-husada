import React from 'react';

const Loading = () => {
  return (
    <div className="fixed inset-0 flex justify-center items-center bg-white bg-opacity-80 z-50">
      <div className="animate-spin rounded-full h-32 w-32 border-t-4 border-b-4 border-green"></div>
    </div>
  );
};

export default React.memo(Loading);
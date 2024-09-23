import React, { useState, useEffect } from 'react';

const Label = ({ lastUpdateTime, prefix = 'Data terbaru masuk' }) => {
  const [timeAgo, setTimeAgo] = useState('');

  useEffect(() => {
    const updateTimeAgo = () => {
      if (!lastUpdateTime) {
        setTimeAgo('Data belum tersedia');
        return;
      }

      const now = new Date();
      const updateTime = new Date(lastUpdateTime);
      const diffInSeconds = Math.floor((now - updateTime) / 1000);

      if (diffInSeconds < 60) {
        setTimeAgo(`${diffInSeconds} detik yang lalu`);
      } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        setTimeAgo(`${minutes} menit yang lalu`);
      } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        setTimeAgo(`${hours} jam yang lalu`);
      } else {
        const days = Math.floor(diffInSeconds / 86400);
        setTimeAgo(`${days} hari yang lalu`);
      }
    };

    updateTimeAgo();
    const intervalId = setInterval(updateTimeAgo, 60000); // Update every minute

    return () => clearInterval(intervalId);
  }, [lastUpdateTime]);

  return (
    <div className="text-sm font-semibold text-gray-800 mb-6">
      {prefix}: {timeAgo}
    </div>
  );
};

export default Label;
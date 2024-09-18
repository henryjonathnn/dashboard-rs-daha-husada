import React from 'react';
import { Bar } from 'react-chartjs-2';
import { Chart as ChartJS, BarElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(BarElement, CategoryScale, LinearScale);

const XBarChart = ({ data }) => {
  const options = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y', // Mengatur grafik menjadi horizontal
    scales: {
      x: {
        beginAtZero: true,
        ticks: {
          font: {
            size: 11,
          },
        },
      },
      y: {
        ticks: {
          font: {
            size: 11,
          },
        },
      },
    },
    plugins: {
      legend: {
        display: false,
      },
    },
  };

  return (
    <div className="bg-white p-4 rounded-lg shadow-lg">
      <h3 className="font-semibold text-sm">Grafik Unit</h3>
      <div style={{ width: '100%', height: 300 }}>
        <Bar data={data} options={options} />
      </div>
    </div>
  );
};

export default React.memo(XBarChart);

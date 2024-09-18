import React from 'react';
import { Line } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend
);

const LineChart = ({ data, title, label }) => {
  if (!data || typeof data !== 'object') {
    console.error('Invalid data provided to LineChart:', data);
    return <div>Error: Invalid data for chart</div>;
  }

  let chartData;
  if (Array.isArray(data)) {
    chartData = {
      labels: data.map(item => item.tanggal),
      datasets: [
        {
          label: label,
          data: data.map(item => item.total),
          fill: false,
          borderColor: 'rgb(75, 192, 192)',
          tension: 0.1
        }
      ]
    };
  } else {
    // Assuming data is an object with date keys and total values
    const sortedDates = Object.keys(data).sort((a, b) => new Date(a) - new Date(b));
    chartData = {
      labels: sortedDates,
      datasets: [
        {
          label: label,
          data: sortedDates.map(date => data[date]),
          fill: false,
          borderColor: 'rgb(75, 192, 192)',
          tension: 0.1
        }
      ]
    };
  }

  const options = {
    responsive: true,
    plugins: {
      legend: {
        position: 'top',
        display: false,
      },
      title: {
        display: true,
        text: title,
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          precision: 0
        }
      }
    }
  };

  return <Line data={chartData} options={options} />;
};

export default LineChart;
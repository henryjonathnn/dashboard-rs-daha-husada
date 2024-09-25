import React, { useState, useCallback, useMemo } from 'react';
import { usePage, router } from '@inertiajs/react';
import Card from '../Components/ui/Card';
import { IoSendSharp } from "react-icons/io5";
import { FaTools, FaCheckCircle } from "react-icons/fa";
import { MdPendingActions, MdOutlineAccessTimeFilled } from "react-icons/md";
import Selector from '../Components/ui/Selector';
import LineChart from '../Components/charts/LineChart';
import Modal from '../Components/ui/Modal';
import Label from '../Components/ui/Label';

const PermintaanUpdate = () => {
  const { data_bulan, data_tahun, totalData, totalStatus, detailStatus, selectedMonth, selectedYear, dailyRequests, lastUpdateTime } = usePage().props;

  const [modalOpen, setModalOpen] = useState(false);
  const [modalData, setModalData] = useState([]);
  const [modalTitle, setModalTitle] = useState('');

  const handleSelectionChange = useCallback(({ month, year }) => {
    router.get('/permintaan-update', { month, year }, {
      preserveState: true,
      preserveScroll: true,
    });
  }, []);

  const openModal = useCallback((type) => {
    let detailData;
    switch (type) {
      case 'terkirim':
        detailData = { "Menunggu": detailStatus['Terkirim'] };
        break;
      case 'proses':
        detailData = { "Proses": detailStatus['Dalam Pengerjaan'] };
        break;
      case 'selesai':
        detailData = { "Selesai": detailStatus['Selesai'] };
        break;
      case 'pending':
        detailData = { "Pending": detailStatus['Pending'] };
        break;
      default:
        detailData = {};
    }

    setModalData(detailData);
    setModalTitle({
      terkirim: 'Detail Data Menunggu',
      proses: 'Detail Data Proses',
      pending: 'Detail Data Pending',
      selesai: 'Detail Data Selesai',
    }[type] || `Detail Data ${type.charAt(0).toUpperCase() + type.slice(1)}`);
    setModalOpen(true);
  }, [detailStatus]);

  const cards = useMemo(() => [
    { name: 'Menunggu', icon: <IoSendSharp />, bgColor: 'bg-sky-200', value: totalStatus['Terkirim'] || 0, hasDetail: true, detailType: 'terkirim', tooltipText: 'Jumlah komplain yang terkirim, namun belum diproses oleh tim IT.' },
    { name: 'Proses', icon: <FaTools />, bgColor: 'bg-yellow-200', value: totalStatus['Dalam Pengerjaan'] || 0, hasDetail: true, detailType: 'proses', tooltipText: 'Jumlah komplain yang sedang diproses oleh tim IT.' },
    { name: 'Selesai', icon: <FaCheckCircle />, bgColor: 'bg-green', value: totalStatus['Selesai'] || 0, hasDetail: true, detailType: 'selesai', tooltipText: 'Jumlah komplain yang sudah berhasil diselesaikan.' },
    { name: 'Pending', icon: <MdPendingActions />, bgColor: 'bg-slate-300', value: totalStatus['Pending'] || 0, hasDetail: true, detailType: 'pending', tooltipText: 'Jumlah komplain yang ditunda.' },
    { name: 'Respon Time', icon: <MdOutlineAccessTimeFilled />, bgColor: 'bg-orange-300', value: totalData.respon_time || 'N/A', hasDetail: false, tooltipText: 'Rata-rata waktu respon untuk menangani komplain.' },
    { name: 'Durasi Pengerjaan', icon: <MdOutlineAccessTimeFilled />, bgColor: 'bg-violet-300', value: totalData.durasi_pengerjaan || 'N/A', hasDetail: false, tooltipText: 'Rata-rata durasi waktu pengerjaan untuk menyelesaikan komplain.' },
  ], [totalStatus, totalData]);

  const chartData = useMemo(() => {
    return Object.entries(dailyRequests).map(([day, count]) => ({
      tanggal: `${day}/${selectedMonth}`,
      total: count
    }));
  }, [dailyRequests, selectedMonth]);

  return (
    <div className="py-2">
      <div className="max-w-screen-2xl h-max mx-auto sm:px-6 lg:px-2">
        <div className="overflow-hidden sm:rounded-lg">
          <div className="p-2">
            <Selector
              title={`Laporan Komplain IT Bulan ${data_bulan.find(m => m.value === selectedMonth)?.label} ${selectedYear}`}
              onSelectionChange={handleSelectionChange}
              data_bulan={data_bulan}
              data_tahun={data_tahun}
              selectedMonth={selectedMonth}
              selectedYear={selectedYear}
            />
            <Label lastUpdateTime={lastUpdateTime} />
            <h3 className='text-base lg:text-lg font-bold text-white mb-2'>
              <span className='bg-light-green py-2 px-3 rounded'>
                {`Total Komplain: ${totalData.total_komplain || 0}`}
              </span>
            </h3>

            <div className='grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mt-8 lg:mt-12'>
              {cards.map((card, index) => (
                <Card key={index} {...card} onClick={card.hasDetail ? () => openModal(card.detailType) : undefined} />
              ))}
            </div>

            <div className='bg-white mt-14 rounded-md p-3 shadow-lg'>
              <LineChart 
                data={chartData}
                title={`Jumlah Permintaan Update Harian - ${data_bulan.find(m => m.value === selectedMonth)?.label} ${selectedYear}`}
                label="Jumlah Permintaan"
              />
            </div>

            <Modal
              isOpen={modalOpen}
              onClose={() => setModalOpen(false)}
              data={modalData}
              title={modalTitle}
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default React.memo(PermintaanUpdate);
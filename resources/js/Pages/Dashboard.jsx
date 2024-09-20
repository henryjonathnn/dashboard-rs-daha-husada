import React, { useState, useCallback, lazy, Suspense } from 'react';
import { usePage, router } from '@inertiajs/react';
import Card from '@/Components/ui/Card';
import Selector from '@/Components/ui/Selector';
import { IoSendSharp } from "react-icons/io5";
import { FaTools, FaCheckCircle } from "react-icons/fa";
import { MdPendingActions, MdOutlineAccessTimeFilled } from "react-icons/md";

const Modal = lazy(() => import('@/Components/ui/Modal'));

const Dashboard = () => {
    const { komplainData, updateData, selectedYear, selectedMonth } = usePage().props;

    const [month, setMonth] = useState(selectedMonth);
    const [year, setYear] = useState(selectedYear);
    const [modalOpen, setModalOpen] = useState(false);
    const [modalData, setModalData] = useState([]);
    const [modalTitle, setModalTitle] = useState('');

    const handleSelectionChange = useCallback(({ month, year }) => {
        setMonth(month);
        setYear(year);
        router.get('/', { month, year }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, []);

    const openModal = useCallback((data, type, prefix) => {
        let detailData;
        if (prefix === 'Komplain') {
            switch (type) {
                case 'Terkirim':
                    detailData = data.detailStatus?.detail_data_terkirim || [];
                    break;
                case 'proses':
                    detailData = data.detailStatus?.detail_data_proses || [];
                    break;
                case 'Selesai':
                    detailData = data.detailStatus?.detail_data_selesai || [];
                    break;
                case 'Pending':
                    detailData = data.detailStatus?.detail_data_pending || [];
                    break;
                default:
                    detailData = [];
            }
        } else if (prefix === 'Permintaan Update') {
            switch (type) {
                case 'Terkirim':
                    detailData = data.detailStatus?.detail_data_terkirim || [];
                    break;
                case 'proses':
                    detailData = data.detailStatus?.detail_data_proses || [];
                    break;
                case 'Selesai':
                    detailData = data.detailStatus?.detail_data_selesai || [];
                    break;
                case 'Pending':
                    detailData = data.detailStatus?.detail_data_pending || [];
                    break;
                default:
                    detailData = [];
            }
        }

        if (detailData && detailData.length > 0) {
            setModalData(detailData);
            setModalTitle({
                terkirim: 'Detail Data Menunggu',
                proses: 'Detail Data Proses',
                pending: 'Detail Data Pending',
                selesai: 'Detail Data Selesai',
              }[type] || `Detail Data ${type.charAt(0).toUpperCase() + type.slice(1)}`);
          
            setModalOpen(true);
        } else {
            console.log(`No detail data available for ${prefix} ${type}`);
            // You might want to show a message to the user here
        }
    }, []);

    const createCards = (data, prefix) => [
        { name: 'Menunggu', icon: <IoSendSharp />, bgColor: 'bg-sky-200', value: data.totalStatus?.['Terkirim'] || 0, hasDetail: true, detailType: 'Terkirim', tooltipText: `Jumlah ${prefix} yang terkirim, namun belum diproses oleh tim IT.` },
        { name: 'Proses', icon: <FaTools />, bgColor: 'bg-yellow-200', value: data.totalStatus?.['Dalam Pengerjaan / Pengecekan Petugas'] || 0, hasDetail: true, detailType: 'proses', tooltipText: `Jumlah ${prefix} yang sedang diproses oleh tim IT.` },
        { name: 'Selesai', icon: <FaCheckCircle />, bgColor: 'bg-green', value: data.totalStatus?.['Selesai'] || 0, hasDetail: true, detailType: 'Selesai', tooltipText: `Jumlah ${prefix} yang sudah berhasil diselesaikan.` },
        { name: 'Pending', icon: <MdPendingActions />, bgColor: 'bg-slate-200', value: data.totalStatus?.['Pending'] || 0, hasDetail: true, detailType: 'Pending', tooltipText: `Jumlah ${prefix} yang ditunda.` },
        { name: 'Respon Time', icon: <MdOutlineAccessTimeFilled />, bgColor: 'bg-orange-200', value: data.totalData?.respon_time || 'N/A', hasDetail: false, tooltipText: `Rata-rata waktu respon untuk menangani ${prefix}.` },
        { name: 'Durasi Pengerjaan', icon: <MdOutlineAccessTimeFilled />, bgColor: 'bg-violet-200', value: data.totalData?.durasi_pengerjaan || 'N/A', hasDetail: false, tooltipText: `Rata-rata durasi waktu pengerjaan untuk menyelesaikan ${prefix}.` },
    ];

    const komplainCards = createCards(komplainData, 'komplain');
    const updateCards = createCards(updateData, 'permintaan update');

    return (
        <div className="py-2">
            <div className="max-w-screen-2xl h-max mx-auto sm:px-6 lg:px-2">
                <div className="overflow-hidden sm:rounded-lg">
                    <div className="p-2">
                        <Selector
                            title={`Dashboard IT Bulan ${komplainData.data_bulan.find(m => m.value === month)?.label} ${year}`}
                            onSelectionChange={handleSelectionChange}
                            data_bulan={komplainData.data_bulan}
                            data_tahun={komplainData.data_tahun}
                            selectedMonth={month}
                            selectedYear={year}
                        />
                        
                        <h2 className="text-xl font-bold mb-4 mt-8">Komplain IT</h2>
                        <h3 className='text-base lg:text-lg font-bold text-white mb-4'>
                            <span className='bg-light-green py-2 px-3 rounded'>
                                {`Total Komplain: ${komplainData.totalData?.total_komplain || 0}`}
                            </span>
                        </h3>
                        <div className='grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8'>
                            {komplainCards.map((card, index) => (
                                <Card 
                                    key={`komplain-${index}`} 
                                    {...card} 
                                    onClick={card.hasDetail ? () => openModal(komplainData, card.detailType, 'Komplain') : undefined}
                                />
                            ))}
                        </div>

                        <h2 className="text-xl font-bold mb-4 mt-12">Permintaan Update IT</h2>
                        <h3 className='text-base lg:text-lg font-bold text-white mb-4'>
                            <span className='bg-light-green py-2 px-3 rounded'>
                                {`Total Permintaan: ${updateData.totalData?.total_permintaan || 0}`}
                            </span>
                        </h3>
                        <div className='grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4'>
                            {updateCards.map((card, index) => (
                                <Card 
                                    key={`update-${index}`} 
                                    {...card} 
                                    onClick={card.hasDetail ? () => openModal(updateData, card.detailType, 'Permintaan Update') : undefined}
                                />
                            ))}
                        </div>

                        <Suspense fallback={<div>Loading...</div>}>
                            <Modal
                                isOpen={modalOpen}
                                onClose={() => setModalOpen(false)}
                                data={modalData}
                                title={modalTitle}
                            />
                        </Suspense>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default React.memo(Dashboard);